<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\user;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Coupon;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\OrderDeliveryTrackMail;
use Illuminate\Support\Facades\Mail;
use App\Exports\OrdersExport;
use Maatwebsite\Excel\Facades\Excel;

class OrderController extends Controller
{
    public function getIndex()
    {
        return view('admin.orders.index');
    }

    public function getList(Request $request)
    {
        $orders = Order::with(['user', 'coupon', 'items.product.category'])
            ->when($request->user_id, function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            })
            ->when($request->category_id, function ($q) use ($request) {
                $q->whereHas('items.product', function ($p) use ($request) {
                    $p->where('category_id', $request->category_id);
                });
            })
            ->when($request->product_id, function ($q) use ($request) {
                $q->whereHas('items', function ($i) use ($request) {
                    $i->where('product_id', $request->product_id);
                });
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when(auth()->user()->type == 'employee', function ($q) {
                $q->whereHas('coupon', function ($coupon) {
                    $coupon->where('employee_id', auth()->id());
                });
            })
            ->latest();

        return datatables()->of($orders)
            ->addColumn('order_no', function ($o) {
                return \Illuminate\Support\Str::limit($o->order_number, 20);
            })

            ->addColumn('user', function ($o) {
                $code = \Illuminate\Support\Str::limit($o->user->code, 10);
                $name = \Illuminate\Support\Str::limit($o->user->name, 10);
        
                return '[ <b>'.$code.'</b> ]<br>'.$name;
            })
            
            ->addColumn('category', function ($o) {
            
                return $o->items
                    ->pluck('product.category.name')
                    ->unique()
                    ->map(function ($name) {
                        return \Illuminate\Support\Str::limit($name, 10);
                    })
                    ->implode(', ');
            })
            
            ->addColumn('products', function ($order) {
                return $order->items->map(function ($item) {
                    $name = \Illuminate\Support\Str::limit($item->product_name, 10);
                    return '<div class="mb-1">'.$name.'</div>';
                })->implode('');
            })
            ->addColumn('items_count', function ($order) {
                return '<span class="fw-bold">'.$order->items->count().'</span>';
            })
            ->addColumn('amount', fn ($o) => '₹ '.number_format($o->total_amount, 2))
            ->addColumn('status', function ($o) {
                return match ($o->status) {
                    'pending'   => '<span class="badge bg-warning">Pending</span>',
                    'paid'      => '<span class="badge bg-info">Paid</span>',
                    'packed'    => '<span class="badge bg-primary">Packed</span>',
                    'shipped'   => '<span class="badge bg-dark">Shipped</span>',
                    'delivered' => '<span class="badge bg-success">Delivered</span>',
                    'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
                    'rto'       => '<span class="badge bg-dark text-warning">RTO</span>',
                    default     => $o->status
                };
            })
            ->addColumn('created_at', fn ($o) =>
                $o->created_at->format('d M Y h:i A')
            )
            ->rawColumns(['user', 'products', 'status'])
            ->make(true);
    }

    public function export(Request $request)
    {
        $request->validate([
            'from_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'to_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:from_date',
            ],

            'status' => [
                'nullable',
                'in:pending,paid,packed,shipped,delivered,rto,cancelled',
            ],

            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'product_id' => [
                'nullable',
                'integer',
                'exists:products,id',
            ],
        ]);

        $fileName = 'orders_' .
            $request->from_date .
            '_to_' .
            $request->to_date .
            '.xlsx';

        return Excel::download(
            new OrdersExport(
                $request->from_date,
                $request->to_date,
                $request->status,
                $request->user_id,
                $request->category_id,
                $request->product_id,
                auth()->user()
            ),
            $fileName
        );
    }

    public function getView(Request $request, $id)
    {
        $order = Order::with([
            'user',
            'payment',
            'coupon',
            'addressData',
            'items',
            'items.product' => function ($q) {
                $q->withTrashed();
            },
            'items.product.images',
            'items.product.category',
            'items.product.storeReviews' => function ($q) {
                $q->select('id','product_id','user_id','rating','review');
            }
        ])->findOrFail($id);

        if (
            auth()->user()->type == 'employee' &&
            (
                !$order->coupon ||
                $order->coupon->employee_id != auth()->id()
            )
        ) {
            abort(403);
        }

        return view('admin.orders.view', compact('order'));
    }
    
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,packed,shipped,rto,cancelled',
            'cancel_reason' => 'required_if:status,cancelled|nullable|string|max:1000',
        ]);

        $order = Order::with('coupon')->findOrFail($id);

        // Employee permission check
        if (
            auth()->user()->type == 'employee' &&
            (
                !$order->coupon ||
                $order->coupon->employee_id != auth()->id()
            )
        ) {
            abort(403);
        }

        $status = $request->status;

        $updateData = [
            'status' => $status,
            'shipping_status' => $status,
        ];

        /*
        |--------------------------------------------------------------------------
        | RTO
        |--------------------------------------------------------------------------
        */
        if ($status === 'rto') {
            $updateData['rto_at'] = $order->rto_at ?? now();
        } else {
            $updateData['rto_at'] = null;
        }

        /*
        |--------------------------------------------------------------------------
        | CANCELLED
        |--------------------------------------------------------------------------
        */
        if ($status === 'cancelled') {
            $updateData['cancelled_at'] = $order->cancelled_at ?? now();
            $updateData['cancel_reason'] = trim($request->cancel_reason);
        } else {
            $updateData['cancelled_at'] = null;
            $updateData['cancel_reason'] = null;
        }

        $order->update($updateData);

        return back()->with(
            'success',
            'Order status updated successfully.'
        );
    }

    public function generatePdf($id)
    {
        $pdf = $this->buildInvoicePdf($id);

        $fileName = 'invoice_' . $id . '.pdf';
        $directory = storage_path('app/public/invoices');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $fullPath = $directory . DIRECTORY_SEPARATOR . $fileName;

        file_put_contents($fullPath, $pdf->output());

        Order::where('id', $id)->update([
            'pdf' => 'invoices/' . $fileName,
        ]);

        return back()->with(
            'success',
            'PDF generated successfully.'
        );
    }

    public function viewPdf($id)
    {
        $order = Order::findOrFail($id);

        $this->authorizeInvoiceAccess($order);

        $pdf = $this->buildInvoicePdf($id);

        return response(
            $pdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' =>
                    'inline; filename="Invoice-' .
                    $order->invoice_number .
                    '.pdf"',
            ]
        );
    }

    public function downloadPdf($id)
    {
        $order = Order::findOrFail($id);

        $this->authorizeInvoiceAccess($order);

        $pdf = $this->buildInvoicePdf($id);

        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf->output();
            },
            'Invoice-' . $order->invoice_number . '.pdf',
            [
                'Content-Type' => 'application/pdf',
            ]
        );
    }

    /**
     * Common PDF builder for generate/view/download.
     *
     * The invoice HTML is rendered first. After Dompdf knows the final
     * page count, the legal footer is painted on the LAST page only.
     */
    private function buildInvoicePdf($id)
    {
        $order = Order::with([
            'user',
            'payment',
            'coupon',
            'addressData',
            'items',
            'items.product' => function ($q) {
                $q->withTrashed();
            },
            'items.product.images',
            'items.product.category',
            'items.product.storeReviews',
        ])->findOrFail($id);

        $this->authorizeInvoiceAccess($order);

        $orderData = $this->formatOrderData($order);
    
        $pdf = Pdf::loadView(
            'pdf.invoice',
            [
                'order' => $order,
                'orderData' => $orderData,
            ]
        );

        $pdf->setPaper('a4', 'portrait');

        $dompdf = $pdf->getDomPDF();

        // First render: Dompdf now knows the actual page count.
        $dompdf->render();

        $canvas = $dompdf->getCanvas();

        // Draw footer ONLY on the final page.
        $canvas->page_script(
            function (
                $pageNumber,
                $pageCount,
                $canvas,
                $fontMetrics
            ) {
                if ($pageNumber !== $pageCount) {
                    return;
                }

                $pageWidth = $canvas->get_width();
                $pageHeight = $canvas->get_height();

                $font = $fontMetrics->getFont(
                    'DejaVu Sans',
                    'normal'
                );

                $fontSize = 8;
                $textColor = [0.38, 0.38, 0.38];
                $lineColor = [0.85, 0.85, 0.85];

                // Keep the footer inside the bottom margin reserved by @page.
                $lineY = $pageHeight - 66;

                $canvas->line(
                    30,
                    $lineY,
                    $pageWidth - 30,
                    $lineY,
                    $lineColor,
                    0.6
                );

                $lines = [
                    'Please note that this invoice is not a demand for payment.',
                    'Regd Office: Veltex Services Private Limited',
                    '711, Plot A09, ITL Tower, Netaji Subhash Place, Pitampura, Delhi 110034',
                    'Email: care@astrotring.shop',
                ];

                $y = $pageHeight - 53;

                foreach ($lines as $line) {
                    $textWidth = $fontMetrics->getTextWidth(
                        $line,
                        $font,
                        $fontSize
                    );

                    $x = max(
                        30,
                        ($pageWidth - $textWidth) / 2
                    );

                    $canvas->text(
                        $x,
                        $y,
                        $line,
                        $font,
                        $fontSize,
                        $textColor
                    );

                    $y += 11;
                }
            }
        );

        return $pdf;
    }

    /**
     * Centralized employee access check for every invoice endpoint.
     */
    private function authorizeInvoiceAccess(Order $order): void
    {
        if (
            auth()->user()->type === 'employee' &&
            (
                !$order->coupon ||
                $order->coupon->employee_id != auth()->id()
            )
        ) {
            abort(403);
        }
    }

    private function formatOrderData($order)
    {
        return [

            // BASIC
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'invoice_number' => $order->invoice_number,
            'hsn_code' => $order->hsn_code,
            'status' => $order->status,

            // USER
            'user_id' => $order->user_id,

            // PRICING
            'pricing' => [
                'subtotal' => $order->subtotal,
                'discount' => $order->discount,
                'taxable_amount' => $order->taxable_amount,
                'gst_rate' => $order->gst_rate,
                'tax_type' => $order->tax_type,
                'cgst_amount' => $order->cgst_amount,
                'sgst_amount' => $order->sgst_amount,
                'igst_amount' => $order->igst_amount,
                'delivery_charge' => $order->delivery_charge,
                'cod_charge' => (float) (
                    $order->price_breakdown['cod_charge'] ?? 0
                ),
                'wallet_used' => $order->wallet_used,
                'paid_amount' => $order->paid_amount,
                'total_amount' => $order->total_amount,
            ],

            // PAYMENT
            'payment' => $order->payment ? [
                'payment_id' => $order->payment->id,
                'transaction_id' => $order->payment->transaction_id,
                'gateway' => $order->payment->payment_gateway,
                'mode' => $order->payment->payment_mode,
                'amount' => $order->payment->amount,
                'currency' => $order->payment->currency,
                'status' => $order->payment->payment_status,
                'paid_at' => $order->paid_at,
            ] : [
                'payment_id' => null,
                'transaction_id' => 'WALLET-TXN-ORDER-' . $order->id,
                'gateway' => 'wallet',
                'mode' => 'wallet_only',
                'amount' => $order->wallet_used,
                'currency' => 'INR',
                'status' => 'success',
                'paid_at' => $order->paid_at,
            ],

            // SHIPPING
            'shipment_id' => $order->shipment_id,
            'awb_code' => $order->awb_code,
            'courier_name' => $order->courier_name,
            'shipping_status' => $order->shipping_status,

            // BOX
            'total_weight' => $order->total_weight,
            'box_length' => $order->box_length,
            'box_breadth' => $order->box_breadth,
            'box_height' => $order->box_height,

            // ADDRESS SNAPSHOT + CURRENT ADDRESS
            'address' => [
                'snapshot' => [
                    'name' => $order->name,
                    'email' => $order->email,
                    'mobile' => $order->mobile,
                    'alternative_mobile' => $order->alternative_mobile,
                    'state_code' => $order->state_code,
                    'state' => $order->state,
                    'city' => $order->city,
                    'country' => $order->country,
                    'address' => $order->address,
                    'pincode' => $order->pincode,
                ],
                'current' => $order->addressData ? [
                    'id' => $order->addressData->id,
                    'name' => $order->addressData->name,
                    'email' => $order->addressData->email,
                    'mobile' => $order->addressData->mobile,
                    'alternative_mobile' => $order->addressData->alternative_mobile,
                    'state_code' => $order->addressData->state_code,
                    'state' => $order->addressData->state,
                    'city' => $order->addressData->city,
                    'country' => $order->addressData->country,
                    'address' => $order->addressData->address,
                    'pincode' => $order->addressData->pincode,
                ] : null,
            ],

            // ITEMS
            'items' => $order->items->map(function ($item) {

                $product = $item->product;

                return [
                    'product_id' => $item->product_id,

                    // Snapshot data from the order item
                    'name' => $item->product_name,
                    'slug' => $item->product_slug,
                    'image' => $item->product_image
                        ? asset('storage/product/' . $item->product_image)
                        : null,

                    // Live product data where still available
                    'product' => $product ? [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'description' => $product->description,
                        'stock' => $product->stock_qty,
                        'status' => $product->stock_status,
                        'images' => $product->images->map(
                            fn ($img) => asset(
                                'storage/product/' . $img->images
                            )
                        ),
                    ] : null,

                    'ratti' => $item->ratti,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->total,

                    // GST must come from the order-item snapshot,
                    // not from the current product GST rate.
                    'gst_rate' => $item->gst_rate,
                    'gst_amount' => $item->gst_amount,
                    'taxable_amount' => $item->taxable_amount,
                    'cgst_amount' => $item->cgst_amount,
                    'sgst_amount' => $item->sgst_amount,
                    'igst_amount' => $item->igst_amount,
                    'tax_type' => $item->tax_type,
                    'hsn_code' => $item->hsn_code,

                    // Dimensions
                    'weight' => $item->weight,
                    'length' => $item->length,
                    'breadth' => $item->breadth,
                    'height' => $item->height,
                ];
            }),

            // COUPON
            'coupon' => $order->coupon ? [
                'id' => $order->coupon->id,
                'code' => $order->coupon->code,
                'type' => $order->coupon->discount_type,
                'value' => $order->coupon->discount_value,
            ] : null,

            // EXTRA
            'meta' => [
                'price_breakdown' => $order->price_breakdown,
            ],

            // TIMELINE
            'timestamps' => [
                'created_at' => $order->created_at,
                'paid_at' => $order->paid_at,
                'delivered_at' => $order->delivered_at,
                'cancelled_at' => $order->cancelled_at,
            ],
        ];
    }

    public function sendMail(Request $request, $id)
    {
        $request->validate([
            'awb_code' => 'required|min:5'
        ]);

        $order = Order::with('user')->findOrFail($id);

        $order->update([
            'awb_code' => trim($request->awb_code)
        ]);

        Mail::to($order->email ?? $order->user->email)
            ->send(new OrderDeliveryTrackMail($order));

        return back()->with(
            'success',
            'Tracking mail sent successfully.'
        );
    }
}
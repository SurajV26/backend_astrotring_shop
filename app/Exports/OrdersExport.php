<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrdersExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected string $fromDate,
        protected string $toDate,
        protected ?string $status = null,
        protected ?int $userId = null,
        protected ?int $categoryId = null,
        protected ?int $productId = null,
    ) {}

    public function collection()
    {
        $orders = Order::with([
            'user',
            'items.product.category',
            'coupon',
        ])
            ->whereBetween('created_at', [
                Carbon::parse($this->fromDate)->startOfDay(),
                Carbon::parse($this->toDate)->endOfDay(),
            ])

            ->when($this->status, function ($q) {
                $q->where('status', $this->status);
            })

            ->when($this->userId, function ($q) {
                $q->where('user_id', $this->userId);
            })

            ->when($this->categoryId, function ($q) {
                $q->whereHas('items.product', function ($q) {
                    $q->where('category_id', $this->categoryId);
                });
            })

            ->when($this->productId, function ($q) {
                $q->whereHas('items', function ($q) {
                    $q->where('product_id', $this->productId);
                });
            })

            ->when(auth()->user()?->type === 'employee', function ($q) {
                $q->whereHas('coupon', function ($q) {
                    $q->where('employee_id', auth()->id());
                });
            })

            ->latest()
            ->get();


        return $orders->map(function ($order) {

            /*
            |--------------------------------------------------------------------------
            | PRODUCT-WISE GST
            |--------------------------------------------------------------------------
            */

            $productTaxable = $order->items->sum(
                fn ($item) => (float) $item->taxable_amount
            );

            $productGst = $order->items->sum(
                fn ($item) => (float) $item->gst_amount
            );

            $productGstDetails = $order->items->map(function ($item) {

                $taxType = strtoupper($item->tax_type ?? 'GST');

                return sprintf(
                    '%s | Qty %s | ₹%s | Taxable ₹%s | GST %s%% | %s ₹%s',
                    $item->product_name,
                    $item->quantity,
                    number_format($item->price, 2),
                    number_format($item->taxable_amount, 2),
                    number_format($item->gst_rate, 2),
                    $taxType,
                    number_format($item->gst_amount, 2)
                );

            })->implode("\n");


            /*
            |--------------------------------------------------------------------------
            | GST RATES
            |--------------------------------------------------------------------------
            */

            $gstRates = $order->items
                ->pluck('gst_rate')
                ->map(fn ($rate) => number_format((float) $rate, 2) . '%')
                ->unique()
                ->implode(', ');


            /*
            |--------------------------------------------------------------------------
            | ORDER PRICE BREAKDOWN
            |--------------------------------------------------------------------------
            */

            $breakdown = is_array($order->price_breakdown)
                ? $order->price_breakdown
                : (json_decode($order->price_breakdown ?? '{}', true) ?? []);


            $shippingGst = (float) ($breakdown['shipping_gst_amount'] ?? 0);
            $codGst = (float) ($breakdown['cod_gst_amount'] ?? 0);

            $totalGst = $productGst + $shippingGst + $codGst;


            /*
            |--------------------------------------------------------------------------
            | PRODUCT LIST
            |--------------------------------------------------------------------------
            */

            $products = $order->items->map(function ($item) {
                return $item->quantity .
                    ' × ' .
                    $item->product_name .
                    ' — ₹' .
                    number_format((float) $item->total, 2);
            })->implode("\n");


            return [

                $order->order_number,

                $order->invoice_number,

                $order->user?->name ?? $order->name,

                $order->created_at
                    ? $order->created_at->format('d-m-Y H:i')
                    : '',

                $products,

                $productGstDetails,

                $gstRates,

                number_format($productTaxable, 2),

                number_format($productGst, 2),

                number_format($shippingGst, 2),

                number_format($codGst, 2),

                number_format($totalGst, 2),

                number_format((float) $order->subtotal, 2),

                number_format((float) $order->discount, 2),

                number_format((float) $order->delivery_charge, 2),

                number_format((float) $order->total_amount, 2),

                $order->status,
            ];
        });
    }


    public function headings(): array
    {
        return [
            'Order Number',
            'Invoice Number',
            'Customer',
            'Order Date',
            'Products',
            'Product-wise GST Details',
            'GST Rate',
            'Product Taxable Amount',
            'Product GST',
            'Shipping GST',
            'COD GST',
            'Total GST',
            'Subtotal',
            'Discount',
            'Delivery Charge',
            'Final Amount',
            'Status',
        ];
    }
}
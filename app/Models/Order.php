<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderThankYouMail;
use App\Mail\OrderDetailsMail;
use App\Mail\OrderDeliveredMail;
use App\Mail\OrderCancelledMail;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'address_id',
        'name',
        'email',
        'mobile',
        'alternative_mobile',
        'city',
        'state_code',
        'state',
        'country',
        'address',
        'pincode',

        'shipment_id',
        'awb_code',
        'courier_name',
        'shipping_status',

        'invoice_sequence',
        'invoice_number',
        'pdf',

        'coupon_id',
        'payment_id',
        'order_number',
        'hsn_code',

        'subtotal',
        'discount',
        'delivery_charge',

        'taxable_amount',
        'gst_rate',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'tax_type',

        'wallet_used',
        'advance_paid_amount',
        'remaining_cod_amount',
        'is_cod_advance',
        'paid_amount',
        'total_amount',

        'total_weight',
        'box_length',
        'box_breadth',
        'box_height',

        'status',

        'paid_at',
        'cancelled_at',
        'cancel_reason',
        'delivered_at',
        'rto_at',

        'price_breakdown',
    ];

    protected $casts = [

        // JSON
        'price_breakdown' => 'array',

        // MONEY
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'advance_paid_amount' => 'decimal:2',
        'remaining_cod_amount' => 'decimal:2',
        'wallet_used' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',

        // GST
        'taxable_amount' => 'decimal:2',
        'gst_rate' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',

        // COD
        'is_cod_advance' => 'boolean',

        // BOX
        'total_weight' => 'decimal:2',
        'box_length' => 'decimal:2',
        'box_breadth' => 'decimal:2',
        'box_height' => 'decimal:2',

        // DATES
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'delivered_at' => 'datetime',
        'rto_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function addressData()
    {
        return $this->belongsTo(
            AlternativeAddress::class,
            'address_id'
        );
    }

    public function walletTransactions()
    {
        return $this->hasMany(StoreWalletTransaction::class);
    }

    public function cancellations()
    {
        return $this->hasMany(OrderItemCancellation::class);
    }

    protected static function booted()
    {
        static::created(function ($order) {

            DB::afterCommit(function () use ($order) {

                try {

                    $order = $order->fresh()->load([
                        'user',
                        'items.product',
                        'payment'
                    ]);

                    if (
                        !$order->email &&
                        (!$order->user || !$order->user->email)
                    ) {
                        \Log::error('Order/User email missing');
                        return;
                    }

                    Mail::to(
                        $order->email ??
                        $order->user->email
                    )->send(
                        new OrderThankYouMail($order)
                    );

                    Mail::to(
                        $order->email ??
                        $order->user->email
                    )->send(
                        new OrderDetailsMail($order)
                    );

                } catch (\Exception $e) {

                    \Log::error('Order Mail Failed', [
                        'error' => $e->getMessage()
                    ]);
                }
            });
        });

        static::updated(function ($order) {

            DB::afterCommit(function () use ($order) {

                try {

                    $order = $order->fresh()->load([
                        'user',
                        'items.product',
                        'payment',
                        'walletTransactions'
                    ]);

                    if (
                        !$order->email &&
                        (!$order->user || !$order->user->email)
                    ) {
                        \Log::error('Order/User email missing');
                        return;
                    }

                    if ($order->status === 'delivered') {

                        if (!$order->delivered_at) {

                            $order->updateQuietly([
                                'delivered_at' => now()
                            ]);
                        }

                        Mail::to(
                            $order->email ??
                            $order->user->email
                        )->send(
                            new OrderDeliveredMail($order)
                        );

                        \Log::info('Delivered mail sent', [
                            'order_id' => $order->id
                        ]);
                    }

                    if ($order->status === 'cancelled') {

                        Mail::to(
                            $order->email ??
                            $order->user->email
                        )->send(
                            new OrderCancelledMail($order)
                        );

                        \Log::info('Cancel mail sent', [
                            'order_id' => $order->id
                        ]);
                    }

                } catch (\Exception $e) {

                    \Log::error('Order Mail Failed', [
                        'error' => $e->getMessage()
                    ]);
                }
            });
        });
    }
}
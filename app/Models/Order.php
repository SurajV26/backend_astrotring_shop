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
        'price_breakdown',
        'delivered_at'
    ];

    protected $casts = [

        // JSON
        'price_breakdown' => 'array',

        // MONEY
        'subtotal' => 'float',
        'discount' => 'float',
        'delivery_charge' => 'float',
        'advance_paid_amount' => 'float',
        'remaining_cod_amount' => 'float',
        'is_cod_advance' => 'boolean',
        'wallet_used' => 'float',
        'paid_amount' => 'float',
        'total_amount' => 'float',
        
        'taxable_amount' => 'float',
        'gst_rate' => 'float',
        'cgst_amount' => 'float',
        'sgst_amount' => 'float',
        'igst_amount' => 'float',
        

        // BOX
        'total_weight' => 'float',
        'box_length' => 'float',
        'box_breadth' => 'float',
        'box_height' => 'float',

        // DATES
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'delivered_at' => 'datetime',
        'rto_at'       => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ================= RELATIONS =================

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
        return $this->belongsTo(\App\Models\AlternativeAddress::class, 'address_id');
    }

    public function walletTransactions()
    {
        return $this->hasMany(StoreWalletTransaction::class);
    }

    public function cancellations()
    {
        return $this->hasMany(OrderItemCancellation::class);
    }

    // ================= EVENTS =================

    protected static function booted()
    {
        /**
         * âœ… ORDER CREATED â†’ MAILS
         */
        static::created(function ($order) {

            DB::afterCommit(function () use ($order) {

                try {

                    $order = $order->fresh()->load([
                        'user',
                        'items.product',
                        'payment'
                    ]);

                    if (!$order->email && (!$order->user || !$order->user->email)) {
                        \Log::error('Order/User email missing');
                        return;
                    }

                    // Thank you mail
                    Mail::to($order->email ?? $order->user->email)
                        ->send(new OrderThankYouMail($order));

                    // Order details mail
                    Mail::to($order->email ?? $order->user->email)
                        ->send(new OrderDetailsMail($order));

                } catch (\Exception $e) {
                    \Log::error('Order Mail Failed', [
                        'error' => $e->getMessage()
                    ]);
                }

            });

        });

        /**
         * âœ… ORDER UPDATED â†’ CANCEL / DELIVER MAIL
         */
        static::updated(function ($order) {

            DB::afterCommit(function () use ($order) {

                try {

                    $order = $order->fresh()->load([
                        'user',
                        'items.product',
                        'payment',
                        'walletTransactions'
                    ]);

                    if (!$order->email && (!$order->user || !$order->user->email)) {
                        \Log::error('Order/User email missing');
                        return;
                    }

                    // ðŸ”¥ DELIVERED MAIL
                    if ($order->status === 'delivered') {

                        // duplicate mail avoid
                        if (!$order->delivered_at) {
                            $order->updateQuietly([
                                'delivered_at' => now()
                            ]);
                        }

                        Mail::to($order->email ?? $order->user->email)
                            ->send(new OrderDeliveredMail($order));

                        \Log::info('Delivered mail sent', [
                            'order_id' => $order->id
                        ]);
                    }

                    // ðŸ”¥ CANCEL MAIL
                    if ($order->status === 'cancelled') {

                        Mail::to($order->email ?? $order->user->email)
                            ->send(new OrderCancelledMail($order));

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
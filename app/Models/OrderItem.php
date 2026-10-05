<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_slug',
        'product_image',
        'ratti',
        'quantity',
        'price',
        'total',
        'weight',
        'length',
        'breadth',
        'height',
        'gst_rate',
        'gst_amount',
        'taxable_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'tax_type',
        'hsn_code',
    ];

    protected $casts = [
        'ratti' => 'decimal:2',
        'quantity' => 'integer',

        'price' => 'decimal:2',
        'total' => 'decimal:2',

        'weight' => 'decimal:2',
        'length' => 'decimal:2',
        'breadth' => 'decimal:2',
        'height' => 'decimal:2',

        'gst_rate' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function returnRequest()
    {
        return $this->hasOne(ReturnRequest::class);
    }

    public function cancellations()
    {
        return $this->hasMany(OrderItemCancellation::class);
    }
}
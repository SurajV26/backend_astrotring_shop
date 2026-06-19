<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('address_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('alternative_mobile')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('state_code', 10)->nullable();
            $table->string('country')->default('India');
            $table->text('address')->nullable();
            $table->string('pincode')->nullable();
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('shipment_id')->nullable();
            $table->string('awb_code')->nullable();
            $table->string('courier_name')->nullable();
            $table->string('shipping_status')->default('pending');

            $table->unsignedBigInteger('invoice_sequence')->nullable()->unique();
            $table->string('invoice_number')->nullable()->unique();
            $table->string('pdf')->nullable();
            $table->string('order_number')->unique();
            $table->string('hsn_code')->nullable();

            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('delivery_charge', 10, 2)->default(0);

            // TAXES
            $table->decimal('taxable_amount', 10, 2)->default(0);
            $table->decimal('gst_rate', 5, 2)->default(0);
            $table->decimal('cgst_amount', 10, 2)->default(0);
            $table->decimal('sgst_amount', 10, 2)->default(0);
            $table->decimal('igst_amount', 10, 2)->default(0);
            $table->string('tax_type')->nullable();

            $table->decimal('wallet_used', 10, 2)->default(0);
            $table->decimal('advance_paid_amount', 10, 2)->default(0);
            $table->decimal('remaining_cod_amount', 10, 2)->default(0);
            $table->boolean('is_cod_advance')->default(false);
            $table->decimal('paid_amount', 10, 2)->default(0);

            $table->decimal('total_amount', 10, 2);

            $table->decimal('total_weight', 10, 2)->nullable();
            $table->decimal('box_length', 10, 2)->nullable();
            $table->decimal('box_breadth', 10, 2)->nullable();
            $table->decimal('box_height', 10, 2)->nullable();

            $table->enum('status', [
                'pending',
                'paid',
                'packed',
                'shipped',
                'delivered',
                'cancelled'
            ])->default('pending');

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->json('price_breakdown')->nullable();

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();

            $table->foreign('coupon_id')
                ->references('id')->on('coupons')
                ->nullOnDelete();

            $table->foreign('payment_id')
                ->references('id')->on('payments')
                ->nullOnDelete();

            $table->foreign('address_id')
                ->references('id')->on('alternative_addresses')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
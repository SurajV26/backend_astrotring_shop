<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adgebra_order_attributions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable()->unique();
            $table->unsignedBigInteger('visit_id')->nullable()->index();
            $table->uuid('tracking_id')->index();
            $table->string('razorpay_order_id', 100)->nullable()->unique();
            $table->timestamp('attributed_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')
                ->references('id')->on('orders')
                ->cascadeOnDelete();
            $table->foreign('visit_id')
                ->references('id')->on('adgebra_visits')
                ->nullOnDelete();
            $table->index(['created_at', 'order_id'], 'adgebra_attributions_date_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adgebra_order_attributions');
    }
};

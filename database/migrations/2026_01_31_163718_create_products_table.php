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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('code')->unique()->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('stone_name')->nullable();

            $table->json('ratti_options')->nullable();
            // example: [{"ratti":5,"ratti_afterPrice":1999,"ratti_beforePrice":2999},{"ratti":6,"ratti_afterPrice":2999,"ratti_beforePrice":3999}]

            $table->text('description')->nullable();
            $table->text('benefits')->nullable();
            $table->text('how_to_use')->nullable();
            $table->text('purity')->nullable();

            $table->json('specifications')->nullable();
            // example: [{"title":"Color","value":"Yellow"}]

            $table->json('faq')->nullable();
            // example: [{"question":"Who can wear?","answer":"Anyone with strong Jupiter"}]

            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            
            $table->decimal('before_price', 10, 2)->nullable();
            $table->decimal('after_price', 10, 2)->nullable();
            $table->string('hsn_code')->nullable()->default('7103');
            $table->decimal('gst_rate', 5, 2)->default(0.5);

            $table->text('shipping_info')->nullable();
            // Free shipping across India within 5–7 days.

            $table->string('origin')->nullable();
            // Example Sri Lanka, Burma, Africa

            $table->json('lab_certificates')->nullable();

            $table->string('planet')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();

            $table->decimal('weight', 8, 2)->nullable();
            $table->decimal('length', 8, 2)->nullable();  
            $table->decimal('breadth', 8, 2)->nullable(); 
            $table->decimal('height', 8, 2)->nullable();   

            $table->integer('stock_qty')->default(0);
            $table->enum('stock_status', ['in_stock', 'few_left', 'out_of_stock'])
                ->default('in_stock');
            $table->string('image')->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')
                ->references('id')->on('categories')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adgebra_clicks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visit_id');
            $table->uuid('tracking_id')->index();
            $table->uuid('visitor_key')->index();
            $table->uuid('event_id')->unique();
            $table->string('destination_path', 255)->nullable();
            $table->timestamps();

            $table->foreign('visit_id')
                ->references('id')->on('adgebra_visits')
                ->cascadeOnDelete();
            $table->index(['created_at', 'visit_id'], 'adgebra_clicks_date_visit_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adgebra_clicks');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adgebra_visits', function (Blueprint $table) {
            $table->id();
            $table->uuid('tracking_id')->unique();
            $table->uuid('event_id')->unique();
            $table->uuid('visitor_key')->index();
            $table->string('landing_page', 50)->index();
            $table->string('landing_path', 255);
            $table->string('source', 100)->default('Adgebra')->index();
            $table->string('medium', 100)->nullable();
            $table->string('campaign_id', 191)->nullable()->index();
            $table->string('placement_id', 191)->nullable()->index();
            $table->string('creative_id', 191)->nullable()->index();
            $table->string('click_id', 191)->nullable()->index();
            $table->string('referrer_host', 255)->nullable();
            $table->timestamps();

            $table->index(['created_at', 'landing_page'], 'adgebra_visits_date_page_idx');
            $table->index(['campaign_id', 'placement_id', 'creative_id'], 'adgebra_visits_campaign_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adgebra_visits');
    }
};

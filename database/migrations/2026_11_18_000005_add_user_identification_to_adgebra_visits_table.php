<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adgebra_visits', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('visitor_key');
            $table->timestamp('identified_at')->nullable()->after('user_id');
            $table->index(['user_id', 'identified_at'], 'adgebra_visits_user_identified_idx');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('adgebra_visits', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex('adgebra_visits_user_identified_idx');
            $table->dropColumn(['user_id', 'identified_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->unsignedInteger('admission_fee')->nullable()->default(null)->change();
            $table->string('source_provider', 40)->default('manual');
            $table->string('source_id', 100)->nullable()->unique();
            $table->string('source_url', 500)->nullable();
            $table->boolean('is_verified')->default(false)->index();
        });

        Schema::table('accommodations', function (Blueprint $table) {
            $table->unsignedInteger('price_per_night')->default(0)->change();
            $table->string('source_provider', 40)->default('manual');
            $table->string('source_id', 100)->nullable()->unique();
            $table->string('source_url', 500)->nullable();
            $table->boolean('is_verified')->default(false)->index();
            $table->timestamp('price_verified_at')->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('destinations')->whereNull('admission_fee')->exists()) {
            throw new RuntimeException('Resolve unknown admission fees before rolling back OpenStreetMap provenance.');
        }

        Schema::table('accommodations', function (Blueprint $table) {
            $table->dropUnique(['source_id']);
            $table->dropColumn(['source_provider', 'source_id', 'source_url', 'is_verified', 'price_verified_at']);
        });
        Schema::table('destinations', function (Blueprint $table) {
            $table->dropUnique(['source_id']);
            $table->dropColumn(['source_provider', 'source_id', 'source_url', 'is_verified']);
            $table->unsignedInteger('admission_fee')->default(0)->nullable(false)->change();
        });
    }
};

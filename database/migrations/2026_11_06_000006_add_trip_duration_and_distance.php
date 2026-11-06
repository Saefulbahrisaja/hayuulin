<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->decimal('total_distance_km', 8, 1)->default(0);
            $table->unsignedInteger('total_duration_minutes')->default(0);
            $table->string('transport_name')->default('Transportasi');
            $table->string('accommodation_name')->nullable();
        });

        Schema::table('trip_stops', function (Blueprint $table) {
            $table->decimal('leg_distance_km', 7, 1)->default(0);
            $table->unsignedSmallInteger('leg_minutes')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('trip_stops', function (Blueprint $table) {
            $table->dropColumn(['leg_distance_km', 'leg_minutes']);
        });
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['total_distance_km', 'total_duration_minutes', 'transport_name', 'accommodation_name']);
        });
    }
};

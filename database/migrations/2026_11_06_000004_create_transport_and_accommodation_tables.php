<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_options', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 30);
            $table->unsignedInteger('cost_per_km')->default(0);
            $table->unsignedSmallInteger('capacity')->default(4);
            $table->unsignedSmallInteger('average_speed_kmh')->default(35);
            $table->text('description');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('transport_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_option_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('origin_region');
            $table->string('destination_region');
            $table->unsignedInteger('fare')->default(0);
            $table->unsignedSmallInteger('duration_minutes')->default(0);
            $table->text('instructions');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['origin_region', 'destination_region']);
        });

        Schema::create('accommodations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 30);
            $table->string('region')->index();
            $table->string('address');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('price_per_night');
            $table->unsignedSmallInteger('capacity')->default(2);
            $table->decimal('rating', 2, 1)->default(0);
            $table->string('amenities')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('travel_tariffs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->unsignedInteger('amount');
            $table->string('unit', 40);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_tariffs');
        Schema::dropIfExists('accommodations');
        Schema::dropIfExists('transport_routes');
        Schema::dropIfExists('transport_options');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('origin_name');
            $table->decimal('origin_latitude', 10, 7);
            $table->decimal('origin_longitude', 10, 7);
            $table->unsignedTinyInteger('days');
            $table->unsignedSmallInteger('travelers');
            $table->unsignedInteger('budget_per_person');
            $table->unsignedInteger('total_cost');
            $table->json('cost_breakdown');
            $table->json('preferences');
            $table->timestamps();
        });

        Schema::create('trip_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('destination_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('day');
            $table->unsignedTinyInteger('sequence');
            $table->string('destination_name');
            $table->string('region');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('admission_fee')->default(0);
            $table->unsignedSmallInteger('visit_duration_minutes')->default(0);
            $table->decimal('rating', 2, 1)->default(0);
            $table->timestamps();
            $table->index(['trip_id', 'day', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_stops');
        Schema::dropIfExists('trips');
    }
};

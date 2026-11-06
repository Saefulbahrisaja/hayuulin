<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->unsignedInteger('actual_cost')->nullable()->after('total_cost');
            $table->date('start_date')->nullable()->after('preferences');
        });

        Schema::create('destination_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->string('sentiment', 16);
            $table->decimal('sentiment_confidence', 5, 4);
            $table->timestamps();
            $table->unique(['destination_id', 'user_id']);
            $table->index(['destination_id', 'sentiment']);
        });

        Schema::create('destination_visit_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('visited_on');
            $table->unsignedInteger('visitor_count');
            $table->boolean('is_holiday')->default(false);
            $table->string('source', 160);
            $table->timestamps();
            $table->unique(['destination_id', 'visited_on']);
            $table->index(['destination_id', 'visited_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destination_visit_metrics');
        Schema::dropIfExists('destination_reviews');
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['actual_cost', 'start_date']);
        });
    }
};

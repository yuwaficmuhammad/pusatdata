<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('active_day', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_version_id')->constrained('schedule_version')->onDelete('cascade');
            $table->tinyInteger('day_of_week'); // 1=Monday ... 7=Sunday
            $table->boolean('is_holiday')->default(0);
            $table->timestamps();
            
            $table->unique(['schedule_version_id', 'day_of_week'], 'idx_active_day_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('active_day');
    }
};

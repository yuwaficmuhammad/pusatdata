<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_version_id')->constrained('schedule_version')->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained('classroom')->onDelete('cascade');
            $table->foreignId('active_day_id')->constrained('active_day')->onDelete('cascade');
            $table->foreignId('time_slot_id')->constrained('time_slot')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subject')->onDelete('restrict');
            $table->foreignId('teacher_id')->constrained('teacher')->onDelete('restrict');
            $table->timestamps();
            
            $table->unique(['schedule_version_id', 'classroom_id', 'active_day_id', 'time_slot_id'], 'idx_schedule_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule');
    }
};

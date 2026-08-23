<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_calendar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained('semester')->onDelete('cascade');
            $table->date('date')->unique();
            $table->enum('type', ['holiday', 'exam', 'activity'])->index();
            $table->string('description', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_calendar');
    }
};

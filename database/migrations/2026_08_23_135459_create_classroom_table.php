<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classroom', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->tinyInteger('grade');
            $table->foreignId('homeroom_teacher_id')->constrained('teacher')->onDelete('restrict');
            $table->foreignId('academic_year_id')->constrained('academic_year')->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom');
    }
};

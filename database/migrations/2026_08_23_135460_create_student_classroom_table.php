<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_classroom', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained('classroom')->onDelete('cascade');
            $table->date('joined_at');
            $table->date('left_at')->nullable();
            $table->enum('mutation_reason', ['active', 'grade_up', 'transferred', 'graduated']);
            $table->timestamps();
            
            $table->unique(['student_id', 'classroom_id', 'joined_at'], 'idx_student_classroom_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_classroom');
    }
};

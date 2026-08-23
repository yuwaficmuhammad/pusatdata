<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user')->onDelete('cascade');
            $table->string('nis', 20)->unique();
            $table->string('nisn', 10)->unique();
            $table->string('name', 100);
            $table->enum('gender', ['L', 'P']);
            $table->string('birth_place', 100);
            $table->date('birth_date');
            $table->string('religion', 30);
            $table->text('address');
            $table->string('phone', 20);
            $table->string('parent_name', 100);
            $table->string('parent_phone', 20)->index();
            $table->enum('status', ['active', 'graduated', 'transferred', 'dropped'])->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student');
    }
};

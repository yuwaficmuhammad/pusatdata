<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');
            $table->foreignId('schedule_version_id')->nullable()->constrained('schedule_version')->onDelete('set null');
            $table->date('date')->index();
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->enum('status', ['hadir', 'terlambat', 'izin', 'sakit', 'alpha'])->default('alpha')->index();
            $table->integer('late_minutes')->default(0);
            $table->string('device_sn', 50)->nullable(); // ADMS device SN
            $table->timestamps();
            
            $table->unique(['student_id', 'date'], 'idx_student_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};

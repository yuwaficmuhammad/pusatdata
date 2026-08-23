<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unknown_attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('device_sn', 50)->nullable();
            $table->string('nis_scanned', 50);
            $table->date('date');
            $table->time('time');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unknown_attendance_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_slot', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20); // Jam ke-1, Istirahat, dll
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_break')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_slot');
    }
};

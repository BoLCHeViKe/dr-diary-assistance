<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hc', function (Blueprint $table) {
            $table->id('nhc');
            $table->date('fecha_apert')->default('1900-01-01');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hc');
    }
};
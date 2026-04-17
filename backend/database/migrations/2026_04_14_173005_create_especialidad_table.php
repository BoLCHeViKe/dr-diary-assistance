<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('especialidad', function (Blueprint $table) {
            $table->char('codigo_esp', 4);
            $table->string('nombre', 30)->unique();

            $table->primary('codigo_esp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('especialidad');
    }
};
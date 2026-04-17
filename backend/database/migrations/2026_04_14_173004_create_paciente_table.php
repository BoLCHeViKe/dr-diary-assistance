<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paciente', function (Blueprint $table) {
            $table->id('id_paciente');
            $table->char('dni', 9)->unique();
            $table->string('nombre', 30);
            $table->string('apellido1', 30);
            $table->string('apellido2', 30);
            $table->date('fecha_nac')->nullable();
            $table->string('telf', 13)->nullable();
            $table->unsignedBigInteger('nhc');

            $table->foreign('nhc')
                  ->references('nhc')
                  ->on('hc')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paciente');
    }
};
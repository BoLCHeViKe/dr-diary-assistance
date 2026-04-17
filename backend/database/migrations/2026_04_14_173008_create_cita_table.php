<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cita', function (Blueprint $table) {
            $table->unsignedBigInteger('id_agenda');
            $table->id('id_cita');
            $table->time('h_cita');
            $table->enum('estado', ['citado', 'en espera', 'validado', 'facturado'])->default('citado');
            $table->char('codigo_esp', 4);
            $table->integer('id_prest');
            $table->unsignedBigInteger('id_paciente');

            $table->unique(['id_agenda', 'h_cita']);
            $table->foreign('id_agenda')
                  ->references('id_agenda')
                  ->on('agenda')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
            $table->foreign(['codigo_esp', 'id_prest'])
                  ->references(['codigo_esp', 'id_prest'])
                  ->on('prestacion')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            $table->foreign('id_paciente')
                  ->references('id_paciente')
                  ->on('paciente')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cita');
    }
};
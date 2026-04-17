<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factura', function (Blueprint $table) {
            $table->id('num_fact');
            $table->date('fecha')->default('1900-01-01');
            $table->enum('estado', ['borrador', 'emitida', 'anulada', 'abono'])->default('borrador');
            $table->unsignedBigInteger('id_paciente');
            $table->unsignedBigInteger('fact_ref')->nullable();

            $table->foreign('id_paciente')
                  ->references('id_paciente')
                  ->on('paciente')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            // CONSTRAINT fac_ref_fk (Relación recursiva para abonos)
            $table->foreign('fact_ref')
                  ->references('num_fact')
                  ->on('factura')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
            // Nota: No añadimos UNIQUE en fact_ref para permitir abonos parciales
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factura');
    }
};
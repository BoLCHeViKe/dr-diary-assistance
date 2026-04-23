<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detallehc', function (Blueprint $table) {
            $table->integer('num_orden');
            $table->unsignedBigInteger('nhc');
            $table->unsignedBigInteger('id_cita')->unique()->nullable();
            $table->string('tto', 60);
            $table->date('f_consulta')->default('1900-01-01');
            $table->text('sinto')->nullable();
            $table->string('diag', 80)->nullable();

            $table->primary(['num_orden', 'nhc']);
            $table->foreign('nhc')
                  ->references('nhc')
                  ->on('hc')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('id_cita')->references('id_cita')->on('cita')->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detallehc');
    }
};
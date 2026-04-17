<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lineafactura', function (Blueprint $table) {
            $table->integer('num_linea');
            $table->unsignedBigInteger('num_fact');
            $table->integer('cantidad')->default(1);
            $table->char('codigo_esp', 4);
            $table->integer('id_prest');
            $table->decimal('precio', 10, 2);
            $table->decimal('total', 10, 2);

            $table->primary(['num_linea', 'num_fact']);
            $table->foreign('num_fact')
                  ->references('num_fact')
                  ->on('factura')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            $table->foreign(['codigo_esp', 'id_prest'])
                  ->references(['codigo_esp', 'id_prest'])
                  ->on('prestacion')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lineafactura');
    }
};
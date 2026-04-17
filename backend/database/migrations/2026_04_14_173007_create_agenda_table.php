<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda', function (Blueprint $table) {
            $table->id('id_agenda');
            $table->date('fecha');
            $table->time('h_inicio');
            $table->time('h_fin');
            $table->integer('min_intervalo')->nullable();
            $table->unsignedBigInteger('id_med');

            $table->foreign('id_med')
                  ->references('id')
                  ->on('medico')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda');
    }
};
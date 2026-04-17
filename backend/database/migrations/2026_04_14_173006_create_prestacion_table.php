<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestacion', function (Blueprint $table) {
            $table->char('codigo_esp', 4);
            $table->integer('id_prest');
            $table->string('nombre', 30);
            $table->string('descripcion', 80)->nullable();
            $table->decimal('precio', 10, 2);

            $table->primary(['codigo_esp', 'id_prest']);
            $table->foreign('codigo_esp')
                  ->references('codigo_esp')
                  ->on('especialidad')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestacion');
    }
};
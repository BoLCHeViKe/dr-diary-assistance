<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usuario', function (Blueprint $table) {
            $table->id();
            $table->string('email', 70)->unique();
            $table->string('pass', 70);
            $table->char('dni', 9)->unique();
            $table->string('nombre', 30);
            $table->string('apellido1', 30);
            $table->string('apellido2', 30);
            $table->unsignedBigInteger('id_rol')->default(2);

            $table->foreign('id_rol')
                ->references('id')
                ->on('rol')
                ->onDelete('restrict')
                ->onUpdate('cascade');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario');
    }
};

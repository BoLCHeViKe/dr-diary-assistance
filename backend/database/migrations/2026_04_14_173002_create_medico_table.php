<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medico', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->string('num_col', 10)->unique()->nullable();

            $table->primary('id');
            $table->foreign('id')
                  ->references('id')
                  ->on('usuario')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medico');
    }
};
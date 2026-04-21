<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Este será el ID que hereden admins y medicos
            
            // Campos de identificación y login
            $table->string('email', 70)->unique();
            $table->string('password'); // Laravel usa 'password', mejor mantener el nombre estándar
            $table->char('dni', 9)->unique();
            
            // Campos de nombre completo
            $table->string('nombre', 30);
            $table->string('apellido1', 30);
            $table->string('apellido2', 30);

            //Datos adicionales
            $table->date('fecha_nac')->nullable();
            $table->string('telf', 13)->nullable();
            $table->string('direccion', 100)->nullable();
            
            // Rol (Relación con tu tabla rol)
            $table->unsignedBigInteger('id_rol')->default(2);
            $table->foreign('id_rol')
                  ->references('id')
                  ->on('rol')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');

            // Campos nativos de Laravel para seguridad y sesiones
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps(); // Esto añade created_at y updated_at automáticamente
        });

        // Las tablas de sistema las dejamos igual para que Laravel funcione bien
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
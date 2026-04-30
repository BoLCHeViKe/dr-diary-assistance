<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('id_rol');
        });

        Schema::table('especialidad', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('nombre');
        });

        Schema::table('prestacion', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('precio');
        });
    }

    public function down(): void
    {
        Schema::table('users',       fn($t) => $t->dropColumn('activo'));
        Schema::table('especialidad', fn($t) => $t->dropColumn('activo'));
        Schema::table('prestacion',   fn($t) => $t->dropColumn('activo'));
    }
};

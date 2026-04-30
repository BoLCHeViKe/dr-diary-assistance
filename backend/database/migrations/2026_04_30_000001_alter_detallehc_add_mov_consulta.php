<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detallehc', function (Blueprint $table) {
            $table->string('mov_consulta', 32)->nullable()->after('id_cita');
        });
    }

    public function down(): void
    {
        Schema::table('detallehc', function (Blueprint $table) {
            $table->dropColumn('mov_consulta');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Expand enum to include BOTH values (allows the UPDATE to succeed)
        DB::statement("ALTER TABLE cita MODIFY COLUMN estado ENUM('citado','en espera','validado','atendido','facturado') NOT NULL DEFAULT 'citado'");

        // 2. Rename existing 'validado' rows to 'atendido'
        DB::statement("UPDATE cita SET estado = 'atendido' WHERE estado = 'validado'");

        // 3. Remove 'validado' from the enum now that no rows use it
        DB::statement("ALTER TABLE cita MODIFY COLUMN estado ENUM('citado','en espera','atendido','facturado') NOT NULL DEFAULT 'citado'");

        // 4. Add nullable FK column linking a billed cita to its invoice
        Schema::table('cita', function (Blueprint $table) {
            $table->unsignedBigInteger('num_fact')->nullable()->after('id_paciente');
            $table->foreign('num_fact')
                  ->references('num_fact')
                  ->on('factura')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('cita', function (Blueprint $table) {
            $table->dropForeign(['num_fact']);
            $table->dropColumn('num_fact');
        });

        DB::statement("ALTER TABLE cita MODIFY COLUMN estado ENUM('citado','en espera','atendido','validado','facturado') NOT NULL DEFAULT 'citado'");
        DB::statement("UPDATE cita SET estado = 'validado' WHERE estado = 'atendido'");
        DB::statement("ALTER TABLE cita MODIFY COLUMN estado ENUM('citado','en espera','validado','facturado') NOT NULL DEFAULT 'citado'");
    }
};

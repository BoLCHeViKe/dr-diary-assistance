<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rol', function (Blueprint $table) {
            $table->boolean('perm_agenda')              ->default(false)->after('tipo');
            $table->boolean('perm_hc')                  ->default(false)->after('perm_agenda');
            $table->boolean('perm_multi_agenda')         ->default(false)->after('perm_hc');
            $table->boolean('perm_facturacion')          ->default(false)->after('perm_multi_agenda');
            $table->boolean('perm_estadisticas')         ->default(false)->after('perm_facturacion');
            $table->boolean('perm_gest_roles')           ->default(false)->after('perm_estadisticas');
            $table->boolean('perm_gest_usuarios')        ->default(false)->after('perm_gest_roles');
            $table->boolean('perm_gest_prestaciones')    ->default(false)->after('perm_gest_usuarios');
            $table->boolean('perm_gest_especialidades')  ->default(false)->after('perm_gest_prestaciones');
        });

        // ADMIN: acceso total
        DB::table('rol')->where('id', 1)->update([
            'tipo'                   => 'ADMIN',
            'perm_agenda'            => true,
            'perm_hc'                => true,
            'perm_multi_agenda'      => true,
            'perm_facturacion'       => true,
            'perm_estadisticas'      => true,
            'perm_gest_roles'        => true,
            'perm_gest_usuarios'     => true,
            'perm_gest_prestaciones' => true,
            'perm_gest_especialidades' => true,
        ]);

        // MÉDICO: agenda + hc + facturación; sin estadísticas, sin gestión
        DB::table('rol')->where('id', 2)->update([
            'tipo'          => 'MEDICO',
            'perm_agenda'   => true,
            'perm_hc'       => true,
            'perm_facturacion' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('rol', function (Blueprint $table) {
            $table->dropColumn([
                'perm_agenda', 'perm_hc', 'perm_multi_agenda',
                'perm_facturacion', 'perm_estadisticas',
                'perm_gest_roles', 'perm_gest_usuarios',
                'perm_gest_prestaciones', 'perm_gest_especialidades',
            ]);
        });

        DB::table('rol')->where('id', 1)->update(['tipo' => 'admin']);
        DB::table('rol')->where('id', 2)->update(['tipo' => 'default']);
    }
};

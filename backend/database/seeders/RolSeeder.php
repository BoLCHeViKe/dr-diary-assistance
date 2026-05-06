<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('rol')->insertOrIgnore([
            'id'                       => 1,
            'tipo'                     => 'ADMIN',
            'perm_agenda'              => true,
            'perm_agenda_disponible'   => true,
            'perm_hc'                  => true,
            'perm_multi_agenda'        => true,
            'perm_facturacion'         => true,
            'perm_estadisticas'        => true,
            'perm_gest_roles'          => true,
            'perm_gest_usuarios'       => true,
            'perm_gest_prestaciones'   => true,
            'perm_gest_especialidades' => true,
        ]);

        DB::table('rol')->insertOrIgnore([
            'id'                     => 2,
            'tipo'                   => 'MEDICO',
            'perm_agenda'            => true,
            'perm_agenda_disponible' => true,
            'perm_hc'                => true,
            'perm_facturacion'       => true,
        ]);
    }
}

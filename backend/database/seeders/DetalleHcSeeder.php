<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DetalleHcSeeder extends Seeder
{
    public function run(): void
    {
        $primerNhc = DB::table('hc')->first()->nhc;

        // Cogemos las citas existentes para enlazarlas
        $cita1 = DB::table('cita')->where('id_paciente', 1)->first();
        $cita2 = DB::table('cita')->where('id_paciente', 2)->first();

        DB::table('detallehc')->insert([
            [
                'nhc'          => $primerNhc,
                'id_cita'      => $cita1->id_cita ?? null,
                'mov_consulta' => 'Revisión anual',
                'f_consulta'   => '2025-01-20',
                'sinto'        => 'Fatiga y cansancio',
                'diag'         => 'HTA inicial',
                'tto'          => 'Dieta hiposódica',
            ],
            [
                'nhc'          => $primerNhc + 1,
                'id_cita'      => $cita2->id_cita ?? null,
                'mov_consulta' => 'Primera consulta dermatológica',
                'f_consulta'   => '2025-01-22',
                'sinto'        => 'Mancha en región dorsal',
                'diag'         => 'Nevus benigno',
                'tto'          => 'Revisión en 6 meses',
            ],
        ]);
    }
}
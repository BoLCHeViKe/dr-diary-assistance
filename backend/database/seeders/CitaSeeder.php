<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('cita')->insert([
            [
                'id_agenda' => 1,
                'h_cita' => '09:00:00',
                'estado' => 'validado',
                'codigo_esp' => 'CARD',
                'id_prest' => 1,
                'id_paciente' => 1
            ],
            [
                'id_agenda' => 1,
                'h_cita' => '09:30:00',
                'estado' => 'validado',
                'codigo_esp' => 'CARD',
                'id_prest' => 2,
                'id_paciente' => 2
            ],
        ]);
    }
}
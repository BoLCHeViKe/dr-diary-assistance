<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FacturaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('factura')->insert([
            // Todas en borrador para que el LineaFacturaSeeder pueda insertar
            ['num_fact' => 1, 'id_paciente' => 1, 'fecha' => '2025-10-25', 'estado' => 'borrador', 'fact_ref' => null],
            ['num_fact' => 2, 'id_paciente' => 2, 'fecha' => '2025-10-25', 'estado' => 'borrador', 'fact_ref' => null],
            ['num_fact' => 6, 'id_paciente' => 4, 'fecha' => '2025-10-27', 'estado' => 'borrador', 'fact_ref' => null],
        ]);
    }
}
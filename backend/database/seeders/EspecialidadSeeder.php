<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EspecialidadSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('especialidad')->insert([
            ['codigo_esp' => 'CARD', 'nombre' => 'Cardiología'],
            ['codigo_esp' => 'DERM', 'nombre' => 'Dermatología'],
            ['codigo_esp' => 'TRAU', 'nombre' => 'Traumatología'],
            ['codigo_esp' => 'GENE', 'nombre' => 'Medicina General'],
            ['codigo_esp' => 'PEDI', 'nombre' => 'Pediatría'],
        ]);
    }
}

<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrestacionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('prestacion')->insertOrIgnore([
            ['codigo_esp' => 'CARD', 'id_prest' => 1, 'nombre' => 'Consulta Cardíaca', 'precio' => 95.00],
            ['codigo_esp' => 'CARD', 'id_prest' => 2, 'nombre' => 'Electrocardiograma', 'precio' => 40.00],
            ['codigo_esp' => 'CARD', 'id_prest' => 3, 'nombre' => 'Prueba de Esfuerzo', 'precio' => 180.00],
            ['codigo_esp' => 'DERM', 'id_prest' => 1, 'nombre' => 'Consulta Dermatología', 'precio' => 80.00],
            ['codigo_esp' => 'DERM', 'id_prest' => 2, 'nombre' => 'Crioterapia', 'precio' => 60.00],
            ['codigo_esp' => 'DERM', 'id_prest' => 3, 'nombre' => 'Extirpación de Quiste', 'precio' => 250.00],
            ['codigo_esp' => 'TRAU', 'id_prest' => 1, 'nombre' => 'Consulta Traumatología', 'precio' => 85.00],
            ['codigo_esp' => 'TRAU', 'id_prest' => 2, 'nombre' => 'Infiltración', 'precio' => 70.00],
            ['codigo_esp' => 'TRAU', 'id_prest' => 3, 'nombre' => 'Revisión Postoperatoria', 'precio' => 50.00],
            ['codigo_esp' => 'GENE', 'id_prest' => 1, 'nombre' => 'Consulta General', 'precio' => 50.00],
            ['codigo_esp' => 'GENE', 'id_prest' => 2, 'nombre' => 'Certificado Médico', 'precio' => 30.00],
            ['codigo_esp' => 'PEDI', 'id_prest' => 1, 'nombre' => 'Consulta Pediatría', 'precio' => 60.00],
            ['codigo_esp' => 'PEDI', 'id_prest' => 2, 'nombre' => 'Vacunación', 'precio' => 20.00],
        ]);
    }
}
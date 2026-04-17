<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DetalleHcSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('detallehc')->insert([
            [
                'nhc' => 2000, 
                'f_consulta' => '2025-01-20', 
                'sinto' => 'Fatiga y cansancio', 
                'diag' => 'HTA inicial', 
                'tto' => 'Dieta hiposódica'
            ],
            [
                'nhc' => 2001, 
                'f_consulta' => '2025-01-22', 
                'sinto' => 'Mancha en región dorsal', 
                'diag' => 'Nevus benigno', 
                'tto' => 'Revisión en 6 meses'
            ],
        ]);
    }
}
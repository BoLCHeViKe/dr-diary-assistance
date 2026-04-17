<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LineaFacturaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('lineafactura')->insert([
            [
                'num_fact' => 1, 
                'cantidad' => 1, 
                'codigo_esp' => 'CARD', 
                'id_prest' => 1,
                'precio' => 0, 'total' => 0 
            ],
            [
                'num_fact' => 2, 
                'cantidad' => 1, 
                'codigo_esp' => 'CARD', 
                'id_prest' => 2,
                'precio' => 0, 'total' => 0
            ],
            [
                'num_fact' => 6, 
                'cantidad' => 1, 
                'codigo_esp' => 'DERM', 
                'id_prest' => 3,
                'precio' => 0, 'total' => 0
            ],
        ]);
    }
}
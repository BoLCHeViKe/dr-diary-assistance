<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HcSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 0; $i <5; $i++) { //Tenemos moqueados 5 pacientes, pues 5 HC
            DB::table('hc')->insertOrIgnore([
                'nhc' => 1000 + $i,
                'fecha_apert' => '2024-01-01'
                //'fecha_apert' => now()->toDateString()
            ]);
        }
    }
}
<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HcSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 0; $i <= 14; $i++) {
            DB::table('hc')->insert([
                'nhc' => 2000 + $i,
                'fecha_apert' => '2024-01-01'
            ]);
        }
    }
}
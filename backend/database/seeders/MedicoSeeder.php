<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicoSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 2; $i <= 15; $i++) {
            DB::table('medico')->insertOrIgnore([
                'id' => $i,
                'num_col' => 'CM' . str_pad($i, 7, '0', STR_PAD_LEFT)
            ]);
        }
    }
}
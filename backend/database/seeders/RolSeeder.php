<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('rol')->insert([
            ['id' => 1, 'tipo' => 'admin'],
            ['id' => 2, 'tipo' => 'default'],
        ]);
    }
}
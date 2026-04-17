<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgendaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('agenda')->insert([
            [
                'id_agenda' => 1,
                'fecha' => '2025-10-25',
                'h_inicio' => '09:00:00',
                'h_fin' => '14:00:00',
                'min_intervalo' => 30,
                'id_med' => 2
            ],
            [
                'id_agenda' => 2,
                'fecha' => '2025-10-25',
                'h_inicio' => '16:00:00',
                'h_fin' => '20:00:00',
                'min_intervalo' => 30,
                'id_med' => 3
            ],
        ]);
    }
}
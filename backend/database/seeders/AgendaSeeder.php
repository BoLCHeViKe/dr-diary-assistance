<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Agenda;
use App\Models\Medico; // Importamos Medico
use Carbon\Carbon;

class AgendaSeeder extends Seeder
{
    public function run()
    {
        // Buscamos el primer médico que exista en la tabla medicos
        $medico = Medico::first();

        if (!$medico) {
            $this->command->error("No hay médicos en la base de datos. ¡Asegúrate de que el MedicoSeeder se ejecute antes!");
            return;
        }

        // Agenda para HOY
        Agenda::create([
            'id_med'        => $medico->id, // Usamos su ID real (probablemente sea el 2)
            'fecha'         => Carbon::today()->format('Y-m-d'),
            'h_inicio'      => '09:00',
            'h_fin'         => '13:00',
            'min_intervalo' => 30,
        ]);

        // Agenda para MAÑANA
        Agenda::create([
            'id_med'        => $medico->id,
            'fecha'         => Carbon::tomorrow()->format('Y-m-d'),
            'h_inicio'      => '16:00',
            'h_fin'         => '20:00',
            'min_intervalo' => 20,
        ]);
    }
}
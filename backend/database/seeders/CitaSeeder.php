<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cita;
use App\Models\Agenda;

class CitaSeeder extends Seeder
{
    public function run()
    {
        $agendaHoy = Agenda::where('fecha', now()->format('Y-m-d'))->first();

        if (!$agendaHoy) {
            $this->command->error("No hay agenda para hoy. Ejecuta AgendaSeeder primero.");
            return;
        }

        Cita::create([
            'id_agenda'   => $agendaHoy->id_agenda,
            'id_paciente' => 1,
            'codigo_esp'  => 'CARD',
            'id_prest'    => 1,
            'h_cita'      => '09:00',
            'estado'      => 'validado',
        ]);

        Cita::create([
            'id_agenda'   => $agendaHoy->id_agenda,
            'id_paciente' => 2,
            'codigo_esp'  => 'CARD',
            'id_prest'    => 2,
            'h_cita'      => '09:30',
            'estado'      => 'en espera',
        ]);

        Cita::create([
            'id_agenda'   => $agendaHoy->id_agenda,
            'id_paciente' => 3,
            'codigo_esp'  => 'CARD',
            'id_prest'    => 1,
            'h_cita'      => '10:30',
            'estado'      => 'citado',
        ]);
    }
}
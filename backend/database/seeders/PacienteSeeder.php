<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PacienteSeeder extends Seeder
{
    public function run(): void
    {
        $pacientes = [
            [1, '70000000A', 'Javier', 'Fernández', 'Ruiz', '1985-05-10', '600111222','javier.fernandez@mail.com', 2000],
            [2, '70000001B', 'María', 'López', 'García', '1992-08-22', '600222333', 'maria.fernandez@mail.com',2001],
            [3, '70000002C', 'Carlos', 'Martínez', 'Sanz', '1978-03-15', '600333444','carlos.martinez@mail.com', 2002],
            [4, '70000003D', 'Sofía', 'Moreno', 'Pérez', '2000-11-05', '600444555','sofia.moreno@mail.com', 2003],
            [5, '70000004E', 'Diego', 'Rodríguez', 'Lara', '1965-12-30', '600555666', 'diego.rodriguez@mail.com', 2004],
        ];

        foreach ($pacientes as $p) {
            DB::table('paciente')->insert([
                'id_paciente' => $p[0],
                'dni' => $p[1],
                'nombre' => $p[2],
                'apellido1' => $p[3],
                'apellido2' => $p[4],
                'fecha_nac' => $p[5],
                'telf' => $p[6],
                'email' => $p[7],
                'nhc' => $p[8]
            ]);
        }
    }
}
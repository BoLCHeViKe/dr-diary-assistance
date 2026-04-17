<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            [1, 'administracion@drdiary.es', 'adminpass', '11111111A', 'Ana', 'Gómez', 'Santos', 1],
            [2, 'dr.rodriguez@drdiary.es', 'medpass', '22222222B', 'Ricardo', 'Rodríguez', 'Navarro', 2],
            [3, 'dra.sanchez@drdiary.es', 'medpass', '33333333C', 'Marta', 'Sánchez', 'Pérez', 2],
            [4, 'dr.lopez@drdiary.es', 'medpass', '44444444D', 'David', 'López', 'Martín', 2],
            [5, 'dra.herrero@drdiary.es', 'medpass', '55555555E', 'Elena', 'Herrero', 'Garrido', 2],
            [6, 'dr.vazquez@drdiary.es', 'medpass', '66666666F', 'Pablo', 'Vázquez', 'Belmonte', 2],
            [7, 'dr.marin@drdiary.es', 'medpass', '77777777G', 'Jorge', 'Marín', 'Soler', 2],
            [8, 'dra.blanco@drdiary.es', 'medpass', '88888888H', 'Lucía', 'Blanco', 'Casas', 2],
            [9, 'dr.castro@drdiary.es', 'medpass', '99999999I', 'Antón', 'Castro', 'Peña', 2],
            [10, 'dra.ruiz@drdiary.es', 'medpass', '10101010J', 'Sofía', 'Ruiz', 'Sosa', 2],
            [11, 'dr.ortega@drdiary.es', 'medpass', '12121212K', 'Luis', 'Ortega', 'Lara', 2],
            [12, 'dra.jimenez@drdiary.es', 'medpass', '13131313L', 'Alba', 'Jiménez', 'Pons', 2],
            [13, 'dr.navarro@drdiary.es', 'medpass', '14141414M', 'Igor', 'Navarro', 'Vila', 2],
            [14, 'dra.mora@drdiary.es', 'medpass', '15151515N', 'Clara', 'Mora', 'Duarte', 2],
            [15, 'dr.pascual@drdiary.es', 'medpass', '16161616O', 'Hugo', 'Pascual', 'Roca', 2],
        ];

        foreach ($usuarios as $u) {
            DB::table('users')->insert([
                'id' => $u[0],
                'email' => $u[1],
                'password' => bcrypt($u[2]), // Ojo, esto encrypta
                'dni' => $u[3],
                'nombre' => $u[4],
                'apellido1' => $u[5],
                'apellido2' => $u[6],
                'id_rol' => $u[7],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
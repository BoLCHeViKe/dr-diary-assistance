<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            // [ID, Email, Pass, DNI, Nombre, Apellido1, Apellido2, Fecha_Nac, Telf, Direccion, ID_Rol]
            [1, 'administracion@drdiary.es', 'adminpass', '11111111A', 'Ana', 'Gómez', 'Santos', '1985-05-12', '600111222', 'Calle Mayor 1, Madrid', 1],
            [2, 'dr.rodriguez@drdiary.es', 'medpass', '22222222B', 'Ricardo', 'Rodríguez', 'Navarro', '1975-10-20', '600222333', 'Av. Libertad 15, Madrid', 2],
            [3, 'dra.sanchez@drdiary.es', 'medpass', '33333333C', 'Marta', 'Sánchez', 'Pérez', '1982-03-14', '600333444', 'Plaza España 5, Madrid', 2],
            [4, 'dr.lopez@drdiary.es', 'medpass', '44444444D', 'David', 'López', 'Martín', '1978-11-30', '600444555', 'Calle Roble 12, Madrid', 2],
            [5, 'dra.herrero@drdiary.es', 'medpass', '55555555E', 'Elena', 'Herrero', 'Garrido', '1988-07-22', '600555666', 'Calle Pez 45, Madrid', 2],
            [6, 'dr.vazquez@drdiary.es', 'medpass', '66666666F', 'Pablo', 'Vázquez', 'Belmonte', '1980-01-05', '600666777', 'Av. Complutense 3, Madrid', 2],
            [7, 'dr.marin@drdiary.es', 'medpass', '77777777G', 'Jorge', 'Marín', 'Soler', '1972-09-18', '600777888', 'Calle Luna 8, Madrid', 2],
            [8, 'dra.blanco@drdiary.es', 'medpass', '88888888H', 'Lucía', 'Blanco', 'Casas', '1990-12-01', '600888999', 'Calle Sol 22, Madrid', 2],
            [9, 'dr.castro@drdiary.es', 'medpass', '99999999I', 'Antón', 'Castro', 'Peña', '1983-04-25', '600999000', 'Calle Alcala 100, Madrid', 2],
            [10, 'dra.ruiz@drdiary.es', 'medpass', '10101010J', 'Sofía', 'Ruiz', 'Sosa', '1986-06-30', '600101010', 'Calle Goya 12, Madrid', 2],
            [11, 'dr.ortega@drdiary.es', 'medpass', '12121212K', 'Luis', 'Ortega', 'Lara', '1979-02-14', '600121212', 'Paseo Castellana 200, Madrid', 2],
            [12, 'dra.jimenez@drdiary.es', 'medpass', '13131313L', 'Alba', 'Jiménez', 'Pons', '1984-08-09', '600131313', 'Calle Atocha 50, Madrid', 2],
            [13, 'dr.navarro@drdiary.es', 'medpass', '14141414M', 'Igor', 'Navarro', 'Vila', '1981-11-11', '600141414', 'Calle Velazquez 33, Madrid', 2],
            [14, 'dra.mora@drdiary.es', 'medpass', '15151515N', 'Clara', 'Mora', 'Duarte', '1989-03-22', '600151515', 'Calle Princesa 10, Madrid', 2],
            [15, 'dr.pascual@drdiary.es', 'medpass', '16161616O', 'Hugo', 'Pascual', 'Roca', '1977-05-05', '600161616', 'Calle Serrano 88, Madrid', 2],
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
                'fecha_nac' => $u[7],
                'telf' => $u[8],
                'direccion' => $u[9],                
                'id_rol' => $u[10],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
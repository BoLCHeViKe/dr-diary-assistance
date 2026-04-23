<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            UserSeeder::class,
            AdminSeeder::class,
            MedicoSeeder::class,
            EspecialidadSeeder::class,
            PrestacionSeeder::class,
            HcSeeder::class,
            PacienteSeeder::class,
            AgendaSeeder::class,
            CitaSeeder::class,
            DetalleHcSeeder::class,
            FacturaSeeder::class,
            LineaFacturaSeeder::class,
        ]);
    }
}
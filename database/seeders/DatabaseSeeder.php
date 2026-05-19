<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MotivosAjusteSeeder::class,
            ParametrosSeeder::class,
            RalCatalogoSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}

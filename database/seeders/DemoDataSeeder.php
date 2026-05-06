<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Datos mínimos para arrancar pruebas:
 * - 1 admin (login al panel)
 * - 1 encargado (login al panel + barcode)
 * - 2 pintores (solo barcode)
 * - 2 productos típicos
 *
 * Lotes y movimientos los crearemos desde el panel para probar el flujo real.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Usuarios
        DB::table('usuarios')->updateOrInsert(
            ['codigo_barcode' => 'ADM-0001'],
            [
                'nombre'        => 'Admin Demo',
                'rol'           => 'admin',
                'email'         => 'admin@shadowpanama.com',
                'password_hash' => Hash::make('admin123'),
                'activo'        => true,
            ]
        );

        DB::table('usuarios')->updateOrInsert(
            ['codigo_barcode' => 'ENC-0001'],
            [
                'nombre'        => 'Luis Ramírez',
                'rol'           => 'encargado',
                'email'         => 'luis@shadowpanama.com',
                'password_hash' => Hash::make('luis123'),
                'activo'        => true,
            ]
        );

        DB::table('usuarios')->updateOrInsert(
            ['codigo_barcode' => 'PNT-0001'],
            ['nombre' => 'Juan Pérez',  'rol' => 'pintor', 'activo' => true]
        );
        DB::table('usuarios')->updateOrInsert(
            ['codigo_barcode' => 'PNT-0002'],
            ['nombre' => 'Carlos Díaz', 'rol' => 'pintor', 'activo' => true]
        );

        // Productos
        DB::table('productos')->updateOrInsert(
            ['ral' => 'RAL9005', 'textura' => 'Mate', 'brillo_pct' => 30],
            [
                'nombre_interno'   => 'Negro mate',
                'stock_minimo_kg'  => 50,
                'stock_critico_kg' => 20,
                'activo'           => true,
            ]
        );

        DB::table('productos')->updateOrInsert(
            ['ral' => 'RAL9016', 'textura' => 'Texturizado', 'brillo_pct' => 20],
            [
                'nombre_interno'   => 'Blanco texturizado',
                'stock_minimo_kg'  => 40,
                'stock_critico_kg' => 15,
                'activo'           => true,
            ]
        );
    }
}

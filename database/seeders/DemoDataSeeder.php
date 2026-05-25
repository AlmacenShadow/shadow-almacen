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
        // Usuarios con login al panel.
        // Importante: NO usar updateOrInsert con password_hash, porque eso
        // pisaría la contraseña real en producción si alguien corre db:seed
        // por error. Solo insertamos si no existe; si existe, no tocamos nada.
        // Las contraseñas reales se gestionan fuera del seeder (panel o SQL directo).
        if (!DB::table('usuarios')->where('codigo_barcode', 'ADM-0001')->exists()) {
            DB::table('usuarios')->insert([
                'codigo_barcode' => 'ADM-0001',
                'nombre'         => 'Admin',
                'rol'            => 'admin',
                'email'          => 'admin@shadowpanama.com',
                // Placeholder inservible — debe cambiarse vía panel antes del primer login.
                'password_hash'  => Hash::make(bin2hex(random_bytes(16))),
                'activo'         => true,
            ]);
        }

        if (!DB::table('usuarios')->where('codigo_barcode', 'ENC-0001')->exists()) {
            DB::table('usuarios')->insert([
                'codigo_barcode' => 'ENC-0001',
                'nombre'         => 'Luis Ramírez',
                'rol'            => 'encargado',
                'email'          => 'luis@shadowpanama.com',
                'password_hash'  => Hash::make(bin2hex(random_bytes(16))),
                'activo'         => true,
            ]);
        }

        DB::table('usuarios')->updateOrInsert(
            ['codigo_barcode' => 'PNT-0001'],
            ['nombre' => 'Juan Pérez',  'rol' => 'pintor', 'activo' => true]
        );
        DB::table('usuarios')->updateOrInsert(
            ['codigo_barcode' => 'PNT-0002'],
            ['nombre' => 'Carlos Díaz', 'rol' => 'pintor', 'activo' => true]
        );

        // Productos demo — usan textura_id en lugar de string desde 2026-05-17.
        // Mate y Texturizado vienen del TexturasSeeder (creado en la migración).
        $mateId        = DB::table('texturas')->where('nombre', 'Mate')->value('id');
        $texturizadoId = DB::table('texturas')->where('nombre', 'Texturizado')->value('id');

        if ($mateId) {
            DB::table('productos')->updateOrInsert(
                ['ral' => 'RAL9005', 'textura_id' => $mateId, 'brillo_pct' => 30],
                [
                    'nombre_interno'   => 'Negro mate',
                    'stock_minimo_kg'  => 50,
                    'stock_critico_kg' => 20,
                    'activo'           => true,
                ]
            );
        }

        if ($texturizadoId) {
            DB::table('productos')->updateOrInsert(
                ['ral' => 'RAL9016', 'textura_id' => $texturizadoId, 'brillo_pct' => 20],
                [
                    'nombre_interno'   => 'Blanco texturizado',
                    'stock_minimo_kg'  => 40,
                    'stock_critico_kg' => 15,
                    'activo'           => true,
                ]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParametrosSeeder extends Seeder
{
    public function run(): void
    {
        $parametros = [
            ['balanza.peso_estable_ms',       '1500',     'ms de lectura estable antes de permitir confirmar'],
            ['balanza.peso_minimo_kg',        '0.5',      'peso mínimo en báscula para habilitar confirmación'],
            ['balanza.modo_sin_balanza',      'false',    'modo pruebas sin hardware; captura manual siempre'],
            ['stock.dias_lead_time',          '30',       'días de lead-time del proveedor para alerta de reposición'],
            ['stock.dias_cobertura_amarillo', '45',       'umbral amarillo para cobertura vs consumo promedio'],
            ['stock.dias_cobertura_rojo',     '30',       'umbral rojo para cobertura vs consumo promedio'],
            ['vencimiento.dias_alerta',       '60,30,15', 'alertas escalonadas de próximos a vencer'],
            ['ventana_consumo.dias',          '60',       'ventana para calcular consumo promedio'],
        ];

        foreach ($parametros as [$clave, $valor, $descripcion]) {
            DB::table('parametros')->updateOrInsert(
                ['clave' => $clave],
                ['valor' => $valor, 'descripcion' => $descripcion]
            );
        }
    }
}

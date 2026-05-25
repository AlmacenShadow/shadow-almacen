<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MotivosAjusteSeeder extends Seeder
{
    public function run(): void
    {
        $motivos = [
            ['MERMA_DERRAME',         'Derrame o caída de caja',                -1, true],
            ['MERMA_HUMEDAD',         'Daño por humedad',                       -1, true],
            ['MERMA_CONTAMINACION',   'Contaminación / mezcla',                 -1, true],
            ['MERMA_VENCIMIENTO',     'Descarte por vencimiento',               -1, true],
            ['DEVOLUCION_PROVEEDOR',  'Devolución al proveedor',                -1, true],
            ['INGRESO_AJUSTE_FISICO', 'Ajuste físico: sobrante encontrado',     +1, true],
            ['MERMA_AJUSTE_FISICO',   'Ajuste físico: faltante',                -1, true],
            ['CORRECCION_REGISTRO',     'Corrección: anular salida o ajuste negativo erróneo',  +1, true],
            ['CORRECCION_REGISTRO_NEG', 'Corrección: anular retorno o ajuste positivo erróneo', -1, true],
            ['MUESTRA_QC',            'Muestra para prueba de color',           -1, true],
            ['OTRO',                  'Otro motivo',                            -1, true],
        ];

        foreach ($motivos as [$codigo, $descripcion, $signo, $requiere_nota]) {
            DB::table('motivos_ajuste')->updateOrInsert(
                ['codigo' => $codigo],
                [
                    'descripcion'   => $descripcion,
                    'signo'         => $signo,
                    'requiere_nota' => $requiere_nota,
                    'activo'        => true,
                ]
            );
        }
    }
}

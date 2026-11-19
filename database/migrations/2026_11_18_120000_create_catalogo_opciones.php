<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogos editables desde Configuración (antes eran constantes en Producto):
 * tipos de pintura (con sigla), acabados, tipos de textura, orígenes (con bandera)
 * y proveedores (sugerencias para el campo proveedor del lote).
 *
 * - Los productos guardan la `clave` (inmutable); el `nombre` se puede editar.
 * - productos.tipo_pintura pasa de guardar el nombre ("Poliéster") a la clave ("poliester").
 * - ral_catalogo gana `personalizado` para colores propios fuera del K7.
 * - parametros: valores por defecto que ahora sí usa el sistema.
 */
return new class extends Migration
{
    private const TIPOS = [
        // clave, nombre guardado antes en productos.tipo_pintura, sigla
        ['epoxi',                  'Epoxi',                     'EPO'],
        ['poliester',              'Poliéster',                 'POL'],
        ['hibrida',                'Híbrida (epoxi-poliéster)', 'EPP'],
        ['poliuretano',            'Poliuretano',               'PUR'],
        ['poliester_superdurable', 'Poliéster superdurable',    'PSD'],
    ];

    private const BANDERAS = [
        'china'    => ['China', 'cn', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 20"><rect width="30" height="20" fill="#de2910"/><polygon points="7,4 7.9,6.5 10.5,6.5 8.4,8.1 9.2,10.6 7,9 4.8,10.6 5.6,8.1 3.5,6.5 6.1,6.5" fill="#ffde00"/></svg>'],
        'colombia' => ['Colombia', 'co', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 20"><rect width="30" height="20" fill="#fcd116"/><rect y="10" width="30" height="5" fill="#003893"/><rect y="15" width="30" height="5" fill="#ce1126"/></svg>'],
        'usa'      => ['USA', 'us', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 20"><rect width="30" height="20" fill="#ffffff"/><g fill="#b22234"><rect width="30" height="2"/><rect y="4" width="30" height="2"/><rect y="8" width="30" height="2"/><rect y="12" width="30" height="2"/><rect y="16" width="30" height="2"/></g><rect width="12" height="11" fill="#3c3b6e"/></svg>'],
        'panama'   => ['Panamá', 'pa', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 20"><rect width="30" height="20" fill="#ffffff"/><rect x="15" width="15" height="10" fill="#d21034"/><rect y="10" width="15" height="10" fill="#005293"/><polygon points="7.5,2.5 8.3,4.8 10.8,4.8 8.8,6.3 9.5,8.7 7.5,7.2 5.5,8.7 6.2,6.3 4.2,4.8 6.7,4.8" fill="#005293"/><polygon points="22.5,12.5 23.3,14.8 25.8,14.8 23.8,16.3 24.5,18.7 22.5,17.2 20.5,18.7 21.2,16.3 19.2,14.8 21.7,14.8" fill="#d21034"/></svg>'],
    ];

    public function up(): void
    {
        Schema::create('catalogo_opciones', function (Blueprint $table) {
            $table->id();
            $table->string('grupo', 20);              // tipo_pintura|acabado|textura_tipo|origen|proveedor
            $table->string('clave', 60);              // lo que guardan los productos; no cambia
            $table->string('nombre', 120);
            $table->string('sigla', 10)->nullable();  // tipo_pintura
            $table->string('iso', 2)->nullable();     // origen: código ISO del país
            $table->mediumText('bandera')->nullable(); // origen: data URI (svg/png)
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['grupo', 'clave']);
        });

        $now = now();
        $filas = [];
        foreach (self::TIPOS as $i => [$clave, $nombre, $sigla]) {
            $filas[] = ['grupo' => 'tipo_pintura', 'clave' => $clave, 'nombre' => $nombre, 'sigla' => $sigla, 'orden' => ($i + 1) * 10];
        }
        foreach ([['mate', 'Mate'], ['semibrillo', 'Semibrillo'], ['brillante', 'Brillante']] as $i => [$c, $n]) {
            $filas[] = ['grupo' => 'acabado', 'clave' => $c, 'nombre' => $n, 'orden' => ($i + 1) * 10];
        }
        foreach ([['martillado', 'Martillado'], ['granulado', 'Granulado'], ['cuero', 'Cuero'], ['arrugado', 'Arrugado'], ['piel_naranja', 'Piel de naranja']] as $i => [$c, $n]) {
            $filas[] = ['grupo' => 'textura_tipo', 'clave' => $c, 'nombre' => $n, 'orden' => ($i + 1) * 10];
        }
        $i = 0;
        foreach (self::BANDERAS as $clave => [$nombre, $iso, $svg]) {
            $filas[] = ['grupo' => 'origen', 'clave' => $clave, 'nombre' => $nombre, 'iso' => $iso,
                        'bandera' => 'data:image/svg+xml;base64,' . base64_encode($svg), 'orden' => (++$i) * 10];
        }
        foreach ($filas as $f) {
            DB::table('catalogo_opciones')->insert($f + ['sigla' => null, 'iso' => null, 'bandera' => null,
                'activo' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        // productos.tipo_pintura: nombre -> clave
        foreach (self::TIPOS as [$clave, $nombre]) {
            DB::table('productos')->where('tipo_pintura', $nombre)->update(['tipo_pintura' => $clave]);
        }

        Schema::table('ral_catalogo', function (Blueprint $table) {
            $table->boolean('personalizado')->default(false);
        });

        foreach ([
            ['producto.stock_minimo_default_kg', '50', 'Stock mínimo (alerta amarilla) que se propone al crear un producto'],
            ['producto.stock_critico_default_kg', '20', 'Stock crítico (alerta roja) que se propone al crear un producto'],
            ['etiquetas.por_caja', '3', 'Etiquetas que se imprimen por caja al recibir un lote'],
        ] as [$clave, $valor, $desc]) {
            DB::table('parametros')->insertOrIgnore(['clave' => $clave, 'valor' => $valor, 'descripcion' => $desc]);
        }
    }

    public function down(): void
    {
        foreach (self::TIPOS as [$clave, $nombre]) {
            DB::table('productos')->where('tipo_pintura', $clave)->update(['tipo_pintura' => $nombre]);
        }
        Schema::dropIfExists('catalogo_opciones');
        Schema::table('ral_catalogo', function (Blueprint $table) {
            $table->dropColumn('personalizado');
        });
        DB::table('parametros')->whereIn('clave', [
            'producto.stock_minimo_default_kg', 'producto.stock_critico_default_kg', 'etiquetas.por_caja',
        ])->delete();
    }
};

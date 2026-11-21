@extends('layouts.panel')
@section('title', 'Buscar lotes')

@section('content')
  <div class="mb-4">
    <h2 class="text-2xl font-bold text-slate-900">Buscar lotes</h2>
    <p class="text-sm text-slate-500">Escribe un color, RAL, cliente, marca, lote o estante. Debajo, el mapa muestra dónde están.</p>
  </div>
  @include('consulta._app', ['modo' => 'panel', 'urlDatos' => $urlDatos, 'urlMovs' => $urlMovs, 'urlFicha' => $urlFicha])
@endsection

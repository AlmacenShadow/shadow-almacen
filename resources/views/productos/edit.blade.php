@extends('layouts.panel')
@section('title', 'Editar producto')

@section('content')
  <div class="mb-5">
    <h2 class="text-2xl font-bold text-slate-900">Editar producto</h2>
    <p class="text-sm text-slate-500 font-mono">
      {{ $producto->ral }} · {{ $producto->textura?->nombre ?? '?' }} · {{ $producto->brillo_pct }}%
    </p>
  </div>

  @include('productos._form')
@endsection

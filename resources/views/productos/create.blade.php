@extends('layouts.panel')
@section('title', 'Nuevo producto')

@section('content')
  <div class="mb-5">
    <h2 class="text-2xl font-bold text-slate-900">Nuevo producto</h2>
    <p class="text-sm text-slate-500">Elige los atributos; si ya existe un producto igual, te avisa.</p>
  </div>

  @include('productos._form', ['producto' => null])
@endsection

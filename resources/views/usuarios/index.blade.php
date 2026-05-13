@extends('layouts.panel')
@section('title', 'Usuarios')

@section('content')
  <div class="flex items-center justify-between mb-5">
    <div>
      <h2 class="text-2xl font-bold text-slate-900">Usuarios</h2>
      <p class="text-sm text-slate-500">Pintores (solo barcode), encargados y admin (barcode + email/contraseña)</p>
    </div>
    <a href="{{ route('usuarios.create') }}"
       class="bg-amber-500 hover:bg-amber-600 text-white font-semibold px-4 py-2 rounded-lg shadow">
      + Nuevo usuario
    </a>
  </div>

  <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
        <tr>
          <th class="text-left px-5 py-3">Código</th>
          <th class="text-left px-5 py-3">Nombre</th>
          <th class="text-left px-5 py-3">Rol</th>
          <th class="text-left px-5 py-3">Email</th>
          <th class="text-center px-5 py-3">Activo</th>
          <th></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        @foreach ($usuarios as $u)
          <tr>
            <td class="px-5 py-3 font-mono">{{ $u->codigo_barcode }}</td>
            <td class="px-5 py-3 font-medium">{{ $u->nombre }}</td>
            <td class="px-5 py-3">
              @php
                $color = match($u->rol) {
                  'admin'     => 'bg-violet-100 text-violet-800',
                  'encargado' => 'bg-blue-100 text-blue-800',
                  default     => 'bg-slate-100 text-slate-700',
                };
              @endphp
              <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $color }}">{{ ucfirst($u->rol) }}</span>
            </td>
            <td class="px-5 py-3 text-slate-600">{{ $u->email ?: '—' }}</td>
            <td class="px-5 py-3 text-center">
              @if ($u->activo)
                <span class="text-emerald-600">●</span>
              @else
                <span class="text-slate-400">○</span>
              @endif
            </td>
            <td class="px-5 py-3 text-right">
              <a href="{{ route('usuarios.edit', $u->id) }}" class="text-amber-600 hover:underline text-sm">editar</a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endsection

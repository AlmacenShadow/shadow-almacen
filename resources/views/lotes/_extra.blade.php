{{--
  Propietario externo + notas del lote. Compartido por create y edit;
  siempre editable (haya o no movimientos). Variable opcional: $lote.
--}}
@php
  $propExt  = (bool) old('propietario_externo', isset($lote) ? $lote->propietario_externo : false);
  $propNom  = old('propietario_nombre', isset($lote) ? $lote->propietario_nombre : '');
  $notasLot = old('notas', isset($lote) ? $lote->notas : '');
@endphp
<div class="col-span-2 rounded-lg border border-amber-200 bg-amber-50/50 p-4">
  <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 cursor-pointer">
    <input type="checkbox" name="propietario_externo" value="1" id="chkProp" @checked($propExt)
           onchange="const t=document.getElementById('txtProp'); t.disabled=!this.checked; t.required=this.checked; if(this.checked) t.focus();">
    <x-propietario-alerta class="w-4 h-4" />
    Propietario externo
    <span class="font-normal text-slate-500">— la pintura es de un cliente, no de Shadow</span>
  </label>
  <input type="text" name="propietario_nombre" id="txtProp" value="{{ $propNom }}" maxlength="120"
         placeholder="Nombre del propietario (cliente)"
         @disabled(!$propExt) @required($propExt)
         class="mt-2 w-full px-3 py-2 border border-slate-300 rounded-lg bg-white disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed">
</div>

<div class="col-span-2">
  <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1" for="notas-lote">Notas (opcional)</label>
  <textarea name="notas" id="notas-lote" rows="3" maxlength="2000"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg"
            placeholder="Cualquier observación sobre este lote">{{ $notasLot }}</textarea>
</div>

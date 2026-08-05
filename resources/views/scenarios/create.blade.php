@extends('layouts.app')
@section('content')
<h1 class="text-3xl font-black mb-6">Configurar cenário</h1>
<form method="POST" action="{{ route('scenarios.store') }}" class="rounded-2xl bg-white border p-6 grid gap-5">@csrf
<label>Ambiente<input name="environment" value="{{ old('environment') }}" required placeholder="Beco urbano, rodovia, mata..." class="mt-2 w-full rounded-xl border p-3"></label>
<label>Nível de ameaça<select name="threat_level" class="mt-2 w-full rounded-xl border p-3"><option value="controlada">Controlada</option><option value="potencial">Potencial</option><option value="ativa">Ativa</option></select></label>
<label>Quantidade de vítimas<input type="number" name="casualties" min="1" max="10" value="1" class="mt-2 w-full rounded-xl border p-3"></label>
<label>Mecanismo do trauma<input name="mechanism" required placeholder="Ferimento penetrante, explosão, atropelamento..." class="mt-2 w-full rounded-xl border p-3"></label>
<fieldset><legend class="font-medium">Recursos disponíveis</legend><div class="mt-2 grid sm:grid-cols-3 gap-2">@foreach(['Kit IFAK','Maca','DEA','Oxigênio','Rádio','Viatura'] as $resource)<label class="rounded-xl border p-3"><input type="checkbox" name="resources[]" value="{{ $resource }}"> {{ $resource }}</label>@endforeach</div></fieldset>
@error('environment')<p class="text-red-600">{{ $message }}</p>@enderror
<button class="rounded-xl bg-slate-900 text-white px-5 py-3 font-bold">Gerar cenário</button></form>
@endsection

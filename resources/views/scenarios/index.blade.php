@extends('layouts.app')
@section('content')
<div class="mb-8"><p class="text-sm font-bold uppercase tracking-widest text-slate-500">MVP</p><h1 class="text-4xl font-black">Cenários simulados de APH tático</h1><p class="mt-3 text-slate-600">Crie, execute, avalie e conduza o debriefing em um único fluxo.</p></div>
<div class="grid gap-4">@forelse($scenarios as $scenario)<a class="block rounded-2xl bg-white border p-5 hover:shadow" href="{{ route('scenarios.show',$scenario) }}"><div class="flex justify-between"><div><h2 class="font-bold text-xl">{{ $scenario->title }}</h2><p class="text-slate-500">{{ $scenario->casualties }} vítima(s) · ameaça {{ $scenario->threat_level }}</p></div><span class="text-sm font-bold uppercase">{{ $scenario->status }}</span></div></a>@empty<div class="rounded-2xl border border-dashed p-10 text-center"><p>Nenhum cenário criado.</p><a class="font-bold underline" href="{{ route('scenarios.create') }}">Criar o primeiro piloto</a></div>@endforelse</div>
<div class="mt-6">{{ $scenarios->links() }}</div>
@endsection

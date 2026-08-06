<?php

use App\Http\Controllers\ScenarioController;
use App\Models\Scenario;
use Illuminate\Support\Facades\Route;

// Página pública — landing marketing/institucional
Route::view('/', 'welcome')->name('home');

// Painel autenticado (MVP: sem auth). Redireciona /dashboard para o painel de cenários com KPIs.
Route::get('/dashboard', function () {
    $scenarios = Scenario::latest()->limit(6)->get();
    $all = Scenario::all(['status', 'score', 'critical_errors']);

    return view('dashboard', [
        'recent' => $scenarios,
        'total' => $all->count(),
        'drafts' => $all->where('status', 'draft')->count(),
        'running' => $all->where('status', 'running')->count(),
        'completed' => $all->where('status', 'completed')->count(),
        'avgScore' => optional($all->where('status', 'completed'))->avg('score'),
        // Top erros críticos mais listados no catálogo (independe de execução real)
        'topErrors' => $all
            ->pluck('critical_errors')
            ->filter(fn ($v) => is_array($v))
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(4),
    ]);
})->name('dashboard');

// Cenários (CRUD parcial + ações de execução/avaliação)
Route::resource('scenarios', ScenarioController::class)
    ->only(['index', 'create', 'store', 'show']);

Route::post('/scenarios/{scenario}/execute', [ScenarioController::class, 'execute'])
    ->name('scenarios.execute');

Route::post('/scenarios/{scenario}/evaluate', [ScenarioController::class, 'evaluate'])
    ->name('scenarios.evaluate');

// Healthcheck (para monitoramento de deploy)
Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'app' => config('app.name'),
    'version' => '0.1.0',
    'time' => now()->toIso8601String(),
]));

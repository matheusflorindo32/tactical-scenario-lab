<?php

use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\PersonIdentifierController;
use App\Http\Controllers\ScenarioController;
use App\Http\Controllers\UnitController;
use App\Models\Scenario;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

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
        'topErrors' => $all
            ->pluck('critical_errors')
            ->filter(fn ($value) => is_array($value))
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(4),
    ]);
})->name('dashboard');

Route::resource('organizations', OrganizationController::class)
    ->only(['index', 'create', 'store', 'show']);

Route::get('/organizations/{organization}/units/create', [UnitController::class, 'create'])
    ->name('organizations.units.create');
Route::post('/units', [UnitController::class, 'store'])->name('units.store');

Route::resource('people', PersonController::class)
    ->only(['index', 'create', 'store', 'show']);

Route::get('/people/{person}/identifiers/create', [PersonIdentifierController::class, 'create'])
    ->name('people.identifiers.create');
Route::post('/people/{person}/identifiers', [PersonIdentifierController::class, 'store'])
    ->name('people.identifiers.store');

Route::resource('scenarios', ScenarioController::class)
    ->only(['index', 'create', 'store', 'show']);

Route::post('/scenarios/{scenario}/execute', [ScenarioController::class, 'execute'])
    ->name('scenarios.execute');

Route::post('/scenarios/{scenario}/evaluate', [ScenarioController::class, 'evaluate'])
    ->name('scenarios.evaluate');

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'app' => config('app.name'),
    'version' => '0.2.0-dev',
    'time' => now()->toIso8601String(),
]));

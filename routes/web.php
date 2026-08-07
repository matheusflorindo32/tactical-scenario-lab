<?php

use App\Http\Controllers\ActiveOrganizationController;
use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationMembershipController;
use App\Http\Controllers\PersonContactController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\PersonIdentifierController;
use App\Http\Controllers\PersonRoleController;
use App\Http\Controllers\ScenarioController;
use App\Http\Controllers\UnitController;
use App\Models\Scenario;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

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
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::post('/organizations/{organization}/activate', [ActiveOrganizationController::class, 'update'])
        ->name('organizations.activate');

    Route::resource('organizations', OrganizationController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::patch('/organizations/{organization}/deactivate', [OrganizationController::class, 'deactivate'])
        ->name('organizations.deactivate');

    Route::get('/organizations/{organization}/units/create', [UnitController::class, 'create'])
        ->name('organizations.units.create');
    Route::post('/units', [UnitController::class, 'store'])->name('units.store');
    Route::get('/units/{unit}/edit', [UnitController::class, 'edit'])->name('units.edit');
    Route::put('/units/{unit}', [UnitController::class, 'update'])->name('units.update');
    Route::patch('/units/{unit}/deactivate', [UnitController::class, 'deactivate'])->name('units.deactivate');

    Route::resource('people', PersonController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::patch('/people/{person}/deactivate', [PersonController::class, 'deactivate'])
        ->name('people.deactivate');

    Route::get('/people/{person}/identifiers/create', [PersonIdentifierController::class, 'create'])
        ->name('people.identifiers.create');
    Route::post('/people/{person}/identifiers', [PersonIdentifierController::class, 'store'])
        ->name('people.identifiers.store');

    Route::get('/people/{person}/contacts/create', [PersonContactController::class, 'create'])
        ->name('people.contacts.create');
    Route::post('/people/{person}/contacts', [PersonContactController::class, 'store'])
        ->name('people.contacts.store');

    Route::get('/people/{person}/memberships/create', [OrganizationMembershipController::class, 'create'])
        ->name('people.memberships.create');
    Route::post('/people/{person}/memberships', [OrganizationMembershipController::class, 'store'])
        ->name('people.memberships.store');
    Route::patch('/people/{person}/memberships/{membership}/close', [OrganizationMembershipController::class, 'close'])
        ->name('people.memberships.close');

    Route::get('/people/{person}/roles/create', [PersonRoleController::class, 'create'])
        ->name('people.roles.create');
    Route::post('/people/{person}/roles', [PersonRoleController::class, 'store'])
        ->name('people.roles.store');
    Route::patch('/people/{person}/roles/{role}/revoke', [PersonRoleController::class, 'revoke'])
        ->name('people.roles.revoke');

    Route::resource('scenarios', ScenarioController::class)
        ->only(['index', 'create', 'store', 'show']);

    Route::post('/scenarios/{scenario}/execute', [ScenarioController::class, 'execute'])
        ->name('scenarios.execute');

    Route::post('/scenarios/{scenario}/evaluate', [ScenarioController::class, 'evaluate'])
        ->name('scenarios.evaluate');
});

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'app' => config('app.name'),
    'version' => '0.2.0-dev',
    'time' => now()->toIso8601String(),
]));

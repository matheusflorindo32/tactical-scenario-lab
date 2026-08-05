<?php
use App\Http\Controllers\ScenarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('scenarios.index'));
Route::resource('scenarios', ScenarioController::class)->only(['index','create','store','show']);
Route::post('/scenarios/{scenario}/execute', [ScenarioController::class, 'execute'])->name('scenarios.execute');
Route::post('/scenarios/{scenario}/evaluate', [ScenarioController::class, 'evaluate'])->name('scenarios.evaluate');
Route::get('/health', fn () => response()->json(['status' => 'ok']));

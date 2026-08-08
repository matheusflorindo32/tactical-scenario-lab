<?php

use App\Http\Controllers\AccessAdministrationController;
use App\Http\Controllers\ActionItemController;
use App\Http\Controllers\ActiveOrganizationController;
use App\Http\Controllers\AssessmentCriterionController;
use App\Http\Controllers\AssessmentEvidenceController;
use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\CriticalErrorOccurrenceController;
use App\Http\Controllers\DebriefEntryController;
use App\Http\Controllers\ExecutionAssessmentController;
use App\Http\Controllers\ExecutionCsvController;
use App\Http\Controllers\ExecutionEventController;
use App\Http\Controllers\ExecutionHistoryController;
use App\Http\Controllers\ExecutionInjectController;
use App\Http\Controllers\ExecutionParticipantController;
use App\Http\Controllers\ExecutionReportController;
use App\Http\Controllers\ExecutionResourceController;
use App\Http\Controllers\ExecutionTeamController;
use App\Http\Controllers\ExecutiveDashboardController;
use App\Http\Controllers\InstructorDashboardController;
use App\Http\Controllers\KeyTimeRecordController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationMembershipController;
use App\Http\Controllers\PersonContactController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\PersonIdentifierController;
use App\Http\Controllers\PersonRoleController;
use App\Http\Controllers\ScenarioController;
use App\Http\Controllers\ScenarioExecutionController;
use App\Http\Controllers\ScenarioVersionController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/dashboard', InstructorDashboardController::class)
    ->middleware(['auth', 'account.active'])
    ->name('dashboard');
Route::get('/dashboard/executive', ExecutiveDashboardController::class)
    ->middleware(['auth', 'account.active'])
    ->name('dashboard.executive');

Route::middleware(['auth', 'account.active'])->group(function () {
    Route::get('/history/executions', ExecutionHistoryController::class)->name('execution-history.index');
    Route::get('/reports/executions.csv', ExecutionCsvController::class)->name('reports.executions.csv');
    Route::get('/reports/executions/{execution}/pdf', ExecutionReportController::class)->name('reports.executions.pdf');

    Route::get('/access', [AccessAdministrationController::class, 'index'])->name('access.index');
    Route::get('/access/create', [AccessAdministrationController::class, 'create'])->name('access.create');
    Route::post('/access', [AccessAdministrationController::class, 'store'])->name('access.store');
    Route::get('/access/{access}/edit', [AccessAdministrationController::class, 'edit'])->name('access.edit');
    Route::put('/access/{access}', [AccessAdministrationController::class, 'update'])->name('access.update');
    Route::patch('/access/{access}/revoke', [AccessAdministrationController::class, 'revoke'])->name('access.revoke');
    Route::patch('/access/accounts/{user}/deactivate', [AccessAdministrationController::class, 'deactivateAccount'])->name('access.accounts.deactivate');
    Route::patch('/access/accounts/{user}/reactivate', [AccessAdministrationController::class, 'reactivateAccount'])->name('access.accounts.reactivate');

    Route::post('/organizations/{organization}/activate', [ActiveOrganizationController::class, 'update'])->name('organizations.activate');
    Route::resource('organizations', OrganizationController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::patch('/organizations/{organization}/deactivate', [OrganizationController::class, 'deactivate'])->name('organizations.deactivate');

    Route::get('/organizations/{organization}/units/create', [UnitController::class, 'create'])->name('organizations.units.create');
    Route::post('/units', [UnitController::class, 'store'])->name('units.store');
    Route::get('/units/{unit}/edit', [UnitController::class, 'edit'])->name('units.edit');
    Route::put('/units/{unit}', [UnitController::class, 'update'])->name('units.update');
    Route::patch('/units/{unit}/deactivate', [UnitController::class, 'deactivate'])->name('units.deactivate');

    Route::resource('people', PersonController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::patch('/people/{person}/deactivate', [PersonController::class, 'deactivate'])->name('people.deactivate');
    Route::get('/people/{person}/identifiers/create', [PersonIdentifierController::class, 'create'])->name('people.identifiers.create');
    Route::post('/people/{person}/identifiers', [PersonIdentifierController::class, 'store'])->name('people.identifiers.store');
    Route::get('/people/{person}/contacts/create', [PersonContactController::class, 'create'])->name('people.contacts.create');
    Route::post('/people/{person}/contacts', [PersonContactController::class, 'store'])->name('people.contacts.store');
    Route::get('/people/{person}/memberships/create', [OrganizationMembershipController::class, 'create'])->name('people.memberships.create');
    Route::post('/people/{person}/memberships', [OrganizationMembershipController::class, 'store'])->name('people.memberships.store');
    Route::patch('/people/{person}/memberships/{membership}/close', [OrganizationMembershipController::class, 'close'])->name('people.memberships.close');
    Route::get('/people/{person}/roles/create', [PersonRoleController::class, 'create'])->name('people.roles.create');
    Route::post('/people/{person}/roles', [PersonRoleController::class, 'store'])->name('people.roles.store');
    Route::patch('/people/{person}/roles/{role}/revoke', [PersonRoleController::class, 'revoke'])->name('people.roles.revoke');

    Route::resource('scenarios', ScenarioController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('/scenario-versions/{scenarioVersion}/publish', [ScenarioVersionController::class, 'publish'])->name('scenario-versions.publish');
    Route::post('/scenario-versions/{scenarioVersion}/executions', [ScenarioExecutionController::class, 'store'])->name('executions.store');
    Route::get('/executions/{execution}', [ScenarioExecutionController::class, 'show'])->name('executions.show');
    Route::patch('/executions/{execution}/start', [ScenarioExecutionController::class, 'start'])->name('executions.start');
    Route::patch('/executions/{execution}/complete', [ScenarioExecutionController::class, 'complete'])->name('executions.complete');
    Route::patch('/executions/{execution}/cancel', [ScenarioExecutionController::class, 'cancel'])->name('executions.cancel');
    Route::post('/executions/{execution}/teams', [ExecutionTeamController::class, 'store'])->name('execution-teams.store');
    Route::post('/executions/{execution}/participants', [ExecutionParticipantController::class, 'store'])->name('execution-participants.store');
    Route::post('/executions/{execution}/events', [ExecutionEventController::class, 'store'])->name('execution-events.store');
    Route::post('/executions/{execution}/injects', [ExecutionInjectController::class, 'store'])->name('execution-injects.store');
    Route::patch('/execution-injects/{inject}/deliver', [ExecutionInjectController::class, 'deliver'])->name('execution-injects.deliver');
    Route::patch('/execution-injects/{inject}/cancel', [ExecutionInjectController::class, 'cancel'])->name('execution-injects.cancel');
    Route::patch('/execution-resources/{resource}', [ExecutionResourceController::class, 'update'])->name('execution-resources.update');

    Route::post('/executions/{execution}/assessment', [ExecutionAssessmentController::class, 'store'])->name('assessments.store');
    Route::get('/assessments/{assessment}', [ExecutionAssessmentController::class, 'show'])->name('assessments.show');
    Route::patch('/assessments/{assessment}/adjustment', [ExecutionAssessmentController::class, 'adjustment'])->name('assessments.adjustment');
    Route::patch('/assessments/{assessment}/finalize', [ExecutionAssessmentController::class, 'finalize'])->name('assessments.finalize');

    Route::post('/assessments/{assessment}/criteria', [AssessmentCriterionController::class, 'store'])->name('assessment-criteria.store');
    Route::patch('/assessment-criteria/{criterion}', [AssessmentCriterionController::class, 'update'])->name('assessment-criteria.update');
    Route::delete('/assessment-criteria/{criterion}', [AssessmentCriterionController::class, 'destroy'])->name('assessment-criteria.destroy');
    Route::post('/assessment-criteria/{criterion}/evidence', [AssessmentEvidenceController::class, 'store'])->name('assessment-evidence.store');
    Route::delete('/assessment-evidence/{evidence}', [AssessmentEvidenceController::class, 'destroy'])->name('assessment-evidence.destroy');
    Route::post('/assessments/{assessment}/critical-errors', [CriticalErrorOccurrenceController::class, 'store'])->name('critical-error-occurrences.store');
    Route::delete('/critical-error-occurrences/{occurrence}', [CriticalErrorOccurrenceController::class, 'destroy'])->name('critical-error-occurrences.destroy');
    Route::post('/assessments/{assessment}/key-times', [KeyTimeRecordController::class, 'store'])->name('key-times.store');
    Route::delete('/key-times/{keyTime}', [KeyTimeRecordController::class, 'destroy'])->name('key-times.destroy');
    Route::post('/assessments/{assessment}/debrief-entries', [DebriefEntryController::class, 'store'])->name('debrief-entries.store');
    Route::patch('/debrief-entries/{entry}', [DebriefEntryController::class, 'update'])->name('debrief-entries.update');
    Route::delete('/debrief-entries/{entry}', [DebriefEntryController::class, 'destroy'])->name('debrief-entries.destroy');
    Route::post('/assessments/{assessment}/action-items', [ActionItemController::class, 'store'])->name('action-items.store');
    Route::patch('/action-items/{actionItem}', [ActionItemController::class, 'update'])->name('action-items.update');
    Route::delete('/action-items/{actionItem}', [ActionItemController::class, 'destroy'])->name('action-items.destroy');
    Route::patch('/action-items/{actionItem}/status', [ActionItemController::class, 'transition'])->name('action-items.transition');

    Route::post('/scenarios/{scenario}/execute', [ScenarioController::class, 'execute'])->name('scenarios.execute');
});

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'app' => config('app.name'),
    'version' => '0.2.0-dev',
    'time' => now()->toIso8601String(),
]));

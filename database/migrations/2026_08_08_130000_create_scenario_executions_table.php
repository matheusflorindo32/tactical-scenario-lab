<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scenario_executions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('scenario_version_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('sequence_number');
            $table->enum('status', ['draft', 'running', 'completed', 'cancelled'])->default('draft');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['scenario_version_id', 'sequence_number']);
            $table->index(['organization_id', 'status']);
            $table->index(['scenario_version_id', 'status']);
        });

        $this->backfillLegacyExecutions();
    }

    public function down(): void
    {
        Schema::dropIfExists('scenario_executions');
    }

    private function backfillLegacyExecutions(): void
    {
        if (! Schema::hasTable('scenarios') || ! Schema::hasTable('scenario_versions')) {
            return;
        }

        DB::table('scenarios')
            ->whereNotNull('organization_id')
            ->where(function ($query): void {
                $query
                    ->whereIn('status', ['running', 'completed'])
                    ->orWhereNotNull('started_at');
            })
            ->orderBy('id')
            ->chunkById(100, function ($scenarios): void {
                foreach ($scenarios as $scenario) {
                    $versionId = DB::table('scenario_versions')
                        ->where('scenario_id', $scenario->id)
                        ->orderByDesc('version_number')
                        ->value('id');

                    if ($versionId === null) {
                        continue;
                    }

                    $status = $scenario->status === 'completed' ? 'completed' : 'running';
                    $now = now();

                    DB::table('scenario_executions')->insert([
                        'uuid' => (string) Str::uuid(),
                        'organization_id' => $scenario->organization_id,
                        'scenario_version_id' => $versionId,
                        'sequence_number' => 1,
                        'status' => $status,
                        'started_at' => $scenario->started_at,
                        'completed_at' => $status === 'completed' ? $scenario->completed_at : null,
                        'cancelled_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }, 'id');
    }
};

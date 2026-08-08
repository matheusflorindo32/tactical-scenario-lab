<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('execution_participants', function (Blueprint $table): void {
            $table->foreignId('organization_membership_id')
                ->nullable()
                ->constrained('organization_memberships')
                ->nullOnDelete();
            $table->foreignId('unit_id_snapshot')
                ->nullable()
                ->constrained('units')
                ->nullOnDelete();
            $table->string('unit_name_snapshot', 160)->nullable();
            $table->string('position_snapshot', 120)->nullable();
            $table->index(['scenario_execution_id', 'unit_id_snapshot']);
        });

        $this->backfillHistoricalAttribution();
    }

    public function down(): void
    {
        Schema::table('execution_participants', function (Blueprint $table): void {
            $table->dropIndex(['scenario_execution_id', 'unit_id_snapshot']);
            $table->dropConstrainedForeignId('organization_membership_id');
            $table->dropConstrainedForeignId('unit_id_snapshot');
            $table->dropColumn(['unit_name_snapshot', 'position_snapshot']);
        });
    }

    private function backfillHistoricalAttribution(): void
    {
        DB::table('execution_participants')
            ->join('scenario_executions', 'scenario_executions.id', '=', 'execution_participants.scenario_execution_id')
            ->select([
                'execution_participants.id',
                'execution_participants.person_id',
                'scenario_executions.organization_id',
                'scenario_executions.started_at',
                'scenario_executions.created_at as execution_created_at',
            ])
            ->orderBy('execution_participants.id')
            ->chunk(200, function ($participants): void {
                foreach ($participants as $participant) {
                    $anchor = $participant->started_at ?: $participant->execution_created_at;
                    if (! $anchor) {
                        continue;
                    }

                    $anchorDate = substr((string) $anchor, 0, 10);
                    $candidates = DB::table('organization_memberships')
                        ->where('person_id', $participant->person_id)
                        ->where('organization_id', $participant->organization_id)
                        ->whereNotNull('started_at')
                        ->whereDate('started_at', '<=', $anchorDate)
                        ->where(function ($query) use ($anchorDate): void {
                            $query->whereNull('ended_at')
                                ->orWhereDate('ended_at', '>=', $anchorDate);
                        })
                        ->where(function ($query) use ($anchor): void {
                            $query->whereNull('deleted_at')
                                ->orWhere('deleted_at', '>', $anchor);
                        })
                        ->get();

                    if ($candidates->count() !== 1) {
                        continue;
                    }

                    $membership = $candidates->first();
                    $unit = $membership->unit_id
                        ? DB::table('units')->where('id', $membership->unit_id)->first()
                        : null;

                    DB::table('execution_participants')
                        ->where('id', $participant->id)
                        ->update([
                            'organization_membership_id' => $membership->id,
                            'unit_id_snapshot' => $unit?->id,
                            'unit_name_snapshot' => $unit?->name,
                            'position_snapshot' => $membership->position,
                        ]);
                }
            });
    }
};

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
        Schema::create('scenario_versions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('scenario_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('environment', 100);
            $table->string('threat_level', 20);
            $table->string('mechanism', 150);
            $table->unsignedBigInteger('estimated_casualty_count');
            $table->json('resources')->nullable();
            $table->json('learning_objectives')->nullable();
            $table->json('expected_actions')->nullable();
            $table->json('critical_errors')->nullable();
            $table->string('publication_status', 20)->default('draft');
            $table->timestamps();

            $table->unique(['scenario_id', 'version_number']);
            $table->index(['scenario_id', 'publication_status']);
        });

        DB::table('scenarios')
            ->orderBy('id')
            ->chunkById(200, function ($scenarios): void {
                $rows = [];

                foreach ($scenarios as $scenario) {
                    $rows[] = [
                        'uuid' => (string) Str::uuid(),
                        'scenario_id' => $scenario->id,
                        'version_number' => 1,
                        'environment' => $scenario->environment,
                        'threat_level' => $scenario->threat_level,
                        'mechanism' => $scenario->mechanism,
                        'estimated_casualty_count' => $scenario->estimated_casualty_count ?? $scenario->casualties,
                        'resources' => $scenario->resources,
                        'learning_objectives' => $scenario->learning_objectives,
                        'expected_actions' => $scenario->expected_actions,
                        'critical_errors' => $scenario->critical_errors,
                        'publication_status' => 'draft',
                        'created_at' => $scenario->created_at,
                        'updated_at' => $scenario->updated_at,
                    ];
                }

                if ($rows !== []) {
                    DB::table('scenario_versions')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenario_versions');
    }
};

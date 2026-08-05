<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scenarios', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('environment');
            $table->enum('threat_level', ['controlada', 'potencial', 'ativa']);
            $table->unsignedTinyInteger('casualties');
            $table->string('mechanism');
            $table->json('resources')->nullable();
            $table->json('learning_objectives');
            $table->json('expected_actions');
            $table->json('critical_errors');
            $table->enum('status', ['draft', 'running', 'completed'])->default('draft');
            $table->unsignedTinyInteger('score')->nullable();
            $table->text('debrief_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenarios');
    }
};

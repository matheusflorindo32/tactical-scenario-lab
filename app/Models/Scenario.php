<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scenario extends Model
{
    protected $fillable = [
        'title', 'environment', 'threat_level', 'casualties', 'mechanism', 'resources',
        'learning_objectives', 'expected_actions', 'critical_errors', 'status', 'score', 'debrief_notes',
    ];

    protected function casts(): array
    {
        return [
            'resources' => 'array', 'learning_objectives' => 'array',
            'expected_actions' => 'array', 'critical_errors' => 'array',
            'casualties' => 'integer', 'score' => 'integer',
        ];
    }
}

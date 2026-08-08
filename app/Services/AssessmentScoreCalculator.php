<?php

namespace App\Services;

final class AssessmentScoreCalculator
{
    public function calculateBase(iterable $criteria): float
    {
        $weightedScore = 0.0;

        foreach ($criteria as $criterion) {
            $score = (float) $this->value($criterion, 'score');
            $weight = (float) $this->value($criterion, 'weight');
            $weightedScore += ($score * $weight) / 100;
        }

        return round($weightedScore, 2);
    }

    public function calculateFinal(float $base, float $penalties, int $adjustment): float
    {
        return round(max(0.0, min(100.0, $base - $penalties + $adjustment)), 2);
    }

    public function result(float $finalScore, float $threshold, bool $automaticFail): string
    {
        return $automaticFail || $finalScore < $threshold ? 'failed' : 'passed';
    }

    private function value(mixed $criterion, string $key): mixed
    {
        if (is_array($criterion)) {
            return $criterion[$key] ?? null;
        }

        return $criterion->{$key} ?? null;
    }
}

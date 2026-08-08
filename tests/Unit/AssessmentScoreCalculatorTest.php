<?php

namespace Tests\Unit;

use App\Services\AssessmentScoreCalculator;
use PHPUnit\Framework\TestCase;

class AssessmentScoreCalculatorTest extends TestCase
{
    public function test_weighted_base_score_is_deterministic(): void
    {
        $calculator = new AssessmentScoreCalculator;

        $this->assertSame(86.00, $calculator->calculateBase([
            ['score' => 80.00, 'weight' => 40.00],
            ['score' => 90.00, 'weight' => 60.00],
        ]));
    }

    public function test_final_score_applies_penalties_adjustment_and_clamp(): void
    {
        $calculator = new AssessmentScoreCalculator;

        $this->assertSame(81.00, $calculator->calculateFinal(86.00, 7.00, 2));
        $this->assertSame(0.00, $calculator->calculateFinal(5.00, 20.00, -10));
        $this->assertSame(100.00, $calculator->calculateFinal(99.00, 0.00, 10));
    }

    public function test_result_respects_threshold_and_automatic_fail_precedence(): void
    {
        $calculator = new AssessmentScoreCalculator;

        $this->assertSame('failed', $calculator->result(95.00, 70.00, true));
        $this->assertSame('passed', $calculator->result(70.00, 70.00, false));
        $this->assertSame('failed', $calculator->result(69.99, 70.00, false));
    }
}

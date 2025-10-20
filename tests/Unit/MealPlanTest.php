<?php

namespace Tests\Unit;

use App\Support\MealPlan;
use PHPUnit\Framework\TestCase;

class MealPlanTest extends TestCase
{
    public function test_normalize_plan_preserves_units(): void
    {
        $plan = [
            [
                [
                    ['id' => 1, 'quantity' => 2, 'unit' => 'grams'],
                    ['ingredient_id' => 2, 'qty' => '3', 'measurement_unit' => 'ml'],
                ],
            ],
        ];

        $normalized = MealPlan::normalizePlan($plan, true);

        $this->assertSame('grams', $normalized[0][0][0]['unit']);
        $this->assertSame('ml', $normalized[0][0][1]['unit']);
    }

    public function test_normalize_entry_trims_and_limits_unit(): void
    {
        $entries = [
            ['id' => 5, 'quantity' => 1, 'unit' => '  Cups  '],
            ['id' => 6, 'quantity' => 1, 'unit' => str_repeat('y', 70)],
        ];

        $normalized = MealPlan::normalizeMeal($entries, true);

        $this->assertCount(2, $normalized);
        $this->assertSame('Cups', $normalized[0]['unit']);
        $this->assertSame(str_repeat('y', 50), $normalized[1]['unit']);
    }
}

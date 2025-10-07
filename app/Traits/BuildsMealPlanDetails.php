<?php

namespace App\Traits;

use App\Models\Ingredient;

trait BuildsMealPlanDetails
{
    protected function buildMealPlanDetails(array $plan): array
    {
        if (empty($plan)) {
            return [];
        }

        $ingredientIds = [];

        foreach ($plan as $dayMeals) {
            if (!is_array($dayMeals)) {
                continue;
            }

            foreach ($dayMeals as $mealEntries) {
                if (!is_array($mealEntries)) {
                    continue;
                }

                foreach ($mealEntries as $entry) {
                    if (!is_array($entry)) {
                        continue;
                    }

                    $id = (int) ($entry['id'] ?? 0);

                    if ($id > 0) {
                        $ingredientIds[] = $id;
                    }
                }
            }
        }

        $ingredientIds = array_values(array_unique($ingredientIds));

        if (empty($ingredientIds)) {
            return [];
        }

        $ingredients = Ingredient::whereIn('id', $ingredientIds)->get()->keyBy('id');

        $days = [];

        foreach ($plan as $dayIndex => $dayMeals) {
            $meals = [];
            $dayTotals = [
                'protein' => 0.0,
                'carbs' => 0.0,
                'fat' => 0.0,
                'calories' => 0.0,
            ];

            if (!is_array($dayMeals)) {
                $days[] = [
                    'day_number' => (int) $dayIndex + 1,
                    'meals' => [],
                    'totals' => $this->formatTotals($dayTotals),
                ];

                continue;
            }

            foreach ($dayMeals as $mealIndex => $mealEntries) {
                $mealDetails = [];
                $mealTotals = [
                    'protein' => 0.0,
                    'carbs' => 0.0,
                    'fat' => 0.0,
                    'calories' => 0.0,
                ];

                if (is_array($mealEntries)) {
                    foreach ($mealEntries as $entry) {
                        if (!is_array($entry)) {
                            continue;
                        }

                        $ingredientId = (int) ($entry['id'] ?? 0);

                        if ($ingredientId <= 0) {
                            continue;
                        }

                        $ingredient = $ingredients->get($ingredientId);

                        if (!$ingredient) {
                            continue;
                        }

                        $quantity = isset($entry['quantity']) ? (float) $entry['quantity'] : 1.0;

                        if ($quantity <= 0) {
                            continue;
                        }

                        $baseProtein = (float) $ingredient->protein;
                        $baseCarbs = (float) $ingredient->carbs;
                        $baseFat = (float) $ingredient->fat;
                        $baseCalories = ($baseProtein * 4) + ($baseCarbs * 4) + ($baseFat * 9);

                        $protein = round($baseProtein * $quantity, 2);
                        $carbs = round($baseCarbs * $quantity, 2);
                        $fat = round($baseFat * $quantity, 2);
                        $calories = round($baseCalories * $quantity, 2);

                        $mealTotals['protein'] += $protein;
                        $mealTotals['carbs'] += $carbs;
                        $mealTotals['fat'] += $fat;
                        $mealTotals['calories'] += $calories;

                        $mealDetails[] = [
                            'id' => $ingredientId,
                            'title' => $ingredient->title,
                            'quantity' => round($quantity, 2),
                            'protein' => $protein,
                            'carbs' => $carbs,
                            'fat' => $fat,
                            'calories' => $calories,
                            'image' => getSingleMedia($ingredient, 'ingredient_image', null),
                            'description' => $ingredient->description,
                        ];
                    }
                }

                $dayTotals['protein'] += $mealTotals['protein'];
                $dayTotals['carbs'] += $mealTotals['carbs'];
                $dayTotals['fat'] += $mealTotals['fat'];
                $dayTotals['calories'] += $mealTotals['calories'];

                $meals[] = [
                    'meal_number' => (int) $mealIndex + 1,
                    'ingredients' => $mealDetails,
                    'totals' => $this->formatTotals($mealTotals),
                ];
            }

            $days[] = [
                'day_number' => (int) $dayIndex + 1,
                'meals' => $meals,
                'totals' => $this->formatTotals($dayTotals),
            ];
        }

        return $days;
    }

    protected function formatTotals(array $totals): array
    {
        return [
            'protein' => round($totals['protein'] ?? 0, 2),
            'carbs' => round($totals['carbs'] ?? 0, 2),
            'fat' => round($totals['fat'] ?? 0, 2),
            'calories' => round($totals['calories'] ?? 0, 2),
        ];
    }
}

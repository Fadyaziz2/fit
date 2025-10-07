<?php

namespace App\Support;

use InvalidArgumentException;

class MealPlan
{
    public static function normalizePlan($plan, bool $strict = false, bool $preserveKeys = false): array
    {
        if (!is_array($plan)) {
            return [];
        }

        $normalized = [];

        foreach ($plan as $dayIndex => $dayMeals) {
            $dayKey = (int) $dayIndex;

            if (!array_key_exists($dayKey, $normalized)) {
                $normalized[$dayKey] = [];
            }

            if (!is_array($dayMeals)) {
                if ($strict && $dayMeals !== null && $dayMeals !== '') {
                    throw new InvalidArgumentException('Invalid meal plan structure.');
                }

                continue;
            }

            foreach ($dayMeals as $mealIndex => $meal) {
                $mealKey = (int) $mealIndex;
                $normalized[$dayKey][$mealKey] = self::normalizeMeal($meal, $strict);
            }

            ksort($normalized[$dayKey]);
        }

        ksort($normalized);

        if ($preserveKeys) {
            return $normalized;
        }

        return array_values(array_map(function ($dayMeals) {
            if (!is_array($dayMeals)) {
                return [];
            }

            return array_values(array_map(function ($mealEntries) {
                return array_values(is_array($mealEntries) ? $mealEntries : []);
            }, $dayMeals));
        }, $normalized));
    }

    public static function normalizeMeal($meal, bool $strict = false): array
    {
        $normalized = [];
        $seen = [];

        if (is_array($meal)) {
            foreach ($meal as $entry) {
                $normalizedEntry = self::normalizeEntry($entry, $strict, $seen);

                if ($normalizedEntry !== null) {
                    $normalized[] = $normalizedEntry;
                }
            }

            return $normalized;
        }

        if ($meal === null || $meal === '') {
            return [];
        }

        $normalizedEntry = self::normalizeEntry($meal, $strict, $seen);

        return $normalizedEntry !== null ? [$normalizedEntry] : [];
    }

    public static function normalizeEntry($value, bool $strict = false, array &$seen = []): ?array
    {
        if (is_object($value)) {
            $value = (array) $value;
        }

        if (is_array($value)) {
            $id = $value['id'] ?? $value['ingredient_id'] ?? $value['ingredient'] ?? null;
            $quantity = $value['quantity'] ?? $value['qty'] ?? $value['amount'] ?? 1;

            if ($id === null) {
                if ($strict) {
                    throw new InvalidArgumentException('Invalid meal ingredient entry.');
                }

                return null;
            }

            $id = (int) $id;
            $quantity = self::sanitizeQuantity($quantity, $strict);

            if ($id <= 0 || $quantity === null || in_array($id, $seen, true)) {
                return null;
            }

            $seen[] = $id;

            return [
                'id' => $id,
                'quantity' => $quantity,
            ];
        }

        if (!is_numeric($value)) {
            if ($strict) {
                throw new InvalidArgumentException('Invalid meal ingredient value.');
            }

            return null;
        }

        $id = (int) $value;

        if ($id <= 0 || in_array($id, $seen, true)) {
            return null;
        }

        $seen[] = $id;

        return [
            'id' => $id,
            'quantity' => 1.0,
        ];
    }

    public static function mergeNormalizedPlans(array $base, array $override): array
    {
        foreach ($override as $dayIndex => $dayMeals) {
            if (!isset($base[$dayIndex]) || !is_array($base[$dayIndex])) {
                $base[$dayIndex] = [];
            }

            foreach ($dayMeals as $mealIndex => $entries) {
                $base[$dayIndex][$mealIndex] = is_array($entries)
                    ? array_values($entries)
                    : [];
            }

            ksort($base[$dayIndex]);
        }

        ksort($base);

        return self::reindexPlan($base);
    }

    public static function reindexPlan(array $plan): array
    {
        ksort($plan);

        return array_values(array_map(function ($dayMeals) {
            if (!is_array($dayMeals)) {
                return [];
            }

            ksort($dayMeals);

            return array_values(array_map(function ($mealEntries) {
                if (!is_array($mealEntries)) {
                    return [];
                }

                return array_values(array_map(function ($entry) {
                    return is_array($entry) ? $entry : [];
                }, $mealEntries));
            }, $dayMeals));
        }, $plan));
    }

    protected static function sanitizeQuantity($value, bool $strict): ?float
    {
        if ($value === null || $value === '') {
            if ($strict) {
                throw new InvalidArgumentException('Invalid meal ingredient quantity.');
            }

            return null;
        }

        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }

        if (!is_numeric($value)) {
            if ($strict) {
                throw new InvalidArgumentException('Invalid meal ingredient quantity.');
            }

            return null;
        }

        $quantity = (float) $value;

        if ($quantity <= 0) {
            if ($strict) {
                throw new InvalidArgumentException('Invalid meal ingredient quantity.');
            }

            return null;
        }

        return round($quantity, 2);
    }
}

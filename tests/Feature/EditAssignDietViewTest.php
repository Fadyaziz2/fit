<?php

namespace Tests\Feature;

use App\Models\AssignDiet;
use App\Models\Diet;
use App\Models\Ingredient;
use Illuminate\Support\Collection;
use Tests\TestCase;

class EditAssignDietViewTest extends TestCase
{
    public function test_edit_assign_diet_view_renders_with_ingredient_options(): void
    {
        $assignment = AssignDiet::make([
            'user_id' => 1,
            'diet_id' => 1,
        ]);

        $diet = Diet::make([
            'id' => 1,
            'title' => 'Sample Diet',
            'servings' => 2,
            'days' => 2,
        ]);

        $ingredients = Collection::make([
            tap(Ingredient::make(['title' => 'Oats']), fn ($ingredient) => $ingredient->id = 1),
            tap(Ingredient::make(['title' => 'Yogurt']), fn ($ingredient) => $ingredient->id = 2),
        ]);

        $ingredientOptions = $ingredients->map(function (Ingredient $ingredient) {
            return [
                'id' => $ingredient->id,
                'title' => $ingredient->title,
                'disliked' => false,
            ];
        })->values();

        $session = app('session.store');
        $session->start();
        app('request')->setLaravelSession($session);

        $html = view('users.edit_assign_diet', [
            'assignment' => $assignment,
            'diet' => $diet,
            'plan' => [
                [
                    [
                        ['id' => 1, 'quantity' => 1],
                    ],
                ],
            ],
            'maxMeals' => 1,
            'customPlan' => [],
            'ingredients' => $ingredients,
            'ingredientsMap' => $ingredients->keyBy('id'),
            'dislikedIngredientIds' => [],
            'ingredientOptions' => $ingredientOptions,
        ])->render();

        $this->assertStringContainsString('data-action="add-meal-ingredient"', $html);
        $this->assertStringContainsString('window.dietMealIngredientOptions', $html);
    }
}

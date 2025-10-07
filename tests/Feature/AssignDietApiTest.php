<?php

namespace Tests\Feature;

use App\Models\AssignDiet;
use App\Models\CategoryDiet;
use App\Models\Diet;
use App\Models\Ingredient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Tests\TestCase;

class AssignDietApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_scope_my_assign_diet_returns_diets_for_given_user()
    {
        $role = Role::create([
            'name' => 'user',
            'title' => 'User',
            'status' => 1,
            'guard_name' => 'web',
        ]);

        $user = User::create([
            'username' => 'member',
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone_number' => null,
            'status' => 'active',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'gender' => 'male',
            'display_name' => 'Test User',
            'login_type' => 'email',
            'user_type' => 'user',
            'player_id' => null,
            'is_subscribe' => 0,
            'timezone' => 'UTC',
            'last_notification_seen' => null,
        ]);

        $otherUser = User::create([
            'username' => 'member2',
            'first_name' => 'Other',
            'last_name' => 'User',
            'phone_number' => null,
            'status' => 'active',
            'email' => 'other@example.com',
            'password' => Hash::make('password'),
            'gender' => 'male',
            'display_name' => 'Other User',
            'login_type' => 'email',
            'user_type' => 'user',
            'player_id' => null,
            'is_subscribe' => 0,
            'timezone' => 'UTC',
            'last_notification_seen' => null,
        ]);

        $user->assignRole($role);
        $otherUser->assignRole($role);

        $category = CategoryDiet::create([
            'title' => 'General',
            'slug' => 'general',
            'status' => 'active',
        ]);

        $diet = Diet::create([
            'title' => 'Sample Diet',
            'slug' => 'sample-diet',
            'categorydiet_id' => $category->id,
            'calories' => '100',
            'carbs' => '10',
            'protein' => '5',
            'fat' => '2',
            'servings' => 1,
            'days' => 1,
            'total_time' => '10',
            'is_featured' => '0',
            'status' => 'active',
            'ingredients' => [],
            'description' => 'Sample description',
            'is_premium' => 0,
        ]);

        AssignDiet::create([
            'user_id' => $user->id,
            'diet_id' => $diet->id,
            'serve_times' => ['08:00'],
        ]);

        AssignDiet::create([
            'user_id' => $otherUser->id,
            'diet_id' => $diet->id,
            'serve_times' => ['09:00'],
        ]);

        $results = Diet::myAssignDiet($user->id)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($diet));
    }

    public function test_assign_diet_list_endpoint_returns_assigned_diet_with_meal_times()
    {
        $role = Role::create([
            'name' => 'user',
            'title' => 'User',
            'status' => 1,
            'guard_name' => 'web',
        ]);

        $user = User::create([
            'username' => 'member',
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone_number' => null,
            'status' => 'active',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'gender' => 'male',
            'display_name' => 'Test User',
            'login_type' => 'email',
            'user_type' => 'user',
            'player_id' => null,
            'is_subscribe' => 0,
            'timezone' => 'UTC',
            'last_notification_seen' => null,
        ]);

        $user->assignRole($role);

        $category = CategoryDiet::create([
            'title' => 'General',
            'slug' => 'general',
            'status' => 'active',
        ]);

        $ingredient = Ingredient::create([
            'title' => 'Chicken Breast',
            'description' => 'Lean protein source',
            'protein' => 31,
            'fat' => 3.6,
            'carbs' => 0,
        ]);

        $diet = Diet::create([
            'title' => 'Protein Diet',
            'slug' => 'protein-diet',
            'categorydiet_id' => $category->id,
            'calories' => '450',
            'carbs' => '10',
            'protein' => '40',
            'fat' => '12',
            'servings' => 1,
            'days' => 1,
            'total_time' => '15',
            'is_featured' => '0',
            'status' => 'active',
            'ingredients' => [
                [
                    [
                        ['id' => $ingredient->id, 'quantity' => 1],
                    ],
                ],
            ],
            'description' => 'A simple protein rich diet.',
            'is_premium' => 0,
        ]);

        AssignDiet::create([
            'user_id' => $user->id,
            'diet_id' => $diet->id,
            'serve_times' => ['08:30'],
        ]);

        $this->assertSame(['08:30'], AssignDiet::first()->serve_times);

        $request = Request::create('/api/assign-diet-list', 'GET');
        $request->setUserResolver(fn () => $user);
        auth()->setUser($user);

        $response = app(\App\Http\Controllers\API\AssignUserController::class)->getAssignDiet($request);

        $this->assertTrue($response->isOk());

        $payload = $response->getData(true);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('data', $payload);

        $collection = $payload['data'];

        $this->assertIsArray($collection);
        $this->assertNotEmpty($collection);

        $first = $collection[0];

        $this->assertSame($diet->id, $first['id']);
        $this->assertSame('Protein Diet', $first['title']);
        $this->assertSame('08:30', $first['serve_times'][0]);
        $this->assertSame('08:30', $first['meal_plan'][0]['meals'][0]['time']);
    }
}


<?php

namespace Tests\Feature;

use App\Models\AssignDiet;
use App\Models\CategoryDiet;
use App\Models\Diet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
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

    public function test_assign_diet_api_returns_flattened_resource_list()
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

        Sanctum::actingAs($user);

        $request = Request::create('/api/assign-diet-list', 'GET');

        $response = app(\App\Http\Controllers\API\AssignUserController::class)->getAssignDiet($request);

        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $payload['pagination']['total_items']);
        $this->assertIsArray($payload['data']);
        $this->assertArrayNotHasKey('data', $payload['data']);
        $this->assertSame($diet->id, $payload['data'][0]['id']);
        $this->assertSame($diet->title, $payload['data'][0]['title']);
    }
}


<?php

namespace Tests\Unit;

use App\DataTables\UsersDataTable;
use App\Models\Branch;
use App\Models\FreeBookingRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermissionScope;
use App\Models\Specialist;
use App\Models\SpecialistAppointment;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserManagedIdsTest extends TestCase
{
    use RefreshDatabase;

    protected Role $role;
    protected User $superUser;
    protected Branch $branch;
    protected Specialist $specialist;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('permission.models.role', Role::class);
        config()->set('permission.models.permission', Permission::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::create([
            'name' => 'user-list',
            'title' => 'User List',
            'guard_name' => 'web',
        ]);

        $this->role = Role::create([
            'name' => 'manager',
            'title' => 'Manager',
            'status' => 1,
        ]);

        $this->role->givePermissionTo('user-list');

        RolePermissionScope::create([
            'role_id' => $this->role->id,
            'permission_name' => 'user-list',
            'scope' => RolePermissionScope::SCOPE_PRIVATE,
        ]);

        $this->superUser = User::create([
            'username' => 'superuser',
            'first_name' => 'Super',
            'last_name' => 'User',
            'display_name' => 'Super User',
            'email' => 'super@example.com',
            'password' => Hash::make('password'),
            'user_type' => 'manager',
            'status' => 'active',
        ]);

        $this->superUser->assignRole($this->role);

        $this->branch = Branch::create([
            'name' => 'Main Branch',
            'phone' => null,
            'email' => null,
            'address' => null,
        ]);

        $this->specialist = Specialist::create([
            'name' => 'Specialist',
            'super_user_id' => $this->superUser->id,
            'branch_id' => $this->branch->id,
            'phone' => null,
            'email' => null,
            'specialty' => null,
            'is_active' => true,
            'notes' => null,
        ]);
    }

    /** @test */
    public function it_returns_users_assigned_through_profiles(): void
    {
        $client = User::create([
            'username' => 'clientuser',
            'first_name' => 'Client',
            'last_name' => 'User',
            'display_name' => 'Client User',
            'email' => 'client@example.com',
            'password' => Hash::make('password'),
            'user_type' => 'user',
            'status' => 'active',
        ]);

        UserProfile::create([
            'user_id' => $client->id,
            'specialist_id' => $this->specialist->id,
        ]);

        $this->actingAs($this->superUser);

        $ids = (new UsersDataTable())->query()->pluck('id');

        $this->assertSame([$client->id], $ids->all());
    }

    /** @test */
    public function it_includes_users_with_appointments_even_without_profiles(): void
    {
        $client = User::create([
            'username' => 'manualuser',
            'first_name' => 'Manual',
            'last_name' => 'User',
            'display_name' => 'Manual User',
            'email' => 'manual@example.com',
            'password' => Hash::make('password'),
            'user_type' => 'user',
            'status' => 'active',
        ]);

        SpecialistAppointment::create([
            'user_id' => $client->id,
            'specialist_id' => $this->specialist->id,
            'branch_id' => $this->branch->id,
            'appointment_date' => Carbon::now()->toDateString(),
            'appointment_time' => '10:00:00',
            'type' => 'regular',
            'status' => 'pending',
            'notes' => null,
        ]);

        $this->actingAs($this->superUser);

        $ids = (new UsersDataTable())->query()->pluck('id');

        $this->assertSame([$client->id], $ids->all());
    }

    /** @test */
    public function it_includes_users_with_free_booking_requests(): void
    {
        $client = User::create([
            'username' => 'freerequestuser',
            'first_name' => 'Free',
            'last_name' => 'Request',
            'display_name' => 'Free Request',
            'email' => 'free@example.com',
            'password' => Hash::make('password'),
            'user_type' => 'user',
            'status' => 'active',
        ]);

        FreeBookingRequest::create([
            'user_id' => $client->id,
            'specialist_id' => $this->specialist->id,
            'branch_id' => $this->branch->id,
            'phone' => '123456789',
            'status' => 'pending',
        ]);

        $this->actingAs($this->superUser);

        $ids = (new UsersDataTable())->query()->pluck('id');

        $this->assertSame([$client->id], $ids->all());
    }
}

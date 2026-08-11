<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
});

test('an admin can register a new user with exactly one role', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/users', [
            'name' => 'New Coordinator',
            'email' => 'coordinator@example.com',
            'password' => 'a-strong-password',
            'role' => 'program_coordinator',
        ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'coordinator@example.com')
        ->assertJsonPath('data.roles.0', 'program_coordinator')
        ->assertJsonCount(1, 'data.roles');

    $created = User::where('email', 'coordinator@example.com')->firstOrFail();
    expect($created->hasRole('program_coordinator'))->toBeTrue();
});

test('registration validates required fields and a fixed role value', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/users', ['role' => 'super_admin'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
});

test('a non-admin cannot register users', function () {
    $coordinator = User::factory()->create();
    $coordinator->assignRole('program_coordinator');

    $this->actingAs($coordinator)
        ->postJson('/api/v1/admin/users', [
            'name' => 'Someone',
            'email' => 'someone@example.com',
            'password' => 'a-strong-password',
            'role' => 'program_coordinator',
        ])
        ->assertForbidden();
});

test('an unauthenticated request cannot register users', function () {
    $this->postJson('/api/v1/admin/users', [
        'name' => 'Someone',
        'email' => 'someone@example.com',
        'password' => 'a-strong-password',
        'role' => 'program_coordinator',
    ])->assertUnauthorized();
});

test('an admin can list users', function () {
    $admin = makeAdmin();
    User::factory()->count(2)->create();

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/users')
        ->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta']);
});

test('an admin can reassign a user\'s role, replacing the previous one', function () {
    $admin = makeAdmin();
    $target = User::factory()->create();
    $target->assignRole('program_coordinator');

    $this->actingAs($admin)
        ->postJson("/api/v1/admin/users/{$target->id}/role", ['role' => 'qa_officer'])
        ->assertOk()
        ->assertJsonPath('data.roles.0', 'qa_officer')
        ->assertJsonCount(1, 'data.roles');

    $target->refresh();
    expect($target->hasRole('qa_officer'))->toBeTrue();
    expect($target->hasRole('program_coordinator'))->toBeFalse();
});

test('assigning an invalid role value is rejected', function () {
    $admin = makeAdmin();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->postJson("/api/v1/admin/users/{$target->id}/role", ['role' => 'super_admin'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['role']);
});

test('a non-admin cannot assign roles', function () {
    $coordinator = User::factory()->create();
    $coordinator->assignRole('program_coordinator');
    $target = User::factory()->create();

    $this->actingAs($coordinator)
        ->postJson("/api/v1/admin/users/{$target->id}/role", ['role' => 'qa_officer'])
        ->assertForbidden();
});

test('deactivating a user immediately revokes their tokens and sessions', function () {
    $admin = makeAdmin();
    $target = User::factory()->create();
    $target->createToken('device');

    DB::table('sessions')->insert([
        'id' => 'test-session-id',
        'user_id' => $target->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'payload' => 'x',
        'last_activity' => time(),
    ]);

    expect($target->tokens()->count())->toBe(1);
    expect(DB::table('sessions')->where('user_id', $target->id)->count())->toBe(1);

    $this->actingAs($admin)
        ->patchJson("/api/v1/admin/users/{$target->id}", ['is_active' => false])
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    expect($target->tokens()->count())->toBe(0);
    expect(DB::table('sessions')->where('user_id', $target->id)->count())->toBe(0);
});

test('an admin cannot deactivate their own account', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->patchJson("/api/v1/admin/users/{$admin->id}", ['is_active' => false])
        ->assertStatus(422)
        ->assertJsonPath('message', 'An administrator cannot deactivate their own account.');

    expect($admin->fresh()->is_active)->toBeTrue();
});

test('a deactivated user is blocked from protected routes even with a resolvable session', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->actingAs($user)
        ->getJson('/api/v1/auth/me')
        ->assertStatus(403)
        ->assertJsonPath('message', 'This account has been deactivated.');
});

test('an admin can delete a user, revoking access and soft-deleting the record', function () {
    $admin = makeAdmin();
    $target = User::factory()->create();
    $target->createToken('device');

    $this->actingAs($admin)
        ->deleteJson("/api/v1/admin/users/{$target->id}")
        ->assertNoContent();

    expect($target->tokens()->count())->toBe(0);
    expect(User::find($target->id))->toBeNull();
    expect(User::withTrashed()->find($target->id))->not->toBeNull();
});

test('an admin cannot delete their own account', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->deleteJson("/api/v1/admin/users/{$admin->id}")
        ->assertStatus(422)
        ->assertJsonPath('message', 'An administrator cannot delete their own account.');

    expect(User::find($admin->id))->not->toBeNull();
});

test('a non-admin cannot delete users', function () {
    $coordinator = User::factory()->create();
    $coordinator->assignRole('program_coordinator');
    $target = User::factory()->create();

    $this->actingAs($coordinator)
        ->deleteJson("/api/v1/admin/users/{$target->id}")
        ->assertForbidden();

    expect(User::find($target->id))->not->toBeNull();
});

test('an admin can grant a user a direct permission beyond their role', function () {
    $admin = makeAdmin();
    $target = User::factory()->create();
    $target->assignRole('program_coordinator');

    $response = $this->actingAs($admin)
        ->putJson("/api/v1/admin/users/{$target->id}/permissions", [
            'permissions' => ['accreditation.manage'],
        ])
        ->assertOk()
        ->assertJsonPath('data.direct_permissions.0', 'accreditation.manage');

    expect($response->json('data.effective_permissions'))
        ->toContain('accreditation.manage', 'programs.create');
    expect($target->fresh()->hasPermissionTo('accreditation.manage'))->toBeTrue();
});

test('syncing permissions replaces the previous direct set', function () {
    $admin = makeAdmin();
    $target = User::factory()->create();
    $target->givePermissionTo('accreditation.manage');

    $this->actingAs($admin)
        ->putJson("/api/v1/admin/users/{$target->id}/permissions", ['permissions' => []])
        ->assertOk()
        ->assertJsonCount(0, 'data.direct_permissions');

    expect($target->fresh()->hasPermissionTo('accreditation.manage'))->toBeFalse();
});

test('an unknown permission slug is rejected', function () {
    $admin = makeAdmin();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->putJson("/api/v1/admin/users/{$target->id}/permissions", [
            'permissions' => ['not-a-real-permission'],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['permissions.0']);
});

test('a non-admin cannot manage another user\'s permissions', function () {
    $coordinator = User::factory()->create();
    $coordinator->assignRole('program_coordinator');
    $target = User::factory()->create();

    $this->actingAs($coordinator)
        ->putJson("/api/v1/admin/users/{$target->id}/permissions", ['permissions' => []])
        ->assertForbidden();
});

test('listing users does not N+1 on roles/permissions as the user count grows', function () {
    // Both counts stay within the default page size (15) — the point is
    // to prove query count doesn't scale with row count within a page,
    // not to test pagination itself.
    $admin = makeAdmin();

    $countQueriesForPageOf = function (int $userCount) use ($admin) {
        User::factory()->count($userCount)->create()->each(
            fn (User $user) => $user->assignRole('program_coordinator')
        );

        // Setup above (inserts + role assignment) must not be counted —
        // flush immediately before the request, not just after measuring.
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($admin)->getJson('/api/v1/admin/users')->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::flushQueryLog();

        return $queryCount;
    };

    $queriesForThree = $countQueriesForPageOf(3);
    $queriesForTwelve = $countQueriesForPageOf(9); // 3 + 9 = 12, still one page

    // If roles/permissions were lazy-loaded per row, adding 9 more users
    // to the same page would add roughly 9 x (2-3) more queries. A flat,
    // small increase (session/auth bookkeeping only) proves the eager
    // load is working.
    expect($queriesForTwelve - $queriesForThree)->toBeLessThan(5);
});

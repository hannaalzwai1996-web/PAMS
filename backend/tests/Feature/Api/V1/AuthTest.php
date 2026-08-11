<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('a user can log in and receive their profile with roles', function () {
    $user = User::factory()->create(['password' => bcrypt('secret-password')]);
    $user->assignRole('program_coordinator');

    $this->withHeaders(spaHeaders())
        ->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])
        ->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.roles.0', 'program_coordinator');

    $this->assertAuthenticatedAs($user);
});

test('login fails with invalid credentials using the standard error envelope', function () {
    $user = User::factory()->create(['password' => bcrypt('secret-password')]);

    $this->withHeaders(spaHeaders())
        ->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
        ->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['email']]);

    $this->assertGuest();
});

test('a deactivated user cannot log in', function () {
    $user = User::factory()->create([
        'password' => bcrypt('secret-password'),
        'is_active' => false,
    ]);

    $this->withHeaders(spaHeaders())
        ->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])
        ->assertStatus(422);

    $this->assertGuest();
});

test('an authenticated user can fetch their own profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

test('unauthenticated requests are rejected with the standard error envelope', function () {
    $this->getJson('/api/v1/auth/me')
        ->assertStatus(401)
        ->assertExactJson(['message' => 'Unauthenticated.']);
});

test('a logged in user can log out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withHeaders(spaHeaders())
        ->postJson('/api/v1/auth/logout')
        ->assertOk();
});

test('repeated failed login attempts for the same account are rate-limited', function () {
    $user = User::factory()->create(['password' => bcrypt('correct-password')]);

    for ($i = 0; $i < 5; $i++) {
        $this->withHeaders(spaHeaders())
            ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertStatus(422);
    }

    // The 6th attempt within the window is throttled, even with the
    // correct password — SRS SEC-09.
    $this->withHeaders(spaHeaders())
        ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'correct-password'])
        ->assertStatus(429);
});

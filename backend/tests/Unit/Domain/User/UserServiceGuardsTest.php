<?php

use App\Domain\User\DTOs\UserDTO;
use App\Domain\User\Repositories\Contracts\UserRepositoryInterface;
use App\Domain\User\Services\UserService;
use App\Models\User;
use App\Support\Exceptions\BusinessRuleException;

/**
 * True isolated unit tests for UserService's two self-service guards.
 * Both guards run before any repository/facade call, so they're cleanly
 * testable with a mocked repository and in-memory (unpersisted) User
 * instances — no database, no HTTP. The "guard allows a valid mutation
 * through" paths are intentionally left to the Feature-level suite
 * (tests/Feature/Api/V1/Admin/UserManagementTest.php), since a successful
 * deactivate/delete also exercises token/session revocation, which needs
 * a real database to be a meaningful assertion rather than a mock of
 * Eloquent internals.
 */
function userWithId(string $id): User
{
    $user = new User;
    $user->id = $id;

    return $user;
}

beforeEach(function () {
    $this->repository = Mockery::mock(UserRepositoryInterface::class);
    $this->service = new UserService($this->repository);
});

afterEach(function () {
    Mockery::close();
});

test('an admin cannot deactivate their own account', function () {
    $admin = userWithId('user-1');

    $this->repository->shouldNotReceive('update');

    $this->service->update($admin, UserDTO::fromArray(['is_active' => false]), $admin);
})->throws(BusinessRuleException::class, 'An administrator cannot deactivate their own account.');

test('an admin cannot delete their own account', function () {
    $admin = userWithId('user-1');

    $this->repository->shouldNotReceive('delete');

    $this->service->delete($admin, $admin);
})->throws(BusinessRuleException::class, 'An administrator cannot delete their own account.');

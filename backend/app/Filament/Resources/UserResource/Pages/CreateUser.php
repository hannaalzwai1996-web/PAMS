<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Domain\User\DTOs\UserDTO;
use App\Domain\User\Services\UserService;
use App\Filament\Concerns\HandlesDomainExceptions;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Registration is admin-only provisioning (ARCH-0001 §0/§3) — this page
 * IS that admin surface for Filament, routed entirely through
 * UserService::register(), never `User::create()` directly.
 */
class CreateUser extends CreateRecord
{
    use HandlesDomainExceptions;

    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return static::runOrNotify(
            fn () => app(UserService::class)->register(UserDTO::fromArray($data))
        );
    }
}

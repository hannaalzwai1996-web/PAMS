<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Domain\User\DTOs\AssignRoleDTO;
use App\Domain\User\DTOs\UserDTO;
use App\Domain\User\Services\UserService;
use App\Filament\Concerns\HandlesDomainExceptions;
use App\Filament\Resources\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    use HandlesDomainExceptions;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->action(fn () => static::runOrNotify(
                    fn () => app(UserService::class)->delete($this->record, auth()->user())
                )),
        ];
    }

    /**
     * Profile fields go through UserService::update() (self-deactivation
     * guard + access revocation); role is a separate operation via
     * UserService::assignRole() — same split as the REST API
     * (PATCH vs POST .../role), just both submitted from one form here
     * for a simpler admin UX.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return static::runOrNotify(function () use ($record, $data) {
            $service = app(UserService::class);

            $updated = $service->update(
                $record,
                UserDTO::fromArray([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'is_active' => $data['is_active'],
                ]),
                auth()->user(),
            );

            return $service->assignRole($updated, AssignRoleDTO::fromArray(['role' => $data['role']]));
        });
    }
}

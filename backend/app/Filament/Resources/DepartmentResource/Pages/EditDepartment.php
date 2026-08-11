<?php

namespace App\Filament\Resources\DepartmentResource\Pages;

use App\Domain\Department\DTOs\DepartmentDTO;
use App\Domain\Department\Services\DepartmentService;
use App\Filament\Concerns\HandlesDomainExceptions;
use App\Filament\Resources\DepartmentResource;
use App\Models\Department;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditDepartment extends EditRecord
{
    use HandlesDomainExceptions;

    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->action(fn () => static::runOrNotify(
                    fn () => app(DepartmentService::class)->delete($this->record)
                )),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Department $record */
        return app(DepartmentService::class)->update($record, DepartmentDTO::fromArray($data));
    }
}

<?php

namespace App\Filament\Resources\DepartmentResource\Pages;

use App\Domain\Department\DTOs\DepartmentDTO;
use App\Domain\Department\Services\DepartmentService;
use App\Filament\Resources\DepartmentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDepartment extends CreateRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(DepartmentService::class)->create(DepartmentDTO::fromArray($data));
    }
}

<?php

namespace App\Filament\Resources;

use App\Domain\Department\Services\DepartmentService;
use App\Filament\Concerns\HandlesDomainExceptions;
use App\Filament\Resources\DepartmentResource\Pages;
use App\Models\Department;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * "Manage settings" (ADR-0001 §3) — Department is the one piece of
 * system-wide reference/lookup data that exists today (ADR-0001 §4,
 * DB Design §3.1). Every mutation goes through DepartmentService, which
 * is also what stops an admin from deleting a department out from under
 * programs that still reference it (a clean ConflictException instead of
 * a raw FK violation).
 */
class DepartmentResource extends Resource
{
    use HandlesDomainExceptions;

    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Administration';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('code')
                ->required()
                ->maxLength(20)
                ->unique(ignoreRecord: true),
            TextInput::make('name')
                ->required()
                ->maxLength(150)
                ->unique(ignoreRecord: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('programs_count')->counts('programs')->label('Programs'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->action(fn (Department $record) => static::runOrNotify(
                        fn () => app(DepartmentService::class)->delete($record)
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}

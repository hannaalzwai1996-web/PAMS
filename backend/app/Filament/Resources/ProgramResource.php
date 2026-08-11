<?php

namespace App\Filament\Resources;

use App\Domain\Program\Models\Program;
use App\Filament\Resources\ProgramResource\Pages;
use App\Support\Enums\ProgramStatus;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * "Display data" (ADR-0001 §3): read-only oversight for the System
 * Administrator. Programs are authored by Program Coordinators through
 * the REST API (BR-5) — this resource intentionally has no create/edit/
 * delete pages or actions, and no Service to call, since it performs no
 * mutations at all.
 */
class ProgramResource extends Resource
{
    protected static ?string $model = Program::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Oversight';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('department.name')->label('Department')->sortable(),
                TextColumn::make('level')->badge(),
                BadgeColumn::make('status')
                    ->formatStateUsing(fn (ProgramStatus $state) => str($state->value)->title())
                    ->colors([
                        'gray' => fn ($state) => $state === ProgramStatus::Draft,
                        'warning' => fn ($state) => $state === ProgramStatus::Submitted,
                        'success' => fn ($state) => $state === ProgramStatus::Approved,
                    ]),
                TextColumn::make('coordinators_count')->counts('coordinators')->label('Coordinators'),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    ProgramStatus::Draft->value => 'Draft',
                    ProgramStatus::Submitted->value => 'Submitted',
                    ProgramStatus::Approved->value => 'Approved',
                ]),
            ])
            ->actions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrograms::route('/'),
            'view' => Pages\ViewProgram::route('/{record}'),
        ];
    }
}

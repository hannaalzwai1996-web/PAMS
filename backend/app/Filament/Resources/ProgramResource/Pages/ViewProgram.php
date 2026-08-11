<?php

namespace App\Filament\Resources\ProgramResource\Pages;

use App\Filament\Resources\ProgramResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

/**
 * Read-only by design (ADR-0001 §3 "Display data") — Program authorship
 * belongs to the Program Coordinator via the REST API, not the admin
 * panel. No header actions: nothing here is editable or deletable.
 */
class ViewProgram extends ViewRecord
{
    protected static string $resource = ProgramResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            TextEntry::make('code'),
            TextEntry::make('name'),
            TextEntry::make('department.name')->label('Department'),
            TextEntry::make('level')->badge(),
            TextEntry::make('status')->badge()->formatStateUsing(fn ($state) => $state->value),
            TextEntry::make('duration_years')->label('Duration (years)'),
            TextEntry::make('current_version_no')->label('Version'),
            TextEntry::make('description')->columnSpanFull(),
            TextEntry::make('coordinators.name')->label('Coordinators')->listWithLineBreaks(),
            TextEntry::make('objectives_count')->label('Program Objectives')->state(fn ($record) => $record->objectives()->count()),
            TextEntry::make('learning_outcomes_count')->label('Learning Outcomes')->state(fn ($record) => $record->learningOutcomes()->count()),
        ]);
    }
}

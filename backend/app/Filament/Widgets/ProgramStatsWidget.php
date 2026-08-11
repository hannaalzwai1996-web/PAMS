<?php

namespace App\Filament\Widgets;

use App\Domain\Program\Services\ProgramOverviewService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * "Monitor system" (ADR-0001 §3). Every number here comes from
 * ProgramOverviewService::stats().
 */
class ProgramStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $stats = app(ProgramOverviewService::class)->stats();

        return [
            Stat::make('Total Programs', $stats['total']),
            Stat::make('Draft', $stats['draft'])->color('gray'),
            Stat::make('Submitted', $stats['submitted'])->color('warning'),
            Stat::make('Approved', $stats['approved'])->color('success'),
        ];
    }
}

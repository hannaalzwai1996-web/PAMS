<?php

namespace App\Filament\Widgets;

use App\Domain\User\Services\UserService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * "Monitor system" (ADR-0001 §3). Every number here comes from
 * UserService::stats() — the widget only renders, it doesn't decide what
 * counts as "active" or how to group by role.
 */
class UserStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $stats = app(UserService::class)->stats();

        $byRole = collect($stats['by_role'])
            ->map(fn (int $count, string $role) => str($role)->replace('_', ' ')->title()." : {$count}")
            ->implode(' · ');

        return [
            Stat::make('Total Users', $stats['total']),
            Stat::make('Active Users', $stats['active'])
                ->description("{$stats['inactive']} inactive")
                ->color('success'),
            Stat::make('By Role', $byRole !== '' ? $byRole : '—')
                ->color('gray'),
        ];
    }
}

<?php

namespace App\Filament\Pages;

use App\Support\Services\SystemHealthCheckService;
use Filament\Actions\Action;
use Filament\Pages\Page;

/**
 * A custom Page (not a Resource) for the "Monitor system" capability
 * (ADR-0001 §3) — everything shown here comes from
 * SystemHealthCheckService::check(); this class only mounts it and wires
 * a refresh button.
 */
class SystemHealth extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'System Health';

    protected static string $view = 'filament.pages.system-health';

    /**
     * @var array<string, array{status: string, message: string}>
     */
    public array $checks = [];

    public function mount(): void
    {
        $this->refreshChecks();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action('refreshChecks'),
        ];
    }

    public function refreshChecks(): void
    {
        $this->checks = app(SystemHealthCheckService::class)->check();
    }
}

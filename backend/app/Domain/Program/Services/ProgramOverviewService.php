<?php

namespace App\Domain\Program\Services;

use App\Domain\Program\Repositories\Contracts\ProgramRepositoryInterface;
use App\Support\Enums\ProgramStatus;

/**
 * Read-only aggregate stats for the "Monitor system" admin dashboard
 * (ADR-0001 §3). Deliberately not part of a full ProgramService — that's
 * separate future work covering the actual Program workflow (create,
 * submit, approve, version).
 */
class ProgramOverviewService
{
    public function __construct(private readonly ProgramRepositoryInterface $programs) {}

    /**
     * @return array{total: int, draft: int, submitted: int, approved: int}
     */
    public function stats(): array
    {
        $all = $this->programs->all();

        return [
            'total' => $all->count(),
            'draft' => $all->where('status', ProgramStatus::Draft)->count(),
            'submitted' => $all->where('status', ProgramStatus::Submitted)->count(),
            'approved' => $all->where('status', ProgramStatus::Approved)->count(),
        ];
    }
}

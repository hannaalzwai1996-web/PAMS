<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Program\Services\ProgramService;
use App\Http\Resources\Api\V1\ProgramResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Authorization is already resolved by the `can:` route middleware
 * (ProgramPolicy) before this method runs (routes/api/v1/programs.php) —
 * per ADR-0001 §2, this only delegates. The per-role filtering (all
 * programs vs. just the caller's assigned ones) lives in
 * ProgramService::list(), not here.
 */
class ProgramController extends Controller
{
    public function __construct(private readonly ProgramService $programs) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return ProgramResource::collection($this->programs->list($request->user()));
    }
}

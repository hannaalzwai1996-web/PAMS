<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Department\Services\DepartmentService;
use App\Http\Resources\Api\V1\DepartmentResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Read-only reference data for the SPA — needed by the Program create/edit
 * form's department picker (P0.1). Department itself is still only
 * mutated through Filament (DepartmentService, unchanged); this adds
 * nothing beyond a listing endpoint for what already exists.
 */
class DepartmentController extends Controller
{
    public function __construct(private readonly DepartmentService $departments) {}

    public function index(): AnonymousResourceCollection
    {
        return DepartmentResource::collection($this->departments->list());
    }
}

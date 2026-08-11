<?php

namespace App\Support\Enums;

/**
 * Program Specification lifecycle status (SRS FR-PROG-03/05/06). Only
 * `Draft` is relevant to the current codebase (ProgramObjectiveService
 * blocks mutation once a program leaves it) — the transitions themselves
 * belong to the future Program workflow module.
 */
enum ProgramStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
}

<?php

namespace App\Support\Enums;

/**
 * Provenance of a PO-PLO Matrix cell — see
 * docs/architecture/0002-po-plo-matrix-engine.md §1 (BR-MTX-2..4). A
 * `Manual` cell can never be silently overwritten by a later
 * `PoPloMatrixService::generate()` run.
 */
enum MatrixEntrySource: string
{
    case Auto = 'auto';
    case Manual = 'manual';
}

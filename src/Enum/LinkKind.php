<?php

namespace App\Enum;

/**
 * Semantic type of directed link between two Refs.
 *
 * Links form a directed graph (source → target). LinkKind defines *how* two
 * objects are related, complementing *which* objects via source_id/target_id.
 * Uniqueness is (source_id, target_id, kind), so the same pair may be connected
 * by multiple kinds at once (e.g. a document attached to a task and referenced
 * in its description).
 *
 * Convention: source is the active/owning side, target is the passive/owned side.
 * Mutual relations are not a separate kind — they are the presence of both
 * (A, B, kind) and (B, A, kind) rows, derived at read time.
 *
 * Max length: 32 ch.
 */
enum LinkKind: string
{
    /**
     * Source owns target. Deleting source cascades to target.
     *
     * Example: a file attached to a task goes away when the task is deleted.
     */
    case Ownership = 'ownership';

    /**
     * Target was produced from source. Records provenance only — both sides
     * live independently after creation.
     *
     * Example: a Secret generated from a task remains even if the task is deleted.
     */
    case Derivation = 'derivation';

    /**
     * Soft pointer: source refers to target without owning or producing it.
     *
     * Example: a Note that mentions a Task in its body.
     */
    case Reference = 'reference';
}

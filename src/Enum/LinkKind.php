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
     * Artifact attached to its owner. Target's lifecycle follows source.
     * Created explicitly by the user.
     *
     * Examples: Task → Document.
     */
    case Attachment = 'attachment';

    /**
     * Target was produced from source. Records provenance; both sides then
     * live independently. Always created by the system.
     *
     * Example: Reminder generated from a Task's deadline.
     */
    case Derivation = 'derivation';

    /**
     * Soft pointer: source refers to target without owning or producing it.
     * Covers both parser-detected and user-added links. If the product later
     * needs to split auto vs manual, introduce a separate Mention case rather
     * than overloading this one.
     *
     * Example: a Note mentions a Task in its body.
     */
    case Reference = 'reference';
}

<?php

namespace App\Component\Searcher\Dto;

/**
 * Base marker interface for DTO classes that support searching, filtering, and sorting.
 *
 * This is an internal interface—do NOT implement it directly in your DTO classes.
 * Instead, implement one of its child interfaces that matches your entity type.
 *
 * Child interfaces define the specific searchable fields for each domain via the #[Searchable]
 * attribute on DTO properties. The Searcher component uses these interfaces and attributes
 * to build query definitions, validate filters, and resolve field names.
 *
 * @see \App\Component\Searcher\SearcherInterface
 * @see \App\Component\Searcher\Attribute\Searchable
 *
 * @internal
 */
interface SearchableInterface
{
}

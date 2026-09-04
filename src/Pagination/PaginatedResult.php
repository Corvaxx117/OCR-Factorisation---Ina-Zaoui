<?php

declare(strict_types=1);

namespace App\Pagination;

/**
 * @template T
 */
final class PaginatedResult
{
    public const DEFAULT_PER_PAGE = 20;

    /**
     * @param list<T> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $total,
    ) {
    }

    public function getTotalPages(): int
    {
        return (int) ceil($this->total / $this->perPage);
    }
}
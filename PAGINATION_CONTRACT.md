# Pagination Contract

## Overview

Pagination follows **offset/limit** pattern with validation and immutable DTOs.

**Future roadmap:**
- Query filters will be added to `PaginationRequestDto` in a future iteration
- MeiliSearch integration will follow, allowing hybrid search + pagination
- Contract is designed to remain stable during these additions

## Components

### 1. Request DTO: `PaginationRequestDto`

Input from HTTP query parameters, validated before repository access.

```php
// Defaults: offset=0, limit=10, max limit 100
$pagination = new PaginationRequestDto(
    offset: 20,
    limit: 10
);
```

**Validation:**
- `offset >= 0`
- `limit > 0 and limit <= 100`

**Future extensions:** query filters will be added here in next iteration.

---

### 2. Response DTO: `PaginationDto`

Metadata returned in API response envelope.

```php
new PaginationDto(
    offset: 20,
    limit: 10,
    total: 150,     // total items available
    count: 10       // items in this response
);
```

**Invariant:** `count <= limit` and `offset + count <= total`

---

### 3. Paginated Response: `PaginatedResponseDto`

Generic, type-safe wrapper for paginated API responses.

```php
new PaginatedResponseDto(
    data: $tasks,           // T[]
    pagination: $pagination // PaginationDto
);
```

**JSON shape:**
```json
{
  "data": [...],
  "pagination": {
    "offset": 20,
    "limit": 10,
    "total": 150,
    "count": 10
  }
}
```

---

## Usage Pattern

### In Controller

```php
use App\Dto\PaginationRequestDto;
use App\Dto\PaginatedResponseDto;
use App\Service\PaginationService;

class TaskController
{
    public function __construct(
        private TaskRepository $repository,
        private PaginationService $paginationService,
    ) {}

    #[Route('/api/tasks', methods: ['GET'])]
    public function list(
        #[MapQueryString] PaginationRequestDto $pagination,
    ): JsonResponse {
        [$tasks, $total] = $this->repository->findPaginated($pagination);

        $paginationDto = $this->paginationService->createPagination(
            $pagination,
            $total,
            count($tasks)
        );

        return $this->json(
            new PaginatedResponseDto($tasks, $paginationDto)
        );
    }
}
```

### In Repository

Implement `PaginableRepositoryInterface` using `PaginationTrait`:

```php
use App\Contract\PaginableInterface;use App\Dto\PaginationRequestDto;use App\Repository\PaginationTrait;

class TaskRepository extends ServiceEntityRepository implements PaginableInterface
{
    use PaginationTrait;

    public function findPaginated(PaginationRequestDto $pagination): array
    {
        $qb = $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC');

        [$query, $total] = $this->applyPagination($qb, $pagination);

        return [$query->getResult(), $total];
    }
}
```

---

## Query Parameters

```
GET /api/tasks?offset=20&limit=10
```

- Both parameters optional
- Invalid values trigger validation errors (422 Unprocessable Entity)
- Defaults: `offset=0, limit=10`

---

## Design Notes

- **Immutable DTOs:** all pagination objects are `readonly` for safety
- **Tuple returns:** repositories return `[items, total]` for clarity
- **Doctrine-first:** `PaginationTrait` is optimized for ORM queries
- **Extensible:** contract prepared for query filters and MeiliSearch in future iterations without breaking changes

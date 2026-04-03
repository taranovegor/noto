# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

All commands are run inside Docker containers via the Makefile. The app uses RoadRunner (not PHP-FPM).

```bash
make start            # Start containers (Docker Compose)
make stop             # Stop containers
make build            # Build Docker images

make test             # Run all tests
make test-unit        # Run unit tests only
make test-integration # Run integration tests only
make test-coverage    # Generate HTML coverage report in ./coverage/

make cs               # Dry-run PHP CS Fixer (check only)
make cs-fix           # Auto-fix code style
make phpstan          # Static analysis (level 6)
```

To run a single test file or method, exec into the app container and run:
```bash
php bin/phpunit tests/Unit/Entity/TaskTest.php
php bin/phpunit --filter testMethodName
```

## Architecture

**Noto** is a Symfony 8 microservice for structured task/project management, exposed as a JSON REST API.

### Architectural Style

Layered architecture with domain grouping inside service layer:

- **Controller** — thin, handles HTTP only. No business logic.
- **Service** — business logic, grouped by domain (`Service/Task/`, `Service/Relation/`, etc.)
- **Repository** — persistence, one per entity. Flat, no nesting.
- **Entity** — Doctrine ORM entities. Flat, no inheritance hierarchy.
- **Dto** — request/response data transfer objects, grouped by domain.
- **Enum** — PHP backed enums for finite value sets.

### Directory Structure

```
src/
├── Component/          # Reusable, self-contained components (Searcher, etc.)
│   └── Searcher/       # Generic search/filter/sort component
├── Controller/Api/     # One controller per domain (TaskController, ProjectController)
├── Dto/
│   ├── Task/           # CreateTaskDto, UpdateTaskDto, TaskResponseDto
│   └── Project/        # CreateProjectDto, ProjectResponseDto
├── Entity/             # Ref, Link, Task, Project (flat, no subdirectories)
├── Enum/               # TaskStatus, TaskPriority, RefType, ProjectStatus
├── EventListener/      # Doctrine lifecycle hooks (timestamps, etc.)
├── Repository/         # One repository per entity (flat)
└── Service/
    ├── Task/           # TaskManager, TaskCodeGenerator
    ├── Project/        # ProjectManager
    └── Relation/       # RelationManager, EntityResolver
```

**Component/** — Directory for self-contained, reusable components that serve multiple domains. Each component is domain-agnostic and can be used across services. Example: `Searcher` handles filtering, sorting, and pagination for any entity.

### Naming Conventions

- Entities: singular nouns (`Task`, `Project`, `Ref`)
- Enums: `{Entity}{Field}` pattern (`TaskStatus`, `TaskPriority`, `RefType`)
- Services: `{Entity}Manager` for CRUD logic, specific names for specific jobs (`TaskCodeGenerator`)
- DTOs: `{Action}{Entity}Dto` for input (`CreateTaskDto`), `{Entity}ResponseDto` for output
- Controllers: `{Entity}Controller`
- Repositories: `{Entity}Repository`

### Request Flow

`HTTP → RoadRunner → Symfony Kernel → Controller → Service → Repository → Doctrine → PostgreSQL`

- Controllers use PHP attributes for routing and OpenAPI docs (NelmioApiDoc)
- All routes are prefixed with `/api` (configured in `config/routes.yaml`)
- Services handle business logic; repositories handle persistence
- Flusher (src/Service/Flusher.php) is injected wherever a Doctrine flush is needed, keeping EntityManager out of controllers

### Key Entities

- **`Task`** — work item with `status` (`TaskStatus`), `priority` (`TaskPriority`), deadline, note. Belongs to `Project`. Has human-readable code like `PRJ-42`.
- **`Project`** — container for tasks. Has unique 3-char `prefix` and `taskCounter` for code generation.
- **`Ref`** — registry entry for polymorphic relationships. Stores `id` + `RefType`.
- **`Link`** — connection between two `Ref` entries (source → target). Relation type auto-generated from entity class names.

All entities use UUID v7 as primary keys.

### Polymorphic Relations (Ref + Link)

Every entity that participates in cross-entity linking implements `Linkable` interface and has a `OneToOne` to `Ref`. Links reference `Ref` entries with real foreign keys, ensuring referential integrity. `EntityResolver` service resolves `Ref` back to concrete entity by type.

### Enums

- `TaskStatus`: todo, in_progress, done
- `TaskPriority`: low, medium, high, urgent
- `ProjectStatus`: active, archived
- `RefType`: task, project, recipe, note, reminder

### Testing

- **Unit tests**: `tests/Unit/` — services, DTOs, entities in isolation
- **Integration tests**: `tests/Integration/` — full HTTP stack with Alice fixtures (`fixtures/`)
- Test environment uses `.env.test` with separate database
- PHPUnit fails on any deprecation, notice, or warning (`phpunit.dist.xml`)

---

## Search & Filtering

List endpoints support **filtering**, **sorting**, and **pagination** via query parameters. The search system is implemented in `src/Component/Searcher/` and powered by a DTO-based resolver (`SearchDtoValueResolver`).

### Quick Example

```
GET /api/tasks?filter[status]=in:backlog,in_progress&sort=-id&limit=20&offset=0
```

### Parameters

| Parameter      | Syntax                        | Default              | Example                                                             |
|----------------|-------------------------------|----------------------|---------------------------------------------------------------------|
| **Pagination** | `limit=N&offset=N`            | `limit=20, offset=0` | `limit=10&offset=5`                                                 |
| **Sorting**    | `sort=field` or `sort=-field` | (none)               | `sort=-priority;deadline` (DESC by priority, then ASC by deadline)  |
| **Filtering**  | `filter[field]=op:value`      | (none)               | `filter[status]=in:todo,done&filter[deadline]=gte:2025-01-01`       |

### Operators & Syntax

- `eq:` or omit operator — equality
- `in:value1,value2` — matches one of values (comma-separated)
- `not_in:value1,value2` — excludes values (comma-separated)
- `gte:`, `lte:`, `gt:`, `lt:`, `neq:` — comparisons
- Multiple conditions for same field: `filter[status]=in:todo,backlog;neq:done` (semicolon-separated)
- Multiple sort fields: `sort=-priority;deadline` (semicolon-separated)

See `src/Component/Searcher/Enum/FilterOperator.php` for all supported operators.

### Response

```json
{
  "data": [ { "id": "...", "name": "..." } ],
  "pagination": { "total": 73, "limit": 20, "offset": 0 }
}
```

To add a new filterable/sortable field to an endpoint, update the corresponding `SearchDefinition` (e.g., `TaskSearchDefinition`) and add the field to the DTO's `Searchable` attribute.

---

## Working with Claude Code

### Diagnosing vs. Fixing

When you encounter a problem (test failures, unexpected behavior, unclear architecture), **stop and ask for clarification instead of attempting multiple fixes**:

- **Diagnose briefly** — understand what's wrong
- **Ask or suggest** — propose a hypothesis or ask what you should do next
- **Wait for answer** — let the user confirm the approach
- **Fix once** — implement the solution with confidence, not trial-and-error

This applies to:
- Test failures with unclear root causes
- Architectural decisions with multiple valid solutions
- Unexpected behavior in external systems (Serializer, Validator, etc.)
- Ambiguous requirements

Reason: Saves tokens and ensures the fix matches your intent, not my guess.

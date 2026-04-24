# noto
Personal knowledge microservice for structured data storage

## Requirements

- Docker and Docker Compose
- Make (for convenient command execution)
- Git

## Quick Start

### Development Environment

1. **Clone the repository**
   ```bash
   git clone git@github.com:taranovegor/noto.git
   cd noto
   ```

2. **Start the application**
   ```bash
   make start
   ```
   This will:
   - Build and start Docker containers
   - Initialize the PostgreSQL database
   - Start the Symfony application in development mode

3. **Access the application**
   - Web: http://localhost:8080

### Production Setup

The project is optimized for production deployment using pre-built Docker images. Set `DOCKER_COMPOSE_ENV` environment variable as `prod`.

## Architecture

### Technology Stack

- **Framework**: Symfony 8.0
- **Language**: PHP >= 8.4
- **Database**: PostgreSQL 18
- **Application Server**: RoadRunner (via baldinof/roadrunner-bundle)
- **ORM**: Doctrine ORM with migrations
- **Container Registry**: GitHub Container Registry (ghcr.io)

### Examples

```bash
# Start the application
make start

# View logs in real-time
make logs

# Rebuild images
make build

# Stop the application
make stop
```

## Environment Configuration

The application uses Symfony's environment management system:

### Environment Files

- **`.env`** - Default configuration (tracked in git)
- **`.env.local`** - Local overrides (not tracked, use for sensitive data)
- **`.env.dev`** - Development-specific configuration (tracked)
- **`.env.dev.local`** - Development overrides (not tracked)

### Key Variables

```env
# Application
APP_ENV=dev                    # dev or prod
APP_SECRET=your-secret-key     # Symfony app secret
APP_SHARE_DIR=var/share        # Shared directory for uploads

# Routing
DEFAULT_URI=http://localhost   # Default application URI

# Database
DATABASE_URL=postgresql://...  # Doctrine DSN

# Docker
DOCKER_COMPOSE_ENV=dev         # dev or prod (switches between compose files)
```

## Database Management

### Migrations

Run Doctrine migrations to initialize the database schema:

```bash
docker-compose exec app php bin/console doctrine:migrations:migrate
```

### Test Database

Before running integration tests for the first time, create the test database (one-time setup):

```bash
make db-init-test
```

## Testing

```bash
make test             # Run all tests
make test-unit        # Unit tests only
make test-integration # Integration tests only
make test-coverage    # Generate HTML coverage report (./coverage/index.html)
```

## Code Style & Static Analysis

```bash
make cs       # Check code style (dry-run)
make cs-fix   # Auto-fix code style
make phpstan  # Static analysis (level 6)
```

## API Documentation

Interactive API documentation (Swagger UI) is available at:

```
http://localhost:8080/api/doc
```

## MCP Server

The application exposes an MCP (Model Context Protocol) server for programmatic task and project management via HTTP:

```
http://localhost:8080/_mcp
```

The server provides resources for reading and tools for creating/updating tasks and projects.

## Monitoring

### View Application Logs

```bash
make logs
```

### Access Container Shell

```bash
docker-compose exec app sh
```

### Container won't start

```bash
# Check logs
make logs

# Rebuild containers
make build && make start
```

## License

[PolyForm Noncommercial 1.0.0](LICENSE.md)

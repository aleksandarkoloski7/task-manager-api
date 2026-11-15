# Task Manager API


A RESTful task management API built with **Laravel** and **PostgreSQL**: token authentication, per-user authorization, filtering and pagination, an external API integration, automated tests and CI. Runs in Docker.

## Features

- Token authentication with Laravel Sanctum (register, login, logout)
- Tasks CRUD with categories; every user only sees their own data (Policies)
- Filtering by status, category and due date; search; sorting; pagination
- Public holidays endpoint backed by the [Nager.Date](https://date.nager.at) API, with caching, timeout/retry and error handling
- Validation with Form Requests, JSON output with API Resources
- Feature tests (PHPUnit), including authorization and the external API (`Http::fake`)
- OpenAPI documentation generated with Scramble
- GitHub Actions CI (code style + tests)

## Tech stack

PHP 8.4, Laravel, PostgreSQL 16, Docker Compose, Sanctum, PHPUnit, Laravel Pint, GitHub Actions.

## Getting started

Requires Docker.

```bash
git clone https://github.com/aleksandarkoloski7/task-manager-api.git
cd task-manager-api
cp .env.example .env          # PowerShell: Copy-Item .env.example .env
docker compose run --rm app composer install
docker compose run --rm app php artisan key:generate
docker compose up -d --build
docker compose exec app php artisan migrate --seed
```

The API runs at `http://localhost:8000`. The seeder creates a demo user: `test@example.com` / `password`.

Database settings (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) are read from `.env` by both Laravel and Docker Compose.

## Running tests

Tests use a separate PostgreSQL database, so development data is never touched.

```bash
docker compose exec db sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -c "CREATE DATABASE task_manager_testing;"'
docker compose exec app php artisan test
```

## API documentation

- Interactive docs: `http://localhost:8000/docs/api`
- OpenAPI specification: [`openapi.json`](openapi.json) (importable into Postman)

## Endpoints

| Method | Path | Auth | Description |
|---|---|---|---|
| POST | `/api/register` | no | Create account, returns token |
| POST | `/api/login` | no | Log in, returns token (5 attempts/min) |
| POST | `/api/logout` | yes | Revoke current token |
| GET | `/api/user` | yes | Current user |
| GET | `/api/tasks` | yes | List tasks (filters, search, sort, pagination) |
| POST | `/api/tasks` | yes | Create task |
| GET | `/api/tasks/{id}` | yes | Show task |
| PUT/PATCH | `/api/tasks/{id}` | yes | Update task |
| DELETE | `/api/tasks/{id}` | yes | Delete task |
| GET | `/api/holidays?country=DE&year=2026` | yes | Public holidays (via Nager.Date) |

Query parameters for `GET /api/tasks`: `status`, `category_id`, `search`, `due_before`, `due_after`, `sort` (`created_at`, `due_date`, `title`, `status`), `direction` (`asc`, `desc`), `per_page` (max 50), `page`.

### Example

```bash
# log in
curl -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'

# list tasks (replace TOKEN)
curl "http://localhost:8000/api/tasks?status=pending&sort=due_date&direction=asc" \
  -H "Accept: application/json" -H "Authorization: Bearer TOKEN"
```

## Design decisions

- **Form Requests, API Resources and Policies** keep controllers thin: validation, output format and authorization each live in one place.
- **Authorization:** tasks are always created through `$request->user()->tasks()`, `user_id` is never accepted from input, and a category can only be used by its owner.
- **Search** is grouped (`user_id = ? AND (title ILIKE ... OR description ILIKE ...)`) so it cannot leak other users' tasks; a test covers this.
- **Sorting** uses an allow-list and `per_page` is capped.
- **External API:** isolated in `HolidayService`, configured via `config/services.php`, cached for 24 hours, 5 s timeout with retry. Upstream failures return `502`, unsupported countries `404`. Tests never call the real service.
- **Tests run on PostgreSQL** (not SQLite) so behaviour such as `ILIKE` matches production.

## How I used AI

<!-- Edit this so it matches how you really worked. -->
I used an AI assistant as a pair-programming tutor: to plan the project in milestones, scaffold boilerplate, and explain framework concepts. I ran and verified every step myself with automated tests and manual requests, and debugged problems from application logs and error messages (for example a namespace typo and a wrong external API base URL, both traced and fixed by me). I reviewed generated code before committing it, and authorization and validation behaviour is covered by tests.

## CI

GitHub Actions runs code style checks (Laravel Pint) and the test suite against PostgreSQL on every push and pull request. Database credentials for CI are stored as GitHub Actions secrets.

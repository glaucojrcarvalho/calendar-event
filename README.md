# Calendar Event API

[![Laravel Tests](https://github.com/glaucojrcarvalho/calendar-event/actions/workflows/laravel.yml/badge.svg)](https://github.com/glaucojrcarvalho/calendar-event/actions/workflows/laravel.yml)

A REST API for managing calendar events, built with **Laravel 10** and **PHP 8.1** following **Domain-Driven Design (DDD)** principles. The project separates concerns across domain, application, infrastructure, and HTTP layers, with a complete test suite covering all layers.

---

## Features

- Create, read, update, and delete calendar events
- Recurring event support with four frequencies: **daily**, **weekly**, **monthly**, **yearly**
- Automatic overlap detection — prevents double-booking
- Date range filtering on the list endpoint
- ISO 8601 date format throughout

---

## Architecture

The codebase follows a layered DDD approach:

```
src/app/
├── Domain/            # Core business logic
│   ├── Entities/      # Event entity with validation and overlap detection
│   ├── Repositories/  # EventRepository interface (contract)
│   └── Services/      # EventRecurringService — generates recurring occurrences
├── Application/       # Use cases: CreateEvent, UpdateEvent, ListEvents, DeleteEvent
├── Infrastructure/    # DatabaseEventRepository — Eloquent implementation
└── Http/              # Controllers, Form Requests, UuidExists middleware
```

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 10 |
| Language | PHP 8.1 |
| Database | MySQL (production) / SQLite in-memory (tests) |
| Testing | PHPUnit 10 |
| CI | GitHub Actions |

---

## API Reference

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/events` | Create a new event |
| `GET` | `/api/events` | List events (paginated) |
| `PUT` | `/api/events/{uuid}` | Update an event |
| `DELETE` | `/api/events/{uuid}` | Delete an event |

### Query parameters — `GET /api/events`

| Parameter | Format | Description |
|---|---|---|
| `startDate` | ISO 8601 | Return events starting on or after this date |
| `endDate` | ISO 8601 | Return events ending on or before this date |

### Request body — `POST /api/events`

```json
{
  "title": "Team standup",
  "description": "Daily sync (optional)",
  "startDate": "2024-06-01T09:00:00+00:00",
  "endDate": "2024-06-01T09:30:00+00:00"
}
```

#### With recurring pattern (optional fields)

```json
{
  "title": "Weekly planning",
  "startDate": "2024-06-03T10:00:00+00:00",
  "endDate": "2024-06-03T11:00:00+00:00",
  "recurringPattern": true,
  "frequency": "weekly",
  "repeatUntil": "2024-08-26T10:00:00+00:00"
}
```

`frequency` accepts: `daily` | `weekly` | `monthly` | `yearly`

---

## Installation

**Prerequisites:** PHP 8.1+, Composer, MySQL

```bash
git clone https://github.com/glaucojrcarvalho/calendar-event.git
cd calendar-event/src

composer install
cp .env.example .env
php artisan key:generate

# Configure DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD in .env
php artisan migrate

php artisan serve
```

---

## Running Tests

Tests run against an **in-memory SQLite database** — no external database setup required.

**Prerequisite:** the `pdo_sqlite` PHP extension must be installed.

```bash
# Ubuntu / Debian
sudo apt-get install php8.1-sqlite3

# macOS (Homebrew)
brew install php
```

```bash
cd src
vendor/bin/phpunit --testdox
```

To run only the pure unit tests (no database required):

```bash
vendor/bin/phpunit --testsuite Unit --filter "EventRecurringService|Overlaps|Validation|CreateEventUseCase|UpdateEventUseCase|DeleteEventUseCase|ListEventUseCase"
```

### Test coverage

| Suite | What is covered |
|---|---|
| Unit | `Event` entity validation, overlap detection algorithm, `EventRecurringService` (all four frequencies, boundary conditions), all use cases (Create, Update, Delete, List), `DatabaseEventRepository` (CRUD + date filtering) |
| Feature | All API endpoints — happy paths, validation errors (422), overlap errors (500), UUID not found (404), recurring event creation and update, date range filtering |

---

## OpenAPI Specification

A Swagger/OpenAPI 3.0 spec is available at [`docs/Laravel_Test_Swagger.yaml`](docs/Laravel_Test_Swagger.yaml).

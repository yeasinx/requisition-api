# 06 — Development, Testing & Operations

This guide provides step-by-step instructions for local setup, running tests, database management, and maintenance workflows.

---

## 1. Local Environment Prerequisites

- **PHP**: `^8.3` (with extensions: `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `curl`)
- **Composer**: `^2.5`
- **Database**: SQLite (default for development/testing) or MySQL 8.0+ / PostgreSQL 15+

---

## 2. Step-by-Step Installation

```bash
# 1. Clone the repository
git clone <repository-url>
cd requisition-api

# 2. Install PHP dependencies
composer install

# 3. Setup environment configuration
cp .env.example .env
php artisan key:generate

# 4. Run database migrations & seed initial Super Admin
php artisan migrate --seed

# 5. Start the local development server
php artisan serve
```

The API will be available at `http://127.0.0.1:8000`.

---

## 3. Seeded Accounts & Bootstrap Credentials

Running `php artisan db:seed` provisions the initial administrator:

| Field | Value |
| :--- | :--- |
| **Email** | `admin@company.com` |
| **Password** | `password123` |
| **Role** | `SUPER_ADMIN` |
| **Employee ID**| `EMP-0001` |
| **Designation**| `Super Administrator` |

### Post-Installation Recommended Steps:
1. Log in with `admin@company.com` via `POST /api/login`.
2. Provision staff accounts for PM, CEO, Controller, Accounts, and HR via `POST /api/users`.
3. Set designated approver user IDs via `PUT /api/settings`.

---

## 4. Running the Test Suite

The project includes unit and feature test suites with PHPUnit and Mockery.

```bash
# Run all tests
php artisan test

# Run a specific test class
php artisan test tests/Unit/WorkflowServiceTest.php

# Run with test filtering
php artisan test --filter=test_regular_employee_starts_at_approver_1
```

### Writing New Tests:
- Place unit tests under `tests/Unit/`.
- Place endpoint / integration tests under `tests/Feature/`.
- Use Eloquent factories located in `database/factories/` (`UserFactory`, `RequisitionFactory`, etc.) for generating test fixtures.

---

## 5. Code Quality & Formatting (Laravel Pint)

The codebase strictly follows Laravel / PSR-12 coding conventions. Run Laravel Pint to check or format code:

```bash
# Format code automatically
./vendor/bin/pint

# Check code formatting without modifying files
./vendor/bin/pint --test
```

---

## 6. Useful Artisan Commands

```bash
# Wipe and re-run all migrations with seeding
php artisan migrate:fresh --seed

# Clear application cache (useful after changing settings or config)
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# List all registered API routes
php artisan route:list --path=api
```

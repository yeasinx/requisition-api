# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Laravel 13 JSON API (PHP 8.3+, Sanctum bearer tokens) for submitting purchase requisitions and moving them through a sequential, multi-tier approval chain. No frontend; all routes live in `routes/api.php` under `auth:sanctum` (except `POST /api/login`).

## Commands

```sh
composer setup                      # install, .env, key, migrate, npm build
php artisan migrate --seed          # seeds SUPER_ADMIN admin@company.com / password123
php artisan serve                   # http://127.0.0.1:8000
composer test                       # config:clear + php artisan test
php artisan test --filter=RequisitionApprovalFilterTest          # one class
php artisan test --filter=test_method_name                       # one method
./vendor/bin/pint                   # format; use --test to check only
```

Tests are PHPUnit (not Pest) feature tests using `RefreshDatabase` against in-memory SQLite; `phpunit.xml` sets `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`, `CACHE_STORE=array`.

## Architecture

**Layering.** Controllers are thin: `Gate::authorize(...)` → FormRequest validation → a service in `app/Services` → an API Resource. FormRequests return `authorize(): true`; authorization lives only in `app/Policies`. Business logic and DB transactions live in services:
- `RequisitionService` — create/update; recomputes line `total_price` and `total_expected_price` server-side (`processItems`), replaces all items on update.
- `WorkflowService` — the approval engine: `getInitialStep`, `getNextStep`, `approve`, `deny`. Each decision writes an immutable `ApprovalStep` audit row and updates the requisition in one transaction.
- `SettingsService` — the singleton `SystemSettings` row that maps each workflow step to a user. Cached under `system_settings` (1h); `updateSettings` clears it. Always read approvers through this service.
- `RequisitionNumberService` — generates `requisition_number` (`REQ-YYYY-NNNN`).

**Workflow.** Steps (`App\Enums\RequisitionStep`) run in fixed order `APPROVER_1 → APPROVER_2 → BUSINESS_CONTROLLER → ACCOUNTS → HR_ADMIN`. The starting step depends on the submitter: the user configured as second approver (CEO) starts at `BUSINESS_CONTROLLER`; the first approver (PM) or roles `ACCOUNTS`/`HR_ADMIN` start at `APPROVER_2`; everyone else at `APPROVER_1`. Approving the last step sets status `APPROVED`; any denial (reason required) sets `DENIED`. Both terminal states set `current_step = null`.

**Approver identity is per-user, not per-role.** Who may act on a step comes from `SystemSettings` user IDs, not from `UserType`. The step → settings-field mapping is repeated in `RequisitionController::index` (list scoping and `approval=pending`), `RequisitionPolicy::view`/`approve`, and `SettingsService::getApproverForStep`. Change all of them together.

**Visibility rules.** A non-admin sees a requisition if they submitted it, it is pending at a step they are assigned to, or they have an `ApprovalStep` on it. `RequisitionController::index` enforces this in the query and `RequisitionPolicy::view` enforces it for single records. Keep the two in sync. `SUPER_ADMIN` sees everything (including `?trashed=only`) but cannot create requisitions. Submitters may edit only before any approval exists, and delete only while `PENDING`. Requisitions and users are soft-deleted.

**Notifications.** Mailables in `app/Mail` are queued after the transaction commits: to the next approver on create/advance, and to the submitter on approve/deny.

**Models** use `#[Fillable([...])]` attributes and a `casts()` method that casts columns to the enums in `app/Enums`. Eager-load `['submittedBy', 'items', 'approvals.actedBy']` before returning a `RequisitionResource`; the resource uses `whenLoaded`.

## Documentation

When behavior or API shape changes, update `docs/wiki/*` (especially `05-API-Reference-and-Examples.md`) and the Bruno collection in `docs/bruno api collection/`. Pushes to `main` that touch `docs/wiki/**` sync automatically to the GitHub wiki (`.github/workflows/sync-wiki.yml`). Commit history keeps feature, test, and docs changes in separate conventional commits (`feat:`, `test:`, `docs:`).

<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

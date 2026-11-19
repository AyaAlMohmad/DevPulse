# DevPulse

### API Uptime & Performance Monitoring for Developers

DevPulse is a lightweight monitoring SaaS designed to help independent developers and startups understand the health of their APIs and microservices. Its goal is simple: make downtime visible, surface performance changes, and notify teams when an endpoint needs attention—without the complexity of a full observability platform.

Built with **Laravel**, **Inertia.js**, **Vue 3**, and **Tailwind CSS**, DevPulse is being developed around a backend-driven monitoring engine and a clear, interactive dashboard.

> **Project status:** Early development. Session authentication, the Vue monitor-management dashboard, and queued manual health checks are implemented. Scheduled checks, charts, live polling, and notifications are still planned; this is not yet a production-ready monitoring service.

## The Problem

An API can be deployed successfully and still become slow or unavailable later. Small teams need an affordable, focused way to answer three questions:

- **Is my service available?**
- **How quickly is it responding?**
- **Will I know when something goes wrong?**

DevPulse is designed to bring those answers into one dashboard.

## MVP Features

### Uptime Monitoring

Create monitors for API endpoints and websites, configure check intervals, and pause or resume monitoring as needed. Scheduled background jobs will check each active endpoint and record its availability and HTTP status.

### Response-Time Tracking

Measure response latency in milliseconds and retain timestamped check results. Interactive charts will make it easier to spot slowdowns and understand how an endpoint performs over time.

### Interactive Dashboard

A responsive dashboard will provide:

- Summary cards for total, up, and down monitors, plus average response time.
- A searchable, filterable monitor list with clear status badges.
- Manual **Check Now** controls.
- Monitor-level response-time charts and recent check history.
- Automatic data refresh through polling, without full-page reloads.

### Downtime Alerts

Notify users when a monitor transitions to a down state through email or a Discord webhook. Transition-based notifications are intended to avoid sending duplicate alerts for every failed check during the same outage.

## Technology Stack

| Layer | Technology | Role |
| --- | --- | --- |
| Backend | Laravel 13 / PHP 8.3+ | Request handling, validation, authorization, and monitoring logic |
| Database | MySQL | Monitor configuration and indexed check history |
| Background processing | Laravel Queues & Scheduler — planned | Recurring checks and asynchronous notifications |
| Frontend | Vue 3 Composition API with `<script setup>` | Reactive pages and reusable UI components |
| Application bridge | Inertia.js 3 | Laravel-driven navigation and Vue page rendering |
| Styling | Tailwind CSS 4 | Responsive dashboard styling |
| Charts | Chart.js or ApexCharts — selection pending | Response-time visualizations |
| Testing | PHPUnit | Backend feature tests and regression coverage |

## Architecture

DevPulse keeps business logic on the backend and presentation concerns in the frontend:

- **Form Requests** validate monitor configuration.
- **Policies** enforce ownership and prevent cross-user access.
- **Actions** encapsulate persistence and business rules.
- **Resources** define explicit JSON response shapes.
- **Eloquent relationships and database indexes** support monitor ownership and chronological check history.
- **Jobs and scheduled tasks**, once implemented, will run checks independently of browser requests.
- **Vue pages and reusable components**, once implemented, will handle dashboard state, forms, filters, and charts.

Monitor endpoints use Laravel session authentication and CSRF protection. Requests that expect JSON receive resources, while browser requests render Inertia pages or redirect after successful mutations. The Vue dashboard includes reusable forms, status badges, server-side filters, pagination, and owner-scoped summary cards. Password reset and email verification are not yet implemented.

## Current Progress

### Implemented

- [x] Monitor and check-history database schemas.
- [x] Monitor create, list, view, update, and delete endpoints.
- [x] Pause and resume endpoints.
- [x] Configurable check intervals: 1, 5, 15, 30, or 60 minutes.
- [x] Request validation and ownership-based authorization.
- [x] Explicit monitor resources and a dedicated save action.
- [x] Backend tests for CRUD, validation, ownership, and state transitions.
- [x] Session authentication, Vue sign-in and registration pages, and an Inertia dashboard.
- [x] Reusable dashboard layout, summary cards, monitor forms, and a paginated table with server-side filters.
- [x] Queued manual health checks, Check Now, and manual result refresh.
- [x] Public-destination validation, DNS pinning, transfer limits, and stale-result protection.

### Next Milestones

- [ ] Password reset and email verification.
- [ ] Response-time charts and automatic refresh.
- [ ] Scheduled health checks.
- [ ] Email or Discord downtime notifications.
- [ ] Data-retention policies and operational hardening.

## Monitoring Semantics

- A newly created monitor starts as **unknown**, not up or down.
- Pausing a monitor preserves its last-known health state.
- Changing a monitor's URL resets its latest health summary while preserving historical check records.
- HTTP 2xx is up. HTTP, DNS, and connection failures are down; destinations blocked by safety rules remain unknown with a safe error category.
- Checks accept public HTTP(S) endpoints on standard ports only, without embedded credentials or fragments. Redirects are not followed.
- DNS resolution is bounded to 5 seconds in a PHP subprocess. HTTP connections have a 3-second connect timeout and a 10-second total timeout.
- The response body is discarded with a 64 KiB limit. Bodies and exception diagnostics are never persisted.
- All resolved addresses are validated, the selected IP is pinned through cURL, proxies are disabled, TLS verification is enabled, and each probe uses a fresh connection.
- Database reservations and worker locks prevent overlapping checks. URL changes and pause/resume invalidate pending work; old results cannot overwrite a new configuration.

### Running Manual Checks

PHP cURL and permission to start PHP subprocesses are required. Apply migrations, then run the dedicated database queue worker:

```sh
php artisan migrate
php artisan queue:work database --queue=monitor-checks --timeout=30
```

In the dashboard, select **Check Now**, then **Refresh results** after the worker processes the job. No scheduler or polling runs yet. If the worker is stopped, a pending reservation expires after five minutes and can be requested again after refreshing. The queue payload contains only a monitor ID and reservation token, not the endpoint URL.

## Development & Verification

Backend tests run against a temporary SQLite in-memory database configured in `phpunit.xml`, separate from the development MySQL database.

Run the backend test suite:

```sh
php artisan test --compact
```

Run the monitor-management tests only:

```sh
php artisan test --compact tests/Feature/MonitorManagementTest.php
```

## Learning Goals

DevPulse also serves as a practical full-stack learning project for developers with a Laravel background:

- Build asynchronous workflows with queues, jobs, and scheduled tasks.
- Design efficient storage for timestamped monitoring data.
- Keep controllers small through validation, policies, actions, and resources.
- Learn Vue reactivity, props, events, and lifecycle hooks through real dashboard interactions.
- Build reusable components and connect frontend state to backend responses.

## Future Direction

Application-level error tracking is a potential expansion beyond the monitoring MVP. It would introduce error-event ingestion, exception grouping, and richer diagnostics. These capabilities are **not part of the current implementation**.

---

**DevPulse — a clearer view of your API's health.**

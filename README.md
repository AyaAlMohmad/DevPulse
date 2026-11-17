# DevPulse

### API Uptime & Performance Monitoring for Developers

DevPulse is a lightweight monitoring SaaS designed to help independent developers and startups understand the health of their APIs and microservices. Its goal is simple: make downtime visible, surface performance changes, and notify teams when an endpoint needs attention—without the complexity of a full observability platform.

Built with **Laravel**, **Inertia.js**, **Vue 3**, and **Tailwind CSS**, DevPulse is being developed around a backend-driven monitoring engine and a clear, interactive dashboard.

> **Project status:** Early development. Monitor-management endpoints, session authentication, Vue sign-in and registration pages, and a protected Inertia dashboard shell are implemented. Monitor-management screens, automated checks, charts, and notifications are still planned; this is not yet a production-ready monitoring service.

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

The current monitor endpoints return JSON using Laravel session authentication and CSRF protection. Sign-in and registration use Inertia with Vue, and authenticated users can access a dashboard shell and sign out. Password reset and email verification are not yet implemented.

## Current Progress

### Implemented

- [x] Monitor and check-history database schemas.
- [x] Monitor create, list, view, update, and delete endpoints.
- [x] Pause and resume endpoints.
- [x] Configurable check intervals: 1, 5, 15, 30, or 60 minutes.
- [x] Request validation and ownership-based authorization.
- [x] Explicit monitor resources and a dedicated save action.
- [x] Backend tests for CRUD, validation, ownership, and state transitions.
- [x] Session authentication, Vue sign-in and registration pages, and an Inertia dashboard shell.

### Next Milestones

- [ ] Password reset and email verification.
- [ ] Reusable dashboard layout, cards, tables, and forms.
- [ ] Response-time charts and automatic refresh.
- [ ] Scheduled health checks and a manual check workflow.
- [ ] Email or Discord downtime notifications.
- [ ] Outgoing-request safeguards, timeout handling, and data-retention policies.

## Monitoring Semantics

- A newly created monitor starts as **unknown**, not up or down.
- Pausing a monitor preserves its last-known health state.
- Changing a monitor's URL resets its latest health summary while preserving historical check records.
- The background-check phase will define successful HTTP status codes, timeout behavior, and redirect handling explicitly.

Because DevPulse will request user-supplied URLs, **SSRF prevention is a requirement before outbound checks are enabled**. URL-format validation alone is not sufficient protection.

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

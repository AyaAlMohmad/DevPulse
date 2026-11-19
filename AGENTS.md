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

## Verified project setup

- Laravel Boost is installed. Bootstrap installation above has been completed; do not repeat it on every task. Generated framework guidelines are in `CLAUDE.md`.
- Run backend verification with `php artisan test --compact`. Tests use a temporary SQLite in-memory database configured in `phpunit.xml`; do not point tests at the development MySQL database.
- Development uses MySQL. Do not replace `.env` or regenerate its application key during setup.
- This workspace uses Git, with `main` tracking `origin/main`. Run `php vendor/bin/pint --dirty --format agent` to format changed PHP files.
- `/dashboard` and browser requests to `/monitors` render the Inertia dashboard. Requests that expect JSON keep resource responses; browser mutations redirect to the dashboard with a session success message. Session authentication, CSRF, and ownership policies apply to both paths.
- Monitor UI includes CRUD, pause/resume, server-side search/status filters, pagination, owner-scoped summary cards, Check Now, and manual Refresh results. Paused monitors are excluded from active health counts and average latest latency. Missing measurements remain null. Password reset, email verification, charts, polling, scheduling, and alerts remain future work.
- Manual checks require PHP cURL and permission to start PHP subprocesses for bounded DNS resolution. Run `php artisan migrate` after pulling the manual-check migration, then run `php artisan queue:work database --queue=monitor-checks --timeout=30`. Checks intentionally use the database connection and dedicated monitor-checks queue, never the sync connection.
- Outbound checks use a forced CurlHandler, public-address validation, CURLOPT_RESOLVE pinning, no proxies, fresh connections, TLS verification, no redirects, default HTTP(S) ports, a 5-second DNS subprocess timeout, 3-second connect timeout, 10-second HTTP timeout, and a 64 KiB discard-only response sink. Do not replace the handler with a stream fallback or weaken these limits.
- Check reservations expire after 300 seconds. Jobs use a per-monitor WithoutOverlapping lock and revalidate their reservation before persisting. URL changes and pause/resume invalidate pending checks. HTTP 2xx is up; blocked targets remain unknown; connection/DNS/HTTP failures are down. Store safe error categories only, never exception text or response bodies.
- Run `php artisan test --compact tests/Feature/MonitorTargetTest.php tests/Feature/MonitorHealthCheckTest.php tests/Feature/ManualMonitorCheckTest.php` for manual-check coverage. Tests fake HTTP and DNS processes; never enable real outbound traffic in these tests.
- Run `php artisan test --compact tests/Feature/MonitorDashboardTest.php` to verify dashboard statistics, filtering, pagination, and the Inertia/JSON contracts.
- Frontend pages use Vue 3 `<script setup>` in `resources/js/Pages`; shared components live in `resources/js/Components` and layouts in `resources/js/Layouts`. Named Laravel URLs are passed through Inertia shared props rather than hardcoded in Vue.
- Run `npm ci` to install frontend dependencies and `npm run build` to verify the production build. `.npmrc` intentionally disables lifecycle scripts; do not weaken this setting.
- Run `php artisan test --compact tests/Feature/AuthenticationTest.php` for authentication checks. These tests use `withoutVite()` so they do not require compiled assets.
- npm audit currently reports a development-toolchain advisory through picomatch (no patched version available when checked). Do not force-downgrade tooling or disable audit to hide it.

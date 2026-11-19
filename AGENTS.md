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
- Outbound checks use a forced CurlHandler, public-address validation, CURLOPT_RESOLVE pinning, no proxies/cookies/browser credentials, fresh connections, TLS verification, and default HTTP(S) ports. Automatic redirects stay disabled; ProbeMonitorUrl follows at most 3 redirects manually, revalidating DNS/IP and pinning on every hop, detecting loops and rejecting HTTPS downgrade. Each DNS subprocess is bounded to 5 seconds; HTTP connect/transfer limits are 3/10 seconds, reduced by a shared 15-second budget checked before and after resolution (a final DNS lookup may overrun that budget by at most its 5-second timeout). The 64 KiB discard-only response-body budget is shared across the chain. Do not replace the handler with a stream fallback or weaken these limits.
- Check reservations expire after 300 seconds. Jobs use a per-monitor WithoutOverlapping lock and revalidate their reservation before persisting. URL changes and pause/resume invalidate pending checks. HTTP 2xx is up; blocked/inconclusive redirect targets, response-size policy limits, and 401/403 remain unknown. 401/403 use access_restricted and display Protected, not Up or Down. Connection/DNS/other HTTP failures are down. Store safe error categories only, never exception text or response bodies. New results persist final_url/redirect_count and monitor summaries last_final_url/last_redirect_count; omit query and fragment from persisted final URLs. Old results remain unchanged and require a new check to use the updated policy. Restart long-running queue workers after code updates.
- Run `php artisan test --compact tests/Feature/MonitorTargetTest.php tests/Feature/MonitorHealthCheckTest.php tests/Feature/ManualMonitorCheckTest.php` for manual-check coverage. Tests fake HTTP and DNS processes; never enable real outbound traffic in these tests.
- Browser GET `/monitors/{monitor}` renders `Monitors/Show`; JSON requests retain the monitor resource. Owner-protected GET `/monitors/{monitor}/checks` returns paginated history, newest checked_at/id first, with comparisons spanning pagination boundaries. New checks snapshot target_url; legacy missing URLs remain null. Compare only adjacent results for the same known URL and only compare latency between successful timed checks. URL changes preserve history.
- `CheckInstructions.vue` explains worker startup, local-address blocking, manual refresh, and inactive scheduling on dashboard/detail pages. Reservations do not prove worker availability. Detail check requests use validated redirect=details, and locale switching back to details verifies monitor ownership. Run `php artisan test --compact tests/Feature/MonitorHistoryTest.php` and `node --test tests/Frontend/*.test.mjs` for history/resource/localization and rendered Vue history coverage.
- Run `php artisan test --compact tests/Feature/MonitorDashboardTest.php` to verify dashboard statistics, filtering, pagination, and the Inertia/JSON contracts.
- `/` renders the DevPulse `Welcome` Inertia page. Supported locales are English (default), Arabic, and French. POST `/locale` accepts validated locale and named redirect destinations; the session preference survives login and logout. `SetLocale` must run after session startup and before Inertia/validation. API health-status and error-category identifiers stay untranslated.
- Shared Inertia translations come from `lang/en.json`, `lang/ar.json`, and `lang/fr.json`. Vue uses `$t(englishKey, parameters)`; Laravel uses `__(englishKey)`. Arabic/French auth and validation files are in the corresponding locale folders. Keep catalog keys and colon-prefixed placeholders aligned. Use logical start/end positioning and isolate endpoint URLs as LTR.
- `Preferences.vue` controls locale and light/dark appearance on all pages. Theme is stored as `devpulse-theme` in localStorage, with device preference as the initial fallback; a pre-asset Blade script prevents theme flash. CSS color tokens switch palettes via the root data-theme attribute. HTML lang/dir are set server-side and synchronized on Inertia navigation.
- Run `php artisan test --compact tests/Feature/LocalizationTest.php` for locale/session/validation coverage and `node --test tests/Frontend/preferences.test.mjs` for translation/theme/first-paint behavior. Frontend tests use built-in node:test with no added dependencies.
- Frontend pages use Vue 3 `<script setup>` in `resources/js/Pages`; shared components live in `resources/js/Components` and layouts in `resources/js/Layouts`. Named Laravel URLs are passed through Inertia shared props rather than hardcoded in Vue.
- Run `npm ci` to install frontend dependencies and `npm run build` to verify the production build. `.npmrc` intentionally disables lifecycle scripts; do not weaken this setting.
- Run `php artisan test --compact tests/Feature/AuthenticationTest.php` for authentication checks. These tests use `withoutVite()` so they do not require compiled assets.
- npm audit currently reports a development-toolchain advisory through picomatch (no patched version available when checked). Do not force-downgrade tooling or disable audit to hide it.

<template>
    <section aria-labelledby="check-instructions-title" class="mb-8 rounded-2xl border border-sky-400/30 bg-slate-900 p-5 sm:p-6">
        <h2 id="check-instructions-title" class="font-semibold text-sky-300">{{ $t('Manual checks require a running worker.') }}</h2>
        <div class="mt-4 grid gap-5 text-sm leading-6 text-slate-300 lg:grid-cols-2">
            <div>
                <p>{{ $t('Keep this command running in a terminal inside the project:') }}</p>
                <pre dir="ltr" class="mt-3 overflow-x-auto rounded-lg border border-slate-800 bg-slate-950 p-3 text-start text-xs text-slate-200"><code>php artisan queue:work database --queue=monitor-checks --timeout=30</code></pre>
                <p class="mt-3">{{ $t('Then select Check Now and Refresh results after the worker finishes.') }}</p>
            </div>
            <div>
                <h3 class="font-medium text-slate-100">{{ $t('Public endpoints only') }}</h3>
                <p class="mt-2">{{ $t('Local addresses (.test, localhost, loopback and private IPs) are blocked for SSRF protection. Use a public health endpoint.') }}</p>
                <p class="mt-3 text-amber-300">{{ $t('Checks are manual. The interval setting does not enable scheduling yet.') }}</p>
            </div>
        </div>
        <p class="mt-4 border-t border-slate-800 pt-4 text-xs leading-6 text-slate-400">{{ $t('Checks do not use your browser session. Up to three safe redirects are followed. Protected access (401/403) does not confirm application health.') }} {{ $t('Response time includes DNS resolution and followed redirects.') }}</p>
    </section>
</template>

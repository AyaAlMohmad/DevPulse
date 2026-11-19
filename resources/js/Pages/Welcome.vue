<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import Preferences from '../Components/Preferences.vue';
import StatusBadge from '../Components/StatusBadge.vue';

const page = usePage();
const examples = [
    { endpoint: 'GET /api/health', status: 'up', response: 84 },
    { endpoint: 'GET /api/billing', status: 'up', response: 126 },
    { endpoint: 'GET /api/search', status: 'down', response: 490 },
];
const features = [
    { title: 'Organize your endpoints', description: 'Create, search, filter, and pause monitors in a workspace that stays out of your way.' },
    { title: 'Inspect every response', description: 'Run queued manual checks and review HTTP status, response time, and clear failure categories.' },
    { title: 'Designed with boundaries', description: 'Public destinations, verified TLS, bounded requests, and protection against stale results.' },
];
const steps = ['Add a public health endpoint', 'Run a manual health check', 'Refresh and review the result'];
</script>

<template>
    <Head :title="$t('Home')">
        <meta name="description" :content="$t('Know what responds. Understand what fails. Keep your endpoints and their latest health results in one focused workspace.')" />
    </Head>
    <div class="relative isolate min-h-screen overflow-hidden bg-slate-950">
        <div class="landing-grid pointer-events-none absolute inset-x-0 top-0 -z-10 h-[680px] opacity-40" aria-hidden="true"></div>
        <header class="border-b border-slate-800/70">
            <nav :aria-label="$t('Home')" class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-5 px-6 py-5">
                <Link :href="page.props.urls.home" class="inline-flex items-center gap-2.5 text-2xl font-bold tracking-tight">
                    <span class="flex size-9 items-center justify-center rounded-xl border border-emerald-400/30 bg-emerald-400/10 text-emerald-400">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12h5l3-8 4 16 3-8h5" stroke-linejoin="round" stroke-linecap="round" /></svg>
                    </span>
                    DevPulse<span class="-ms-2 text-emerald-400">.</span>
                </Link>
                <div class="flex flex-wrap items-center gap-3 sm:gap-5">
                    <Preferences />
                    <Link v-if="page.props.auth.user" :href="page.props.urls.dashboard" class="rounded-lg bg-emerald-400 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-300">{{ $t('Open dashboard') }}</Link>
                    <Link v-else :href="page.props.urls.login" class="rounded-lg px-2 py-2 text-sm font-medium text-slate-200 hover:text-emerald-400">{{ $t('Sign in') }} <svg class="ms-1 inline size-3.5 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 17 17 7M7 7h10v10" /></svg></Link>
                </div>
            </nav>
        </header>

        <main>
            <section class="mx-auto grid max-w-7xl items-center gap-14 px-6 pt-16 pb-20 sm:pt-24 sm:pb-28 lg:grid-cols-2 lg:gap-12">
                <div>
                    <p class="mb-7 inline-flex items-center gap-2 rounded-full border border-emerald-400/25 bg-emerald-400/5 px-3 py-1.5 text-xs font-semibold tracking-wider text-emerald-400">
                        <span class="size-1.5 rounded-full bg-emerald-400" aria-hidden="true"></span>{{ $t('BUILT FOR DEVELOPERS') }}
                    </p>
                    <h1 class="max-w-2xl text-5xl leading-[1.12] font-semibold tracking-tight text-balance sm:text-6xl xl:text-7xl">{{ $t('Your APIs. One clear pulse.') }}</h1>
                    <p class="mt-7 max-w-xl text-lg leading-8 text-slate-400">{{ $t('Know what responds. Understand what fails. Keep your endpoints and their latest health results in one focused workspace.') }}</p>
                    <div class="mt-9 flex flex-wrap gap-4">
                        <Link :href="page.props.auth.user ? page.props.urls.dashboard : page.props.urls.register" class="rounded-xl bg-emerald-400 px-6 py-3.5 font-semibold text-slate-950 shadow-lg shadow-emerald-400/10 hover:bg-emerald-300">{{ $t(page.props.auth.user ? 'Open dashboard' : 'Start your workspace') }}</Link>
                        <a href="#workflow" class="rounded-xl border border-slate-700 bg-slate-900 px-6 py-3.5 font-medium text-slate-300 hover:bg-slate-800">{{ $t('Explore the workflow') }}</a>
                    </div>
                    <div class="mt-10 flex flex-wrap gap-3 text-xs text-slate-400">
                        <span class="rounded-md border border-slate-800 bg-slate-900 px-2.5 py-1.5">Laravel</span>
                        <span class="rounded-md border border-slate-800 bg-slate-900 px-2.5 py-1.5">Vue 3</span>
                        <span class="rounded-md border border-slate-800 bg-slate-900 px-2.5 py-1.5">HTTP / HTTPS</span>
                    </div>
                </div>

                <div class="relative min-w-0">
                    <div class="pointer-events-none absolute -inset-8 -z-10 rounded-full bg-emerald-400/10 blur-3xl" aria-hidden="true"></div>
                    <div class="overflow-hidden rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl shadow-slate-950/30">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 px-5 py-4">
                            <span class="text-sm font-medium">{{ $t('A preview of your workspace') }}</span>
                            <span class="flex gap-1.5" aria-hidden="true"><span class="size-2 rounded-full bg-slate-600"></span><span class="size-2 rounded-full bg-slate-600"></span><span class="size-2 rounded-full bg-emerald-400"></span></span>
                        </div>
                        <div class="p-5 sm:p-7">
                            <div class="grid grid-cols-3 gap-3 border-b border-slate-800 pb-6">
                                <div><p class="text-xs text-slate-400">{{ $t('Total monitors') }}</p><p class="mt-2 text-3xl font-semibold">3</p></div>
                                <div><p class="text-xs text-slate-400">{{ $t('Up') }}</p><p class="mt-2 text-3xl font-semibold text-emerald-400">2</p></div>
                                <div><p class="text-xs text-slate-400">{{ $t('Down') }}</p><p class="mt-2 text-3xl font-semibold text-red-400">1</p></div>
                            </div>
                            <div class="divide-y divide-slate-800">
                                <div v-for="example in examples" :key="example.endpoint" class="flex flex-wrap items-center justify-between gap-3 py-5">
                                    <div class="min-w-0"><code dir="ltr" class="block truncate text-xs text-slate-200 sm:text-sm">{{ example.endpoint }}</code><p class="mt-1 text-xs text-slate-500">{{ $t('Latest response') }} · {{ $t(':value ms', { value: example.response }) }}</p></div>
                                    <StatusBadge :status="example.status" />
                                </div>
                            </div>
                            <div class="mt-3 rounded-xl border border-emerald-400/20 bg-emerald-400/5 px-4 py-5" aria-hidden="true">
                                <svg class="h-16 w-full text-emerald-400" viewBox="0 0 400 64" fill="none" preserveAspectRatio="none"><path d="M0 36h82l10-8 12 17 18-38 20 51 18-26 12 4h58l12-12 12 22 16-15 15 5h115" stroke="currentColor" stroke-width="2" vector-effect="non-scaling-stroke" /></svg>
                            </div>
                        </div>
                        <p class="border-t border-slate-800 px-5 py-3 text-center text-xs text-slate-400">{{ $t('Illustrative data — not live monitoring') }}</p>
                    </div>
                </div>
            </section>

            <section class="border-y border-slate-800 bg-slate-900/60">
                <div class="mx-auto max-w-7xl px-6 py-16 sm:py-20">
                    <h2 class="max-w-2xl text-3xl leading-snug font-semibold tracking-tight">{{ $t('A focused toolkit, not another noisy dashboard.') }}</h2>
                    <div class="mt-10 grid gap-5 md:grid-cols-3">
                        <article v-for="(feature, index) in features" :key="feature.title" class="rounded-2xl border border-slate-800 bg-slate-900 p-7">
                            <span class="font-mono text-sm text-emerald-400" aria-hidden="true">0{{ index + 1 }} /</span>
                            <h3 class="mt-6 text-xl font-semibold">{{ $t(feature.title) }}</h3>
                            <p class="mt-3 text-sm leading-7 text-slate-400">{{ $t(feature.description) }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <section id="workflow" class="mx-auto grid max-w-7xl gap-10 px-6 py-20 sm:py-24 md:grid-cols-2 md:items-center">
                <div><p class="text-sm font-medium text-emerald-400">DevPulse / 01</p><h2 class="mt-4 text-4xl leading-tight font-semibold tracking-tight">{{ $t('From endpoint to insight.') }}</h2><p class="mt-5 max-w-lg text-sm leading-7 text-slate-400">{{ $t('Early access · Manual checks available. Scheduling, charts, and alerts are on the roadmap.') }}</p></div>
                <ol class="space-y-4">
                    <li v-for="(step, index) in steps" :key="step" class="flex items-center gap-4 rounded-xl border border-slate-800 bg-slate-900 p-5"><span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-emerald-400/10 font-mono text-sm text-emerald-400">{{ index + 1 }}</span><span class="font-medium">{{ $t(step) }}</span></li>
                </ol>
            </section>

            <section class="mx-6 mb-16 rounded-2xl border border-emerald-400/20 bg-emerald-400/5 px-6 py-12 text-center sm:mx-auto sm:max-w-5xl">
                <h2 class="text-3xl font-semibold tracking-tight">{{ $t('Build with clarity.') }}</h2>
                <p class="mt-3 text-slate-400">{{ $t('API health, without the noise.') }}</p>
                <Link :href="page.props.auth.user ? page.props.urls.dashboard : page.props.urls.register" class="mt-7 inline-block rounded-xl bg-emerald-400 px-6 py-3 font-semibold text-slate-950 hover:bg-emerald-300">{{ $t(page.props.auth.user ? 'Open dashboard' : 'Create an account') }}</Link>
            </section>
        </main>
        <footer class="border-t border-slate-800"><div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-7 text-xs text-slate-400"><span>© {{ new Date().getFullYear() }} DevPulse</span><span>{{ $t('API health, without the noise.') }}</span></div></footer>
    </div>
</template>

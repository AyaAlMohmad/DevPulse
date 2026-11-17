<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const logoutForm = useForm({});

function logout() {
    logoutForm.post(page.props.urls.logout);
}
</script>

<template>
    <Head title="Dashboard" />
    <div class="min-h-screen">
        <header class="border-b border-slate-800 bg-slate-900">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-6 py-5">
                <span class="text-xl font-bold tracking-tight text-emerald-400">DevPulse</span>
                <form class="flex items-center gap-4" @submit.prevent="logout">
                    <span class="text-sm text-slate-300">{{ page.props.auth.user.name }}</span>
                    <button :disabled="logoutForm.processing" class="rounded-lg border border-slate-700 px-4 py-2 text-sm hover:bg-slate-800 disabled:opacity-50">Sign out</button>
                </form>
            </div>
        </header>
        <main class="mx-auto max-w-6xl px-6 py-12">
            <p class="text-sm font-medium text-emerald-400">YOUR WORKSPACE</p>
            <h1 class="mt-3 text-3xl font-semibold">Welcome, {{ page.props.auth.user.name }}</h1>
            <p class="mt-3 max-w-2xl text-slate-400">Your account is ready. This is the foundation for your API monitoring dashboard.</p>
            <section class="mt-10 rounded-2xl border border-dashed border-slate-700 bg-slate-900 p-8">
                <h2 class="text-lg font-semibold">Monitor management is next</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-400">The monitor backend is implemented. In the next phase, we will connect reusable Vue forms, status badges, and a monitor table to your workspace. No health checks are running yet.</p>
            </section>
        </main>
    </div>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const logoutForm = useForm({});
</script>

<template>
    <div class="min-h-screen">
        <header class="border-b border-slate-800 bg-slate-900">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-5">
                <Link :href="page.props.urls.dashboard" class="text-xl font-bold tracking-tight text-emerald-400">DevPulse</Link>
                <form class="flex items-center gap-4" @submit.prevent="logoutForm.post(page.props.urls.logout)">
                    <span class="text-sm text-slate-300">{{ page.props.auth.user.name }}</span>
                    <button :disabled="logoutForm.processing" class="rounded-lg border border-slate-700 px-4 py-2 text-sm hover:bg-slate-800 disabled:opacity-50">Sign out</button>
                </form>
            </div>
        </header>
        <main class="mx-auto max-w-7xl px-6 py-10">
            <p v-if="page.props.flash.success" role="status" class="mb-6 rounded-xl border border-emerald-800 bg-emerald-950 px-4 py-3 text-sm text-emerald-200">{{ page.props.flash.success }}</p>
            <slot />
        </main>
    </div>
</template>

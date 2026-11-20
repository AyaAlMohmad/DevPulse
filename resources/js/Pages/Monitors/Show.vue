<script setup>
import { computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import CheckHistory from '../../Components/CheckHistory.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import StatCard from '../../Components/StatCard.vue';
import { errorLabels, formatCheckDate } from '../../monitoring.js';

const props = defineProps({ monitor: { type: Object, required: true }, checks: { type: Object, required: true } });
const current = computed(() => props.monitor.data);
const page = usePage();
const form = useForm({ redirect: 'details' });
function refresh() {
    form.clearErrors();
    router.reload({ only: ['monitor', 'checks', 'flash'], preserveScroll: true });
}

</script>

<template>
    <Head :title="`${$t('Monitor details')} · ${current.name}`" />
    <AppLayout>
        <Link :href="page.props.urls.dashboard" class="text-sm font-medium text-emerald-400 hover:underline">{{ $t('Back to monitors') }}</Link>
        <div class="mt-5 mb-7 flex flex-wrap items-start justify-between gap-5">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-3"><h1 dir="auto" class="break-words text-3xl font-semibold">{{ current.name }}</h1><StatusBadge :status="current.status" :paused="current.is_paused" :error-type="current.last_error_type" /></div>
                <p dir="ltr" class="mt-3 break-all text-start text-sm text-slate-400">{{ current.url }}</p>
                <p class="mt-2 text-xs text-slate-400">{{ $t('Last check') }}: {{ current.last_checked_at ? formatCheckDate(current.last_checked_at, page.props.locale) : $t('Never checked') }}</p>
                <p v-if="current.last_error_type" class="mt-3 text-sm text-amber-300">{{ $t(errorLabels[current.last_error_type] ?? 'Check could not complete') }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button :disabled="form.processing" class="rounded-lg border border-slate-700 px-4 py-2.5 hover:bg-slate-800 disabled:opacity-50" @click="refresh">{{ $t('Refresh results') }}</button>
                <button :disabled="form.processing || current.is_paused || current.is_check_pending" class="rounded-lg bg-emerald-400 px-4 py-2.5 font-semibold text-slate-950 hover:bg-emerald-300 disabled:opacity-50" @click="form.post(current.links.check, { preserveScroll: true })">{{ $t(current.is_check_pending ? 'Queued / running' : 'Check Now') }}</button>
            </div>
        </div>
        <p v-if="current.is_check_pending" role="status" class="mb-6 rounded-xl border border-sky-400/30 bg-slate-900 p-4 text-sm leading-6 text-sky-300">{{ $t('Requested: :time', { time: formatCheckDate(current.check_requested_at, page.props.locale) }) }}. {{ $t('Still waiting? Verify that the worker is running, then refresh. This page does not detect worker availability.') }}</p>
        <p v-if="current.check_reservation_expired" role="status" class="mb-6 rounded-xl border border-amber-400/30 bg-slate-900 p-4 text-sm leading-6 text-amber-300">{{ $t('Request reservation expired. Refresh and request a new check after starting the worker.') }}</p>
        <p v-if="form.errors.check || form.errors.redirect" role="alert" class="mb-6 text-sm text-red-400">{{ form.errors.check ?? form.errors.redirect }}</p>
        <dl class="mb-8 grid gap-4 sm:grid-cols-3">
            <StatCard :label="$t('Recorded checks')" :value="checks.meta.total" :detail="$t('All recorded results for this monitor')" />
            <StatCard :label="$t('Response')" :value="current.last_response_time_ms === null ? '—' : $t(':value ms', { value: current.last_response_time_ms })" :detail="$t('Latest response')" />
            <StatCard label="HTTP" :value="current.last_http_status ?? '—'" :detail="$t('Last check')" />
        </dl>
        <div v-if="current.last_final_url" class="mb-8 rounded-xl border border-slate-800 bg-slate-900 p-5 text-sm">
            <p class="text-xs text-slate-400">{{ $t('Last requested URL (query and fragment omitted)') }}</p>
            <p dir="ltr" class="mt-2 break-all text-start">{{ current.last_final_url }}</p>
            <p class="mt-3 text-xs text-slate-400">{{ $t('Redirects: :count', { count: current.last_redirect_count ?? '—' }) }}</p>
        </div>
        <CheckHistory :checks="checks" :current-url="current.url" />
    </AppLayout>
</template>

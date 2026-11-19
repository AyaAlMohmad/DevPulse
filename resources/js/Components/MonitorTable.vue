<script setup>
import { Link } from '@inertiajs/vue3';
import StatusBadge from './StatusBadge.vue';

defineProps({
    monitors: { type: Object, required: true },
    busy: { type: Boolean, default: false },
    filtered: { type: Boolean, default: false },
});
const emit = defineEmits(['edit', 'toggle', 'remove', 'check']);
import { statusLabels, errorLabels } from '../monitoring.js';
</script>

<template>
    <section :aria-label="$t('Monitors')" class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900">
        <div v-if="!monitors.data.length" class="px-6 py-16 text-center">
            <h2 class="text-lg font-semibold">{{ $t(filtered ? 'No matching monitors' : 'No monitors yet') }}</h2>
            <p class="mt-2 text-sm text-slate-400">{{ $t(filtered ? 'Try another search or clear your filters.' : 'Add your first endpoint using the button above.') }}</p>
        </div>
        <div v-else class="overflow-x-auto">
            <table class="w-full text-start text-sm">
                <caption class="sr-only">{{ $t('Your API monitors and latest recorded health results') }}</caption>
                <thead class="border-b border-slate-800 text-xs uppercase tracking-wide text-slate-400">
                    <tr>
                        <th scope="col" class="px-5 py-4">{{ $t('Endpoint') }}</th>
                        <th scope="col" class="px-5 py-4">{{ $t('Status') }}</th>
                        <th scope="col" class="px-5 py-4">{{ $t('Response') }}</th>
                        <th scope="col" class="px-5 py-4">{{ $t('Interval') }}</th>
                        <th scope="col" class="px-5 py-4">{{ $t('Last check') }}</th>
                        <th scope="col" class="px-5 py-4 text-end">{{ $t('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <tr v-for="monitor in monitors.data" :key="monitor.id" class="hover:bg-slate-800/40">
                        <td class="max-w-xs px-5 py-5">
                            <Link :href="monitor.links.show" dir="auto" class="block truncate font-medium hover:text-emerald-400" :title="monitor.name">{{ monitor.name }}</Link>
                            <p dir="ltr" class="mt-1 truncate text-start text-xs text-slate-400" :title="monitor.url">{{ monitor.url }}</p>
                        </td>
                        <td class="px-5 py-5">
                            <StatusBadge :status="monitor.status" :paused="monitor.is_paused" :error-type="monitor.last_error_type" />
                            <p v-if="monitor.is_check_pending" role="status" class="mt-1 text-xs text-sky-300">{{ $t('Queued / running') }}</p>
                            <p v-if="monitor.check_reservation_expired" role="status" class="mt-1 max-w-48 text-xs leading-5 text-amber-300">{{ $t('Request reservation expired. Refresh and request a new check after starting the worker.') }}</p>
                            <p v-if="monitor.last_error_type" class="mt-1 max-w-48 text-xs text-amber-300">{{ $t(errorLabels[monitor.last_error_type] ?? 'Check could not complete') }}</p>
                            <p v-if="monitor.is_paused" class="mt-1 text-xs text-slate-500">{{ $t('Last: :status', { status: $t(statusLabels[monitor.status] ?? 'Unknown') }) }}</p>
                        </td>
                        <td class="whitespace-nowrap px-5 py-5">
                            <span>{{ monitor.last_response_time_ms === null ? '—' : $t(':value ms', { value: monitor.last_response_time_ms }) }}</span>
                            <p class="mt-1 text-xs text-slate-500">{{ monitor.last_http_status === null ? $t('No HTTP result') : `HTTP ${monitor.last_http_status}` }}</p>
                        </td>
                        <td class="whitespace-nowrap px-5 py-5 text-slate-300">{{ $t(':value min', { value: monitor.interval_seconds / 60 }) }}</td>
                        <td class="whitespace-nowrap px-5 py-5 text-xs text-slate-400">{{ monitor.last_checked_at ? new Date(monitor.last_checked_at).toLocaleString($page.props.locale) : $t('Never checked') }}</td>
                        <td class="px-5 py-5">
                            <div class="flex justify-end gap-2" :aria-label="$t('Actions for :name', { name: monitor.name })">
                                <Link :href="monitor.links.show" class="whitespace-nowrap rounded-md border border-sky-400/40 px-3 py-1.5 text-sky-300 hover:bg-slate-800">{{ $t('Details & history') }}</Link>
                                <button :disabled="busy || monitor.is_paused || monitor.is_check_pending" class="whitespace-nowrap rounded-md border border-emerald-800 px-3 py-1.5 text-emerald-300 hover:bg-emerald-950 disabled:cursor-not-allowed disabled:opacity-50" @click="emit('check', monitor)">{{ $t(monitor.is_check_pending ? 'Queued / running' : 'Check Now') }}</button>
                                <button :disabled="busy" class="rounded-md border border-slate-700 px-3 py-1.5 hover:bg-slate-800 disabled:opacity-50" @click="emit('edit', monitor)">{{ $t('Edit') }}</button>
                                <button :disabled="busy" class="rounded-md border border-slate-700 px-3 py-1.5 hover:bg-slate-800 disabled:opacity-50" @click="emit('toggle', monitor)">{{ $t(monitor.is_paused ? 'Resume' : 'Pause') }}</button>
                                <button :disabled="busy" class="rounded-md border border-red-900 px-3 py-1.5 text-red-300 hover:bg-red-950 disabled:opacity-50" @click="emit('remove', monitor)">{{ $t('Delete') }}</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <nav :aria-label="$t('Monitor pagination')" class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-800 px-5 py-4 text-sm">
            <span class="text-slate-400">{{ $t(':total results · Page :current of :last', { total: monitors.meta.total, current: monitors.meta.current_page, last: monitors.meta.last_page }) }}</span>
            <div class="flex gap-3">
                <Link v-if="monitors.links.prev" :href="monitors.links.prev" preserve-scroll class="rounded-lg border border-slate-700 px-3 py-2 hover:bg-slate-800">{{ $t('Previous') }}</Link>
                <span v-else aria-disabled="true" class="px-3 py-2 text-slate-600">{{ $t('Previous') }}</span>
                <Link v-if="monitors.links.next" :href="monitors.links.next" preserve-scroll class="rounded-lg border border-slate-700 px-3 py-2 hover:bg-slate-800">{{ $t('Next') }}</Link>
                <span v-else aria-disabled="true" class="px-3 py-2 text-slate-600">{{ $t('Next') }}</span>
            </div>
        </nav>
    </section>
</template>

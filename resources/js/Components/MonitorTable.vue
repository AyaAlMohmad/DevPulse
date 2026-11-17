<script setup>
import { Link } from '@inertiajs/vue3';
import StatusBadge from './StatusBadge.vue';

defineProps({
    monitors: { type: Object, required: true },
    busy: { type: Boolean, default: false },
    filtered: { type: Boolean, default: false },
});
const emit = defineEmits(['edit', 'toggle', 'remove']);

function formatDate(value) {
    return value ? new Date(value).toLocaleString() : 'Never checked';
}
</script>

<template>
    <section aria-label="Monitors" class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900">
        <div v-if="!monitors.data.length" class="px-6 py-16 text-center">
            <h2 class="text-lg font-semibold">{{ filtered ? 'No matching monitors' : 'No monitors yet' }}</h2>
            <p class="mt-2 text-sm text-slate-400">{{ filtered ? 'Try another search or clear your filters.' : 'Add your first endpoint using the button above.' }}</p>
        </div>
        <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Your API monitors and latest recorded health results</caption>
                <thead class="border-b border-slate-800 text-xs uppercase tracking-wide text-slate-400">
                    <tr>
                        <th scope="col" class="px-5 py-4">Endpoint</th>
                        <th scope="col" class="px-5 py-4">Status</th>
                        <th scope="col" class="px-5 py-4">Response</th>
                        <th scope="col" class="px-5 py-4">Interval</th>
                        <th scope="col" class="px-5 py-4">Last check</th>
                        <th scope="col" class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <tr v-for="monitor in monitors.data" :key="monitor.id" class="hover:bg-slate-800/40">
                        <td class="max-w-xs px-5 py-5">
                            <p class="truncate font-medium" :title="monitor.name">{{ monitor.name }}</p>
                            <p class="mt-1 truncate text-xs text-slate-400" :title="monitor.url">{{ monitor.url }}</p>
                        </td>
                        <td class="px-5 py-5">
                            <StatusBadge :status="monitor.status" :paused="monitor.is_paused" />
                            <p v-if="monitor.is_paused" class="mt-1 text-xs text-slate-500">Last: {{ monitor.status }}</p>
                        </td>
                        <td class="whitespace-nowrap px-5 py-5">
                            <span>{{ monitor.last_response_time_ms === null ? '—' : `${monitor.last_response_time_ms} ms` }}</span>
                            <p class="mt-1 text-xs text-slate-500">{{ monitor.last_http_status === null ? 'No HTTP result' : `HTTP ${monitor.last_http_status}` }}</p>
                        </td>
                        <td class="whitespace-nowrap px-5 py-5 text-slate-300">{{ monitor.interval_seconds / 60 }} min</td>
                        <td class="whitespace-nowrap px-5 py-5 text-xs text-slate-400">{{ formatDate(monitor.last_checked_at) }}</td>
                        <td class="px-5 py-5">
                            <div class="flex justify-end gap-2" :aria-label="`Actions for ${monitor.name}`">
                                <button :disabled="busy" class="rounded-md border border-slate-700 px-3 py-1.5 hover:bg-slate-800 disabled:opacity-50" @click="emit('edit', monitor)">Edit</button>
                                <button :disabled="busy" class="rounded-md border border-slate-700 px-3 py-1.5 hover:bg-slate-800 disabled:opacity-50" @click="emit('toggle', monitor)">{{ monitor.is_paused ? 'Resume' : 'Pause' }}</button>
                                <button :disabled="busy" class="rounded-md border border-red-900 px-3 py-1.5 text-red-300 hover:bg-red-950 disabled:opacity-50" @click="emit('remove', monitor)">Delete</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <nav aria-label="Monitor pagination" class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-800 px-5 py-4 text-sm">
            <span class="text-slate-400">{{ monitors.meta.total }} results · Page {{ monitors.meta.current_page }} of {{ monitors.meta.last_page }}</span>
            <div class="flex gap-3">
                <Link v-if="monitors.links.prev" :href="monitors.links.prev" preserve-scroll class="rounded-lg border border-slate-700 px-3 py-2 hover:bg-slate-800">Previous</Link>
                <span v-else aria-disabled="true" class="px-3 py-2 text-slate-600">Previous</span>
                <Link v-if="monitors.links.next" :href="monitors.links.next" preserve-scroll class="rounded-lg border border-slate-700 px-3 py-2 hover:bg-slate-800">Next</Link>
                <span v-else aria-disabled="true" class="px-3 py-2 text-slate-600">Next</span>
            </div>
        </nav>
    </section>
</template>

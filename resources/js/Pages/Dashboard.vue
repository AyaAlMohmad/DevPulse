<script setup>
import { reactive, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import MonitorForm from '../Components/MonitorForm.vue';
import MonitorTable from '../Components/MonitorTable.vue';
import StatCard from '../Components/StatCard.vue';

const props = defineProps({
    monitors: { type: Object, required: true },
    stats: { type: Object, required: true },
    filters: { type: Object, required: true },
});
const page = usePage();
const formOpen = ref(false);
const editing = ref(null);
const actionForm = useForm({});
const filters = reactive({ ...props.filters });

watch(() => props.filters, (value) => Object.assign(filters, value));

function openForm(monitor = null) {
    editing.value = monitor;
    formOpen.value = true;
}

function closeForm() {
    formOpen.value = false;
    editing.value = null;
}

function applyFilters() {
    router.get(page.props.urls.dashboard, filters, { preserveState: true, preserveScroll: true, replace: true });
}

function resetFilters() {
    Object.assign(filters, { search: '', status: '' });
    applyFilters();
}

function checkMonitor(monitor) {
    actionForm.clearErrors();
    actionForm.post(monitor.links.check, { preserveScroll: true });
}

function refreshResults() {
    actionForm.clearErrors();
    router.reload({ only: ['monitors', 'stats', 'flash'], preserveScroll: true });
}

function toggleMonitor(monitor) {
    actionForm.patch(monitor.is_paused ? monitor.links.resume : monitor.links.pause, { preserveScroll: true });
}

function deleteMonitor(monitor) {
    if (!window.confirm(`Delete "${monitor.name}" and all its check history? This cannot be undone.`)) return;
    actionForm.delete(monitor.links.destroy, {
        preserveScroll: true,
        onSuccess: () => { if (editing.value?.id === monitor.id) closeForm(); },
    });
}
</script>

<template>
    <Head title="Monitors" />
    <AppLayout>
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-emerald-400">YOUR WORKSPACE</p>
                <h1 class="mt-2 text-3xl font-semibold">API monitors</h1>
                <p class="mt-2 text-sm text-slate-400">Manage endpoints and review their latest recorded health.</p>
            </div>
            <div class="flex gap-3">
                <button :disabled="actionForm.processing" class="rounded-lg border border-slate-700 px-4 py-2.5 text-slate-200 hover:bg-slate-800 disabled:opacity-50" @click="refreshResults">Refresh results</button>
                <button :disabled="actionForm.processing" class="rounded-lg bg-emerald-400 px-5 py-2.5 font-semibold text-slate-950 hover:bg-emerald-300 disabled:opacity-50" @click="openForm()">Add monitor</button>
            </div>
        </div>

        <dl class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Total monitors" :value="stats.total" detail="All monitors, including paused" />
            <StatCard label="Up" :value="stats.up" detail="Active monitors last recorded as up" />
            <StatCard label="Down" :value="stats.down" detail="Active monitors last recorded as down" />
            <StatCard label="Avg response time" :value="stats.average_response_time_ms === null ? '—' : `${stats.average_response_time_ms} ms`" detail="Latest measured responses of active monitors" />
        </dl>
        <p class="mb-8 text-xs text-slate-400">{{ stats.unknown }} active with unknown status · {{ stats.paused }} paused. Statistics cover all your monitors, regardless of filters.</p>
        <p class="mb-8 rounded-xl border border-slate-800 bg-slate-900 px-4 py-3 text-sm text-slate-400">Check Now queues a manual check. Select Refresh results after the worker runs. Pending reservations expire after 5 minutes if no worker processes them. Scheduled checks, charts, and automatic refresh are not enabled yet.</p>
        <p v-if="actionForm.errors.check" role="alert" class="mb-6 rounded-lg border border-red-900 bg-red-950 px-4 py-3 text-sm text-red-200">{{ actionForm.errors.check }}</p>

        <MonitorForm v-if="formOpen" :key="editing?.id ?? 'new'" :monitor="editing" @saved="closeForm" @cancel="closeForm" />

        <form class="mb-5 flex flex-wrap items-end gap-3" role="search" @submit.prevent="applyFilters">
            <div class="min-w-48 flex-1">
                <label for="monitor-search" class="mb-2 block text-sm text-slate-300">Search endpoints</label>
                <input id="monitor-search" v-model="filters.search" type="search" maxlength="100" placeholder="Name or URL" class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2.5 focus:ring-2 focus:ring-emerald-400" />
            </div>
            <div>
                <label for="status-filter" class="mb-2 block text-sm text-slate-300">Status</label>
                <select id="status-filter" v-model="filters.status" class="rounded-lg border border-slate-700 bg-slate-900 px-3 py-2.5 focus:ring-2 focus:ring-emerald-400">
                    <option value="">All statuses</option>
                    <option value="up">Up</option>
                    <option value="down">Down</option>
                    <option value="unknown">Unknown</option>
                    <option value="paused">Paused</option>
                </select>
            </div>
            <button type="submit" class="rounded-lg border border-slate-700 px-4 py-2.5 hover:bg-slate-800">Apply filters</button>
            <button type="button" class="rounded-lg px-4 py-2.5 text-slate-400 hover:text-white" @click="resetFilters">Reset</button>
        </form>
        <MonitorTable :monitors="monitors" :busy="actionForm.processing" :filtered="Boolean(props.filters.search || props.filters.status)" @edit="openForm" @toggle="toggleMonitor" @remove="deleteMonitor" @check="checkMonitor" />
    </AppLayout>
</template>

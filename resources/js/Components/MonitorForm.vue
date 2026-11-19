<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import FormField from './FormField.vue';

const props = defineProps({ monitor: { type: Object, default: null } });
const emit = defineEmits(['saved', 'cancel']);
const page = usePage();
const form = useForm({
    name: props.monitor?.name ?? '',
    url: props.monitor?.url ?? '',
    interval_seconds: props.monitor?.interval_seconds ?? 60,
});
const intervals = [
    { value: 60, label: 'Every minute' },
    { value: 300, label: 'Every 5 minutes' },
    { value: 900, label: 'Every 15 minutes' },
    { value: 1800, label: 'Every 30 minutes' },
    { value: 3600, label: 'Every hour' },
];

function submit() {
    const options = { preserveScroll: true, onSuccess: () => emit('saved') };
    if (props.monitor) form.patch(props.monitor.links.update, options);
    else form.post(page.props.urls.monitors, options);
}
</script>

<template>
    <section aria-labelledby="monitor-form-title" class="mb-8 rounded-2xl border border-emerald-800 bg-slate-900 p-6">
        <h2 id="monitor-form-title" class="text-xl font-semibold">{{ monitor ? 'Edit monitor' : 'Add a monitor' }}</h2>
        <p class="mt-2 text-sm text-slate-400">Save a public HTTP or HTTPS endpoint on its standard port (80 or 443), without embedded credentials. Use Check Now to run a manual health check.</p>
        <form class="mt-6 space-y-5" @submit.prevent="submit">
            <div class="grid gap-5 md:grid-cols-2">
                <FormField id="monitor-name" v-model="form.name" label="Monitor name" :error="form.errors.name" />
                <FormField id="monitor-url" v-model="form.url" label="Endpoint URL" type="url" :error="form.errors.url" />
            </div>
            <div class="max-w-sm">
                <label for="monitor-interval" class="mb-2 block text-sm font-medium">Check interval</label>
                <select id="monitor-interval" v-model="form.interval_seconds" :aria-invalid="Boolean(form.errors.interval_seconds)" :aria-describedby="form.errors.interval_seconds ? 'interval-error' : undefined" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 focus:ring-2 focus:ring-emerald-400">
                    <option v-for="interval in intervals" :key="interval.value" :value="interval.value">{{ interval.label }}</option>
                </select>
                <p v-if="form.errors.interval_seconds" id="interval-error" role="alert" class="mt-2 text-sm text-red-400">{{ form.errors.interval_seconds }}</p>
            </div>
            <p v-if="monitor" class="text-xs text-amber-300">Changing the URL resets the latest health summary. Historical checks are retained.</p>
            <div class="flex gap-3">
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-emerald-400 px-5 py-2.5 font-semibold text-slate-950 hover:bg-emerald-300 disabled:opacity-50">{{ form.processing ? 'Saving...' : 'Save monitor' }}</button>
                <button type="button" :disabled="form.processing" class="rounded-lg border border-slate-700 px-5 py-2.5 text-slate-300 hover:bg-slate-800 disabled:opacity-50" @click="emit('cancel')">Cancel</button>
            </div>
        </form>
    </section>
</template>

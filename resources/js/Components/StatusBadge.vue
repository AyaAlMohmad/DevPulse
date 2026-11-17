<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
    paused: { type: Boolean, default: false },
});

const display = computed(() => {
    if (props.paused) return { label: 'Paused', classes: 'bg-amber-400/10 text-amber-300 ring-amber-400/20' };
    if (props.status === 'up') return { label: 'Up', classes: 'bg-emerald-400/10 text-emerald-300 ring-emerald-400/20' };
    if (props.status === 'down') return { label: 'Down', classes: 'bg-red-400/10 text-red-300 ring-red-400/20' };
    return { label: 'Unknown', classes: 'bg-slate-400/10 text-slate-300 ring-slate-400/20' };
});
</script>

<template>
    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset" :class="display.classes">{{ display.label }}</span>
</template>

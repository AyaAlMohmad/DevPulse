<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    detail: { type: String, default: '' },
});
</script>

<template>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
        <dt class="text-sm text-slate-400">{{ label }}</dt>
        <dd class="mt-3 text-3xl font-semibold tracking-tight">{{ value }}</dd>
        <p v-if="detail" class="mt-2 text-xs text-slate-400">{{ detail }}</p>
    </div>
</template>

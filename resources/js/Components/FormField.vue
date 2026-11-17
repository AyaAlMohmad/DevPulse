<script setup>
defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    type: { type: String, default: 'text' },
    autocomplete: { type: String, default: 'off' },
    error: { type: String, default: '' },
    minlength: { type: Number, default: undefined },
});

const model = defineModel({ type: String, default: '' });
</script>

<template>
    <div>
        <label :for="id" class="mb-2 block text-sm font-medium text-slate-200">{{ label }}</label>
        <input
            :id="id"
            v-model="model"
            :name="id"
            :type="type"
            :autocomplete="autocomplete"
            :minlength="minlength"
            :aria-invalid="Boolean(error)"
            :aria-describedby="error ? `${id}-error` : undefined"
            required
            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-slate-100 outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/20"
        />
        <p v-if="error" :id="`${id}-error`" role="alert" class="mt-2 text-sm text-red-400">{{ error }}</p>
    </div>
</template>

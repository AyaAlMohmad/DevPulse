<script setup>
import { watchEffect } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { theme, setTheme } from '../appearance.js';

const page = usePage();
const form = useForm({ locale: page.props.locale, redirect: 'home', monitor_id: null });
const names = { ar: 'العربية', en: 'English', fr: 'Français' };

watchEffect(() => {
    document.documentElement.lang = page.props.locale;
    document.documentElement.dir = page.props.locale === 'ar' ? 'rtl' : 'ltr';
});

function changeLocale(event) {
    form.locale = event.target.value;
    form.redirect = { Welcome: 'home', 'Auth/Login': 'login', 'Auth/Register': 'register', Dashboard: 'dashboard', 'Monitors/Show': 'monitors.show' }[page.component] ?? 'home';
    form.monitor_id = page.component === 'Monitors/Show' ? page.props.monitor.data.id : null;
    form.post(page.props.urls.locale, {
        preserveScroll: true,
        preserveState: false,
        onFinish: () => { event.target.value = page.props.locale; },
    });
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <label for="site-language" class="sr-only">{{ $t('Language') }}</label>
        <select id="site-language" :value="page.props.locale" :disabled="form.processing" class="max-w-32 rounded-lg border border-slate-700 bg-slate-900 px-2.5 py-2 text-sm text-slate-200 disabled:opacity-50" @change="changeLocale">
            <option v-for="locale in page.props.locales" :key="locale" :value="locale" :lang="locale">{{ names[locale] }}</option>
        </select>
        <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-900 p-2 text-sm text-slate-200 hover:bg-slate-800" :aria-label="$t(theme === 'dark' ? 'Light mode' : 'Dark mode')" :title="$t(theme === 'dark' ? 'Light mode' : 'Dark mode')" @click="setTheme(theme === 'dark' ? 'light' : 'dark')">
            <svg v-if="theme === 'dark'" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="4" /><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5" /></svg>
            <svg v-else class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M20.5 14A9 9 0 0 1 10 3.5 9 9 0 1 0 20.5 14Z" /></svg>
            <span class="sr-only sm:not-sr-only">{{ $t(theme === 'dark' ? 'Light mode' : 'Dark mode') }}</span>
        </button>
    </div>
</template>

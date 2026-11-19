<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthLayout from '../../Layouts/AuthLayout.vue';
import FormField from '../../Components/FormField.vue';

const page = usePage();
const form = useForm({ email: '', password: '', remember: false });

function submit() {
    form.post(page.props.urls.login, {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head :title="$t('Sign in')" />
    <AuthLayout :title="$t('Welcome back')" :description="$t('Sign in to your DevPulse workspace.')">
        <form class="space-y-5" novalidate @submit.prevent="submit">
            <FormField id="email" v-model="form.email" :label="$t('Email address')" type="email" autocomplete="username" :error="form.errors.email" />
            <FormField id="password" v-model="form.password" :label="$t('Password')" type="password" autocomplete="current-password" :error="form.errors.password" />
            <label class="flex items-center gap-2 text-sm text-slate-300">
                <input v-model="form.remember" type="checkbox" name="remember" class="size-4 accent-emerald-400" />
                {{ $t('Remember me') }}
            </label>
            <button type="submit" :disabled="form.processing" class="w-full rounded-lg bg-emerald-400 px-4 py-3 font-semibold text-slate-950 transition hover:bg-emerald-300 disabled:cursor-wait disabled:opacity-50">
                {{ $t(form.processing ? 'Signing in...' : 'Sign in') }}
            </button>
            <p class="text-center text-sm text-slate-400">
                {{ $t('New to DevPulse?') }}
                <Link :href="page.props.urls.register" class="font-medium text-emerald-400 underline-offset-4 hover:underline">{{ $t('Create an account') }}</Link>
            </p>
        </form>
    </AuthLayout>
</template>

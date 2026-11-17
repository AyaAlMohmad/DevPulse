<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthLayout from '../../Layouts/AuthLayout.vue';
import FormField from '../../Components/FormField.vue';

const page = usePage();
const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });

function submit() {
    form.post(page.props.urls.register, {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Create account" />
    <AuthLayout title="Create your account" description="Set up your DevPulse workspace to get started.">
        <form class="space-y-5" @submit.prevent="submit">
            <FormField id="name" v-model="form.name" label="Full name" autocomplete="name" :error="form.errors.name" />
            <FormField id="email" v-model="form.email" label="Email address" type="email" autocomplete="username" :error="form.errors.email" />
            <FormField id="password" v-model="form.password" label="Password (at least 12 characters)" type="password" autocomplete="new-password" :minlength="12" :error="form.errors.password" />
            <FormField id="password_confirmation" v-model="form.password_confirmation" label="Confirm password" type="password" autocomplete="new-password" :minlength="12" :error="form.errors.password_confirmation" />
            <button type="submit" :disabled="form.processing" class="w-full rounded-lg bg-emerald-400 px-4 py-3 font-semibold text-slate-950 transition hover:bg-emerald-300 disabled:cursor-wait disabled:opacity-50">
                {{ form.processing ? 'Creating account...' : 'Create account' }}
            </button>
            <p class="text-center text-sm text-slate-400">
                Already have an account?
                <Link :href="page.props.urls.login" class="font-medium text-emerald-400 underline-offset-4 hover:underline">Sign in</Link>
            </p>
        </form>
    </AuthLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login');
}
</script>

<template>
    <Head title="Login" />

    <div class="flex min-h-screen items-center justify-center bg-slate-100 p-4">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-xl sm:p-8">
            <p class="text-xs tracking-[0.24em] text-slate-500 uppercase">Welcome back</p>
            <h1 class="mt-2 text-3xl font-semibold text-slate-900">Login</h1>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Email</label>
                    <input v-model="form.email" type="email" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Password</label>
                    <input v-model="form.password" type="password" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input v-model="form.remember" type="checkbox" />
                    Remember me
                </label>

                <button :disabled="form.processing" class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">
                    {{ form.processing ? 'Logging in...' : 'Login' }}
                </button>
            </form>

            <p class="mt-4 text-sm text-slate-600">
                No account?
                <Link href="/register" class="font-medium text-slate-900">Register</Link>
            </p>
        </div>
    </div>
</template>

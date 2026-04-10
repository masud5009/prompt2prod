<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/register');
}
</script>

<template>
    <Head title="Register" />

    <div class="flex min-h-screen items-center justify-center bg-slate-100 p-4">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-xl sm:p-8">
            <p class="text-xs tracking-[0.24em] text-slate-500 uppercase">Create account</p>
            <h1 class="mt-2 text-3xl font-semibold text-slate-900">Register</h1>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Name</label>
                    <input v-model="form.name" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                </div>

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

                <div>
                    <label class="block text-sm font-medium text-slate-700">Confirm Password</label>
                    <input v-model="form.password_confirmation" type="password" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                </div>

                <button :disabled="form.processing" class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">
                    {{ form.processing ? 'Creating...' : 'Register' }}
                </button>
            </form>

            <p class="mt-4 text-sm text-slate-600">
                Already have account?
                <Link href="/login" class="font-medium text-slate-900">Login</Link>
            </p>
        </div>
    </div>
</template>

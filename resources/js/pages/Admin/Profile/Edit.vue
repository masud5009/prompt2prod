<script setup lang="ts">
import AdminLayout from '@/components/admin/AdminLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
    admin: {
        name: string;
        email: string;
    };
}>();

const form = useForm({
    name: props.admin.name,
    email: props.admin.email,
    current_password: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.put('/admin/profile', {
        onSuccess: () => {
            form.reset('current_password', 'password', 'password_confirmation');
        },
    });
}
</script>

<template>
    <AdminLayout title="Edit Profile">
        <section class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-5">
            <form class="space-y-4" @submit.prevent="submit">
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

                <hr class="border-slate-200" />

                <div>
                    <label class="block text-sm font-medium text-slate-700">Current Password</label>
                    <input v-model="form.current_password" type="password" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    <p v-if="form.errors.current_password" class="mt-1 text-xs text-red-600">{{ form.errors.current_password }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">New Password</label>
                        <input v-model="form.password" type="password" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Confirm Password</label>
                        <input v-model="form.password_confirmation" type="password" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                </div>

                <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Save changes</button>
            </form>
        </section>
    </AdminLayout>
</template>

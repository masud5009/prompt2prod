<script setup lang="ts">
import AdminLayout from '@/components/admin/AdminLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    users: {
        data: Array<{ id: number; name: string; email: string; is_admin: boolean; package_purchases_count: number }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: { search: string };
}>();

const search = ref(props.filters.search ?? '');
const editForm = useForm({ id: 0, name: '', email: '', is_admin: false });
const editingId = ref<number | null>(null);

function doSearch() {
    router.get('/admin/users', { search: search.value }, { preserveState: true, replace: true });
}

function openEdit(user: { id: number; name: string; email: string; is_admin: boolean }) {
    editingId.value = user.id;
    editForm.id = user.id;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.is_admin = user.is_admin;
}

function updateUser() {
    editForm.put(`/admin/users/${editForm.id}`, {
        onSuccess: () => {
            editingId.value = null;
        },
    });
}
</script>

<template>
    <AdminLayout title="User Management">
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <form class="mb-4 flex gap-2" @submit.prevent="doSearch">
                <input v-model="search" placeholder="Search by name or email" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Search</button>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-slate-500"><tr><th class="px-3 py-2">Name</th><th class="px-3 py-2">Email</th><th class="px-3 py-2">Role</th><th class="px-3 py-2">Purchases</th><th class="px-3 py-2">Actions</th></tr></thead>
                    <tbody>
                        <tr v-for="user in users.data" :key="user.id" class="border-t border-slate-100">
                            <td class="px-3 py-2">{{ user.name }}</td>
                            <td class="px-3 py-2">{{ user.email }}</td>
                            <td class="px-3 py-2">{{ user.is_admin ? 'Admin' : 'Customer' }}</td>
                            <td class="px-3 py-2">{{ user.package_purchases_count }}</td>
                            <td class="px-3 py-2"><button class="rounded-lg border px-3 py-1" @click="openEdit(user)">Edit</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <Link
                    v-for="link in users.links"
                    :key="link.label"
                    :href="link.url || '#'
                    "
                    :class="link.active ? 'bg-slate-900 text-white' : 'bg-white text-slate-700'"
                    class="rounded-lg border px-3 py-1 text-xs"
                    v-html="link.label"
                />
            </div>
        </section>

        <section v-if="editingId" class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h4 class="text-lg font-semibold">Edit User</h4>
                <button type="button" class="rounded-lg border px-3 py-1 text-sm" @click="editingId = null">Close</button>
            </div>

            <form class="mt-4 space-y-3" @submit.prevent="updateUser">
                <input v-model="editForm.name" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                <input v-model="editForm.email" type="email" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                <label class="flex items-center gap-2 text-sm"><input v-model="editForm.is_admin" type="checkbox" /> Is admin</label>
                <div class="flex gap-2">
                    <button type="button" class="flex-1 rounded-xl border px-4 py-2 text-sm" @click="editingId = null">Cancel</button>
                    <button class="flex-1 rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Update</button>
                </div>
            </form>
        </section>
    </AdminLayout>
</template>

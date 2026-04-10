<script setup lang="ts">
import AdminLayout from '@/components/admin/AdminLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface PackageRow {
    id: number;
    name: string;
    slug: string;
    image_quota: number;
    price: string;
    currency: string;
    description?: string | null;
    is_active: boolean;
    purchases_count: number;
}

const props = defineProps<{ packages: PackageRow[] }>();

const createForm = useForm({
    name: '',
    image_quota: 50,
    price: 0,
    currency: 'BDT',
    description: '',
    is_active: true,
});

const editForm = useForm({
    id: 0,
    name: '',
    image_quota: 1,
    price: 0,
    currency: 'BDT',
    description: '',
    is_active: true,
});

const editingId = ref<number | null>(null);

function createPackage() {
    createForm.post('/admin/packages', {
        onSuccess: () => createForm.reset('name', 'description'),
    });
}

function openEdit(pkg: PackageRow) {
    editingId.value = pkg.id;
    editForm.id = pkg.id;
    editForm.name = pkg.name;
    editForm.image_quota = pkg.image_quota;
    editForm.price = Number(pkg.price);
    editForm.currency = pkg.currency;
    editForm.description = pkg.description ?? '';
    editForm.is_active = pkg.is_active;
}

function updatePackage() {
    editForm.put(`/admin/packages/${editForm.id}`, {
        onSuccess: () => {
            editingId.value = null;
        },
    });
}

function togglePackage(id: number) {
    useForm({}).patch(`/admin/packages/${id}/toggle`);
}
</script>

<template>
    <AdminLayout title="Package Management">
        <div class="grid gap-6 xl:grid-cols-[360px_1fr]">
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-lg font-semibold">Create Package</h3>
                <form class="mt-4 space-y-3" @submit.prevent="createPackage">
                    <input v-model="createForm.name" placeholder="Package name" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    <div class="grid grid-cols-2 gap-2">
                        <input v-model.number="createForm.image_quota" type="number" min="1" placeholder="Image quota" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                        <input v-model.number="createForm.price" type="number" min="0" step="0.01" placeholder="Price" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                    <input v-model="createForm.currency" maxlength="3" placeholder="Currency" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm uppercase" />
                    <textarea v-model="createForm.description" rows="3" placeholder="Description" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    <label class="flex items-center gap-2 text-sm text-slate-600"><input v-model="createForm.is_active" type="checkbox" /> Active</label>
                    <button class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Save package</button>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-lg font-semibold">Package List</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-slate-500">
                            <tr><th class="px-3 py-2">Name</th><th class="px-3 py-2">Quota</th><th class="px-3 py-2">Price</th><th class="px-3 py-2">Status</th><th class="px-3 py-2">Actions</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="pkg in props.packages" :key="pkg.id" class="border-t border-slate-100">
                                <td class="px-3 py-2">
                                    <p class="font-medium">{{ pkg.name }}</p>
                                    <p class="text-xs text-slate-500">{{ pkg.slug }}</p>
                                </td>
                                <td class="px-3 py-2">{{ pkg.image_quota }}</td>
                                <td class="px-3 py-2">{{ pkg.price }} {{ pkg.currency }}</td>
                                <td class="px-3 py-2"><span class="capitalize" :class="pkg.is_active ? 'text-emerald-600' : 'text-red-600'">{{ pkg.is_active ? 'active' : 'inactive' }}</span></td>
                                <td class="px-3 py-2">
                                    <div class="flex gap-2">
                                        <button class="rounded-lg border px-3 py-1" @click="openEdit(pkg)">Edit</button>
                                        <button class="rounded-lg border px-3 py-1" @click="togglePackage(pkg.id)">Toggle</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <section v-if="editingId" class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h4 class="text-lg font-semibold">Edit Package</h4>
                <button type="button" class="rounded-lg border px-3 py-1 text-sm" @click="editingId = null">Close</button>
            </div>

            <form class="mt-4 space-y-3" @submit.prevent="updatePackage">
                <input v-model="editForm.name" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                <div class="grid grid-cols-2 gap-2">
                    <input v-model.number="editForm.image_quota" type="number" min="1" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    <input v-model.number="editForm.price" type="number" min="0" step="0.01" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <input v-model="editForm.currency" maxlength="3" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm uppercase" />
                <textarea v-model="editForm.description" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                <label class="flex items-center gap-2 text-sm text-slate-600"><input v-model="editForm.is_active" type="checkbox" /> Active</label>
                <div class="flex gap-2">
                    <button type="button" class="flex-1 rounded-xl border px-4 py-2 text-sm" @click="editingId = null">Cancel</button>
                    <button class="flex-1 rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Update</button>
                </div>
            </form>
        </section>
    </AdminLayout>
</template>

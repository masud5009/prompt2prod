<script setup lang="ts">
import AdminLayout from '@/components/admin/AdminLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
    purchases: {
        data: Array<{
            id: number;
            purchased_images: number;
            used_images: number;
            status: 'active' | 'expired' | 'cancelled';
            expires_at?: string | null;
            user: { name: string; email: string };
            package: { name: string };
        }>;
    };
    users: Array<{ id: number; name: string; email: string }>;
    packages: Array<{ id: number; name: string; image_quota: number }>;
}>();

const createForm = useForm({
    user_id: 0,
    generation_package_id: 0,
    purchased_images: 1,
    status: 'active' as 'active' | 'expired' | 'cancelled',
    expires_at: '',
});

function createPurchase() {
    createForm.post('/admin/purchases', {
        onSuccess: () => {
            createForm.reset('expires_at');
            createForm.purchased_images = 1;
        },
    });
}

function updatePurchase(id: number, status: 'active' | 'expired' | 'cancelled', usedImages: number, expiresAt: string | null) {
    useForm({
        status,
        used_images: usedImages,
        expires_at: expiresAt ?? '',
    }).put(`/admin/purchases/${id}`);
}
</script>

<template>
    <AdminLayout title="Purchase Management">
        <div class="grid gap-6 xl:grid-cols-[360px_1fr]">
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-lg font-semibold">Assign Package</h3>
                <form class="mt-4 space-y-3" @submit.prevent="createPurchase">
                    <select v-model.number="createForm.user_id" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option :value="0" disabled>Select user</option>
                        <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }} ({{ user.email }})</option>
                    </select>
                    <select v-model.number="createForm.generation_package_id" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option :value="0" disabled>Select package</option>
                        <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">{{ pkg.name }} ({{ pkg.image_quota }} images)</option>
                    </select>
                    <input v-model.number="createForm.purchased_images" type="number" min="1" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    <select v-model="createForm.status" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="active">active</option>
                        <option value="expired">expired</option>
                        <option value="cancelled">cancelled</option>
                    </select>
                    <input v-model="createForm.expires_at" type="datetime-local" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    <button class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">Assign</button>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-lg font-semibold">Purchase Records</h3>
                <div class="mt-4 space-y-3">
                    <div v-for="row in purchases.data" :key="row.id" class="rounded-xl border border-slate-200 p-3">
                        <p class="font-medium text-slate-900">{{ row.user.name }} • {{ row.package.name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ row.user.email }}</p>
                        <div class="mt-3 grid gap-2 sm:grid-cols-4">
                            <div class="rounded-lg bg-slate-50 p-2 text-xs">Purchased: <span class="font-medium">{{ row.purchased_images }}</span></div>
                            <div class="rounded-lg bg-slate-50 p-2 text-xs">Used: <span class="font-medium">{{ row.used_images }}</span></div>
                            <div class="rounded-lg bg-slate-50 p-2 text-xs">Remaining: <span class="font-medium">{{ Math.max(0, row.purchased_images - row.used_images) }}</span></div>
                            <div class="rounded-lg bg-slate-50 p-2 text-xs capitalize">Status: <span class="font-medium">{{ row.status }}</span></div>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button class="rounded-lg border px-3 py-1 text-xs" @click="updatePurchase(row.id, 'active', row.used_images, row.expires_at ?? null)">Mark active</button>
                            <button class="rounded-lg border px-3 py-1 text-xs" @click="updatePurchase(row.id, 'expired', row.used_images, row.expires_at ?? null)">Mark expired</button>
                            <button class="rounded-lg border px-3 py-1 text-xs" @click="updatePurchase(row.id, 'cancelled', row.used_images, row.expires_at ?? null)">Cancel</button>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>

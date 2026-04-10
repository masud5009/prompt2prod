<script setup lang="ts">
import AdminLayout from '@/components/admin/AdminLayout.vue';

defineProps<{
    stats: {
        users: number;
        admins: number;
        packages: number;
        activePackages: number;
        purchases: number;
        activePurchases: number;
        totalQuota: number;
        totalUsed: number;
        remainingQuota: number;
    };
    recentPurchases: Array<{
        id: number;
        status: string;
        purchased_images: number;
        used_images: number;
        user: { name: string; email: string };
        package: { name: string };
    }>;
}>();
</script>

<template>
    <AdminLayout title="Dashboard">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4"><p class="text-sm text-slate-500">Users</p><p class="mt-2 text-3xl font-semibold">{{ stats.users }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4"><p class="text-sm text-slate-500">Packages</p><p class="mt-2 text-3xl font-semibold">{{ stats.packages }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4"><p class="text-sm text-slate-500">Active Purchases</p><p class="mt-2 text-3xl font-semibold">{{ stats.activePurchases }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4"><p class="text-sm text-slate-500">Remaining Images</p><p class="mt-2 text-3xl font-semibold">{{ stats.remainingQuota }}</p></div>
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4">
            <h3 class="text-lg font-semibold">Recent Purchases</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-slate-500"><tr><th class="px-3 py-2">User</th><th class="px-3 py-2">Package</th><th class="px-3 py-2">Quota</th><th class="px-3 py-2">Used</th><th class="px-3 py-2">Status</th></tr></thead>
                    <tbody>
                        <tr v-for="row in recentPurchases" :key="row.id" class="border-t border-slate-100">
                            <td class="px-3 py-2">{{ row.user.name }}</td>
                            <td class="px-3 py-2">{{ row.package.name }}</td>
                            <td class="px-3 py-2">{{ row.purchased_images }}</td>
                            <td class="px-3 py-2">{{ row.used_images }}</td>
                            <td class="px-3 py-2 capitalize">{{ row.status }}</td>
                        </tr>
                        <tr v-if="recentPurchases.length === 0"><td colspan="5" class="px-3 py-6 text-center text-slate-500">No purchase data yet.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>

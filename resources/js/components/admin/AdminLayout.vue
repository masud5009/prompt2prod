<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    title: string;
}>();

const page = usePage();

const navItems = [
    { label: 'Dashboard', href: '/admin' },
    { label: 'Packages', href: '/admin/packages' },
    { label: 'Purchases', href: '/admin/purchases' },
    { label: 'Users', href: '/admin/users' },
    { label: 'Profile', href: '/admin/profile' },
];

const activePath = computed(() => page.url);

function isActive(href: string) {
    if (href === '/admin') {
        return activePath.value === '/admin';
    }

    return activePath.value.startsWith(href);
}

function logout() {
    router.post('/admin/logout');
}
</script>

<template>
    <div class="relative isolate z-[9999] min-h-screen w-full bg-slate-100 text-slate-900">
        <div class="relative z-[1] grid min-h-screen w-full grid-cols-1 lg:grid-cols-[250px_minmax(0,1fr)]">
            <aside class="border-r border-slate-200 bg-slate-900 p-4 text-slate-100 lg:sticky lg:top-0 lg:h-screen">
                <div class="rounded-2xl bg-slate-800 p-4">
                    <p class="text-xs tracking-[0.25em] text-slate-400 uppercase">Admin Panel</p>
                    <h1 class="mt-2 text-xl font-semibold text-white">Image Studio</h1>
                </div>

                <nav class="mt-6 space-y-2">
                    <Link
                        v-for="item in navItems"
                        :key="item.href"
                        :href="item.href"
                        :class="
                            isActive(item.href)
                                ? 'bg-teal-500/20 text-teal-200 border-teal-400/30'
                                : 'border-transparent text-slate-300 hover:bg-slate-800 hover:text-white'
                        "
                        class="block rounded-xl border px-4 py-2 text-sm font-medium transition"
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <button
                    type="button"
                    class="mt-6 w-full rounded-xl border border-slate-700 px-4 py-2 text-sm font-medium text-slate-300 transition hover:bg-slate-800 hover:text-white"
                    @click="logout"
                >
                    Logout
                </button>
            </aside>

            <main class="min-w-0 p-5 sm:p-8">
                <header class="mb-6 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <h2 class="text-2xl font-semibold text-slate-900">{{ title }}</h2>
                </header>

                <slot />
            </main>
        </div>
    </div>
</template>

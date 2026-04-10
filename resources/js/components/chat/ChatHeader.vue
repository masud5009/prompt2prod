<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    title: string;
    messageCount: number;
    lastUpdatedAt?: string;
    isGenerating: boolean;
}>();

defineEmits<{
    'new-chat': [];
    'toggle-sidebar': [];
}>();

const subtitle = computed(() => {
    if (props.isGenerating) {
        return 'Rendering a fresh set of concepts';
    }

    if (props.messageCount === 0) {
        return 'Start with a prompt to generate your first image set';
    }

    const turns = Math.ceil(props.messageCount / 2);

    return `${turns} prompt ${turns === 1 ? 'turn' : 'turns'} saved in this thread`;
});

</script>

<template>
    <header class="border-b border-slate-200/70 bg-white/70 px-4 py-4 backdrop-blur-xl sm:px-6 lg:px-10">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-3">
                <button type="button"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-slate-300 hover:text-slate-950 lg:hidden"
                    @click="$emit('toggle-sidebar')">
                    <span class="sr-only">Open sidebar</span>
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h10" />
                    </svg>
                </button>

                <div class="min-w-0">
                    <div class="flex items-center gap-3">
                        <div class="min-w-0">
                            <h2 class="truncate font-display text-2xl font-semibold text-slate-950">
                                {{ title }}
                            </h2>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ subtitle }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>

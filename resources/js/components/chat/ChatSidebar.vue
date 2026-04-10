<script setup lang="ts">
import { computed } from 'vue';

import type { ChatSession } from '@/types/image-generator';

const props = defineProps<{
    sessions: ChatSession[];
    activeSessionId: string | number;
    isOpen: boolean;
}>();

const emit = defineEmits<{
    close: [];
    'new-chat': [];
    select: [sessionId: string | number];
}>();

const hasSessions = computed(() => props.sessions.length > 0);

function formatSessionTime(updatedAt: string) {
    const value = new Date(updatedAt);
    const now = new Date();
    const isSameDay = value.toDateString() === now.toDateString();

    return new Intl.DateTimeFormat(undefined, {
        day: isSameDay ? undefined : 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        month: isSameDay ? undefined : 'short',
    }).format(value);
}

function closeSidebar() {
    emit('close');
}
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-40 bg-slate-950/[0.45] backdrop-blur-[2px] lg:hidden" @click="closeSidebar" />

    <aside
        :class="isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        class="fixed inset-y-0 left-0 z-50 flex w-[320px] flex-col overflow-hidden bg-[linear-gradient(180deg,#081321_0%,#10233A_40%,#17324E_100%)] text-white shadow-[0_32px_120px_rgba(8,19,33,0.45)] transition-transform duration-300 lg:static lg:z-auto lg:w-[340px]"
    >
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,0.18),transparent_38%),radial-gradient(circle_at_bottom,rgba(77,208,180,0.12),transparent_26%)]"
        />
        <div class="relative flex h-full flex-col px-5 py-6">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 shadow-[inset_0_1px_0_rgba(255,255,255,0.18)] ring-1 ring-white/10 backdrop-blur"
                    >
                        <svg viewBox="0 0 48 48" class="h-7 w-7 text-amber-200" fill="none" aria-hidden="true">
                            <path
                                d="M14 31.5C14 24.5964 19.5964 19 26.5 19H34V26.5C34 33.4036 28.4036 39 21.5 39H14V31.5Z"
                                fill="currentColor"
                                fill-opacity="0.95"
                            />
                            <path
                                d="M14 16.5C14 11.2533 18.2533 7 23.5 7H34V17.5C34 22.7467 29.7467 27 24.5 27H14V16.5Z"
                                stroke="white"
                                stroke-opacity="0.9"
                                stroke-width="2.2"
                            />
                        </svg>
                    </div>

                    <div>
                        <p class="font-display text-lg tracking-[0.18em] text-white/[0.65] uppercase">Lustre AI</p>
                        <h1 class="font-display text-2xl font-semibold text-white">Image Studio</h1>
                    </div>
                </div>

                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.06] text-white/75 transition hover:border-white/20 hover:bg-white/10 hover:text-white lg:hidden"
                    @click="closeSidebar"
                >
                    <span class="sr-only">Close sidebar</span>
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" d="M6 6L18 18M18 6L6 18" />
                    </svg>
                </button>
            </div>

            <button
                type="button"
                class="mt-8 inline-flex items-center justify-center gap-2 rounded-[22px] border border-white/[0.12] bg-white/10 px-4 py-3 text-sm font-medium text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.2)] transition hover:-translate-y-0.5 hover:bg-white/[0.14]"
                @click="$emit('new-chat')"
            >
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" d="M12 5v14M5 12h14" />
                </svg>
                New chat
            </button>

            <div class="mt-8 flex items-center justify-between text-[11px] font-medium tracking-[0.24em] text-white/[0.45] uppercase">
                <span>Recent sessions</span>
                <span>{{ sessions.length }}</span>
            </div>

            <div class="mt-4 min-h-0 flex-1 space-y-2 overflow-y-auto pr-1">
                <button
                    v-for="session in sessions"
                    :key="session.id"
                    type="button"
                    :class="
                        session.id === activeSessionId
                            ? 'border-white/20 bg-white/[0.15] shadow-[0_18px_48px_rgba(15,23,42,0.22)]'
                            : 'border-white/0 bg-white/[0.03] hover:border-white/10 hover:bg-white/[0.08]'
                    "
                    class="w-full rounded-[24px] border px-4 py-4 text-left transition duration-200"
                    @click="$emit('select', session.id)"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span
                                    class="inline-flex h-2.5 w-2.5 rounded-full"
                                    :class="session.isDraft ? 'bg-emerald-300 shadow-[0_0_12px_rgba(110,231,183,0.8)]' : 'bg-amber-200/[0.85]'"
                                />
                                <p class="truncate font-display text-base font-medium text-white">
                                    {{ session.title }}
                                </p>
                            </div>
                            <p
                                class="mt-2 [display:-webkit-box] overflow-hidden text-sm leading-6 text-white/60 [-webkit-box-orient:vertical] [-webkit-line-clamp:2]"
                            >
                                {{ session.preview }}
                            </p>
                        </div>

                        <span class="shrink-0 text-xs text-white/[0.45]">
                            {{ formatSessionTime(session.updatedAt) }}
                        </span>
                    </div>
                </button>

                <div
                    v-if="!hasSessions"
                    class="rounded-[24px] border border-dashed border-white/[0.14] bg-white/[0.03] px-4 py-5 text-sm leading-6 text-white/[0.55]"
                >
                    Your generations will appear here once a session starts.
                </div>
            </div>

        </div>
    </aside>
</template>

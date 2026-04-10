<script setup lang="ts">
import { computed } from 'vue';

import ImageResultCard from '@/components/chat/ImageResultCard.vue';
import type { ChatMessage, GeneratedImage, GenerationControls } from '@/types/image-generator';

const props = defineProps<{
    message: ChatMessage;
}>();

defineEmits<{
    'copy-prompt': [prompt: string];
    download: [image: GeneratedImage];
    preview: [payload: { image: GeneratedImage; prompt: string; controls?: GenerationControls }];
    regenerate: [payload: { prompt: string; controls?: GenerationControls }];
    retry: [messageId: string];
}>();

const isUser = computed(() => props.message.role === 'user');
const isLoading = computed(() => props.message.role === 'assistant' && props.message.status === 'loading');
const isError = computed(() => props.message.role === 'assistant' && props.message.status === 'error');
const hasImages = computed(() => (props.message.images?.length ?? 0) > 0);
const skeletonCards = computed(() => Array.from({ length: props.message.controls?.imageCount ?? 1 }, (_, index) => index));
const timeLabel = computed(() =>
    new Intl.DateTimeFormat(undefined, {
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(props.message.createdAt)),
);
</script>

<template>
    <div v-if="isUser" class="flex justify-end">
        <article
            class="max-w-3xl rounded-[30px] bg-[linear-gradient(135deg,#0F172A_0%,#1E293B_45%,#0F766E_100%)] px-5 py-4 text-white shadow-[0_24px_70px_rgba(15,23,42,0.25)] sm:px-6"
        >
            <div class="flex items-center gap-2 text-[11px] font-medium tracking-[0.22em] text-white/[0.58] uppercase">
                <span>You</span>
                <span class="h-1 w-1 rounded-full bg-white/35" />
                <span>{{ timeLabel }}</span>
            </div>
            <p class="mt-3 text-[15px] leading-7 whitespace-pre-wrap text-white/[0.96]">
                {{ message.content }}
            </p>
        </article>
    </div>

    <div v-else class="flex gap-4">
        <div
            class="mt-1 hidden h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-slate-950 text-amber-200 shadow-[0_16px_34px_rgba(15,23,42,0.2)] sm:flex"
        >
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M8 8h8M8 16h8" />
            </svg>
        </div>

        <div class="min-w-0 flex-1 space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <span
                    class="inline-flex items-center gap-2 rounded-full bg-slate-950 px-3 py-1 text-[11px] font-medium tracking-[0.22em] text-white uppercase"
                >
                    <span class="h-2 w-2 rounded-full bg-amber-200" />
                    Gemini renderer
                </span>
                <span v-if="message.latencyMs" class="rounded-full bg-white/70 px-3 py-1 text-xs text-slate-500">
                    {{ (message.latencyMs / 1000).toFixed(1) }}s render time
                </span>
            </div>

            <article
                v-if="isLoading"
                class="overflow-hidden rounded-[32px] border border-white/70 bg-white/[0.78] p-5 shadow-[0_24px_80px_rgba(15,23,42,0.08)] backdrop-blur-xl sm:p-6"
            >
                <div class="flex items-center gap-3">
                    <span class="relative flex h-4 w-4">
                        <span class="absolute inset-0 animate-ping rounded-full bg-emerald-300/70" />
                        <span class="relative h-4 w-4 rounded-full bg-emerald-400" />
                    </span>
                    <div>
                        <p class="font-display text-xl font-semibold text-slate-950">Building visual concepts</p>
                        <p class="mt-1 text-sm text-slate-500">
                            Gemini is composing {{ message.controls?.stylePreset?.toLowerCase() ?? 'premium' }}
                            variations for this prompt.
                        </p>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div v-for="index in skeletonCards" :key="index" class="overflow-hidden rounded-[28px] border border-slate-200 bg-slate-50 p-4">
                        <div class="aspect-[4/5] animate-pulse rounded-[24px] bg-[linear-gradient(135deg,#DBEAFE_0%,#F8FAFC_40%,#FDE68A_100%)]" />
                        <div class="mt-4 h-4 w-1/2 animate-pulse rounded-full bg-slate-200" />
                        <div class="mt-3 h-3 w-2/3 animate-pulse rounded-full bg-slate-200" />
                        <div class="mt-5 grid grid-cols-2 gap-2">
                            <div class="h-10 animate-pulse rounded-2xl bg-slate-200" />
                            <div class="h-10 animate-pulse rounded-2xl bg-slate-200" />
                            <div class="h-10 animate-pulse rounded-2xl bg-slate-200" />
                            <div class="h-10 animate-pulse rounded-2xl bg-slate-200" />
                        </div>
                    </div>
                </div>
            </article>

            <article
                v-else-if="isError"
                class="rounded-[32px] border border-red-200/80 bg-white/[0.92] p-5 shadow-[0_24px_70px_rgba(15,23,42,0.08)] sm:p-6"
            >
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="font-display text-xl font-semibold text-slate-950">Generation failed</p>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                            {{ message.errorMessage ?? 'The renderer could not finish this request.' }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-[20px] bg-slate-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-slate-900"
                        @click="$emit('retry', message.id)"
                    >
                        Retry generation
                    </button>
                </div>
            </article>

            <template v-else>
                <article
                    class="rounded-[32px] border border-white/70 bg-white/80 p-5 shadow-[0_22px_70px_rgba(15,23,42,0.08)] backdrop-blur-xl sm:p-6"
                >
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="max-w-3xl">
                            <p class="font-display text-2xl font-semibold text-slate-950">
                                {{ hasImages ? `Generated ${message.images?.length} polished variants` : 'Generation ready' }}
                            </p>
                            <p v-if="message.revisedPrompt" class="mt-2 text-sm leading-6 text-slate-600">
                                {{ message.revisedPrompt }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-[20px] border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-white hover:text-slate-950"
                            @click="$emit('copy-prompt', message.content)"
                        >
                            Copy prompt
                        </button>
                    </div>

                </article>

                <div v-if="hasImages" class="grid gap-4 md:grid-cols-2">
                    <ImageResultCard
                        v-for="image in message.images"
                        :key="image.id"
                        :controls="message.controls"
                        :image="image"
                        :prompt="message.content"
                        @copy-prompt="$emit('copy-prompt', $event)"
                        @download="$emit('download', $event)"
                        @preview="$emit('preview', { image: $event, prompt: message.content, controls: message.controls })"
                        @regenerate="$emit('regenerate', $event)"
                    />
                </div>
            </template>
        </div>
    </div>
</template>

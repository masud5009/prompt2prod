<script setup lang="ts">
import { nextTick, ref, watch } from 'vue';

import type { AspectRatio, GenerationControls, QualityPreset, StylePreset } from '@/types/image-generator';

const props = defineProps<{
    modelValue: string;
    controls: GenerationControls;
    disabled?: boolean;
    isGenerating?: boolean;
}>();

const emit = defineEmits<{
    submit: [];
    'update:controls': [value: GenerationControls];
    'update:modelValue': [value: string];
}>();

const textareaRef = ref<HTMLTextAreaElement | null>(null);
const controlsOpen = ref(false);

const aspectOptions: AspectRatio[] = ['1:1', '4:5', '3:4', '16:9', '9:16'];
const styleOptions: StylePreset[] = ['Editorial', 'Cinematic', 'Futurist', 'Minimal', 'Analog'];
const qualityOptions: QualityPreset[] = ['Balanced', 'High detail', 'Ultra'];

watch(
    () => props.modelValue,
    () => {
        void resizeTextarea();
    },
    { flush: 'post' },
);

function onInput(event: Event) {
    emit('update:modelValue', (event.target as HTMLTextAreaElement).value);
}

function onKeydown(event: KeyboardEvent) {
    if (event.key !== 'Enter' || event.shiftKey || event.isComposing) {
        return;
    }

    event.preventDefault();
    submitPrompt();
}

function submitPrompt() {
    if (props.disabled || !props.modelValue.trim()) {
        return;
    }

    emit('submit');
}

function updateControl<Key extends keyof GenerationControls>(key: Key, value: GenerationControls[Key]) {
    emit('update:controls', {
        ...props.controls,
        [key]: value,
    });
}

async function resizeTextarea() {
    await nextTick();

    if (!textareaRef.value) {
        return;
    }

    textareaRef.value.style.height = '0px';
    textareaRef.value.style.height = `${Math.min(textareaRef.value.scrollHeight, 220)}px`;
}

function focus() {
    textareaRef.value?.focus();
}

defineExpose({
    focus,
});
</script>

<template>
    <div class="border-t border-slate-200/70 bg-white/[0.72] px-4 py-4 backdrop-blur-xl sm:px-6 lg:px-10">
        <div class="mx-auto max-w-5xl">
            <div class="rounded-[34px] border border-white/80 bg-white/[0.92] p-3 shadow-[0_28px_90px_rgba(15,23,42,0.12)]">
                <div class="flex flex-col gap-3">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
                        <button
                            type="button"
                            class="inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-[24px] border border-slate-200 bg-slate-50 px-4 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-white hover:text-slate-950"
                            @click="controlsOpen = !controlsOpen"
                        >
                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" d="M4 7h16M7 12h10M10 17h4" />
                            </svg>
                            Controls
                        </button>

                        <div
                            class="min-w-0 flex-1 rounded-[28px] bg-[linear-gradient(180deg,rgba(248,250,252,0.95)_0%,rgba(241,245,249,0.95)_100%)] px-4 py-3 shadow-[inset_0_1px_0_rgba(255,255,255,0.8)]"
                        >
                            <textarea
                                ref="textareaRef"
                                :disabled="disabled"
                                :value="modelValue"
                                placeholder="Describe the image you want, lighting, materials, camera framing, and the mood you need."
                                class="min-h-[68px] w-full resize-none border-0 bg-transparent text-[15px] leading-7 text-slate-950 outline-none placeholder:text-slate-400 disabled:cursor-not-allowed disabled:opacity-60"
                                @input="onInput"
                                @keydown="onKeydown"
                            />

                            <div class="mt-3 flex flex-col gap-2 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                                <p>1 output · {{ controls.stylePreset }} · {{ controls.aspectRatio }}</p>
                                <p>Press Enter to send, Shift + Enter for a new line.</p>
                            </div>
                        </div>

                        <button
                            type="button"
                            :disabled="disabled || !modelValue.trim()"
                            class="inline-flex h-14 shrink-0 items-center justify-center gap-2 rounded-[26px] bg-slate-950 px-6 text-sm font-medium text-white shadow-[0_18px_36px_rgba(15,23,42,0.24)] transition hover:-translate-y-0.5 hover:bg-slate-900 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="submitPrompt"
                        >
                            <svg
                                v-if="isGenerating"
                                viewBox="0 0 24 24"
                                class="h-4 w-4 animate-spin"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path stroke-linecap="round" d="M21 12a9 9 0 10-9 9" />
                            </svg>
                            <svg v-else viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h13M13 5l7 7-7 7" />
                            </svg>
                            {{ isGenerating ? 'Generating' : 'Send prompt' }}
                        </button>
                    </div>

                    <Transition name="fade-up">
                        <div v-if="controlsOpen" class="grid gap-4 rounded-[28px] border border-slate-200 bg-slate-50/90 p-4 lg:grid-cols-3">
                            <div>
                                <p class="text-[11px] font-medium tracking-[0.2em] text-slate-400 uppercase">Aspect</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button
                                        v-for="aspect in aspectOptions"
                                        :key="aspect"
                                        type="button"
                                        :class="
                                            controls.aspectRatio === aspect
                                                ? 'border-slate-950 bg-slate-950 text-white'
                                                : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-950'
                                        "
                                        class="rounded-full border px-3 py-2 text-sm transition"
                                        @click="updateControl('aspectRatio', aspect)"
                                    >
                                        {{ aspect }}
                                    </button>
                                </div>
                            </div>

                            <div>
                                <p class="text-[11px] font-medium tracking-[0.2em] text-slate-400 uppercase">Style</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button
                                        v-for="style in styleOptions"
                                        :key="style"
                                        type="button"
                                        :class="
                                            controls.stylePreset === style
                                                ? 'border-teal-900 bg-teal-900 text-white'
                                                : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-950'
                                        "
                                        class="rounded-full border px-3 py-2 text-sm transition"
                                        @click="updateControl('stylePreset', style)"
                                    >
                                        {{ style }}
                                    </button>
                                </div>
                            </div>

                            <div>
                                <p class="text-[11px] font-medium tracking-[0.2em] text-slate-400 uppercase">Quality</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button
                                        v-for="quality in qualityOptions"
                                        :key="quality"
                                        type="button"
                                        :class="
                                            controls.quality === quality
                                                ? 'border-amber-600 bg-amber-500 text-slate-950'
                                                : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-950'
                                        "
                                        class="rounded-full border px-3 py-2 text-sm transition"
                                        @click="updateControl('quality', quality)"
                                    >
                                        {{ quality }}
                                    </button>
                                </div>
                            </div>

                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </div>
</template>

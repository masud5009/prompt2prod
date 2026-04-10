<script setup lang="ts">
import type { GeneratedImage, GenerationControls } from '@/types/image-generator';

defineProps<{
    image: GeneratedImage;
    prompt: string;
    controls?: GenerationControls;
}>();

defineEmits<{
    'copy-prompt': [prompt: string];
    download: [image: GeneratedImage];
    preview: [image: GeneratedImage];
    regenerate: [payload: { prompt: string; controls?: GenerationControls }];
}>();

const actionButtonClass =
    'inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-white hover:text-slate-950';
</script>

<template>
    <article
        class="group overflow-hidden rounded-[30px] border border-white/80 bg-white/[0.92] shadow-[0_22px_70px_rgba(15,23,42,0.12)] transition duration-300 hover:-translate-y-1.5 hover:shadow-[0_28px_80px_rgba(15,23,42,0.16)]"
    >
        <button type="button" class="relative block w-full overflow-hidden" @click="$emit('preview', image)">
            <div
                class="aspect-[4/5] w-full bg-gradient-to-br from-slate-200 via-stone-100 to-slate-100"
                :style="{ aspectRatio: `${image.width} / ${image.height}` }"
            >
                <img :src="image.url" :alt="image.alt" class="h-full w-full object-cover transition duration-700 group-hover:scale-[1.03]" />
            </div>

            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-slate-950/70 via-slate-900/10 to-transparent" />

            <div class="absolute top-4 left-4 flex items-center gap-1.5">
                <span
                    v-for="color in image.palette"
                    :key="color"
                    class="h-3.5 w-3.5 rounded-full border border-white/60 shadow-sm"
                    :style="{ backgroundColor: color }"
                />
            </div>

            <div class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-4 p-4 text-left text-white">
                <div>
                    <p class="font-display text-lg font-semibold">{{ image.label }}</p>
                    <p class="mt-1 text-sm text-white/[0.72]">{{ controls?.stylePreset ?? 'Custom' }} · {{ image.width }} × {{ image.height }}</p>
                </div>

                <span class="rounded-full bg-white/[0.14] px-3 py-1 text-[11px] font-medium tracking-[0.22em] text-white/[0.92] uppercase">
                    {{ image.format }}
                </span>
            </div>
        </button>

        <div class="space-y-4 p-4">
            <div class="flex items-center justify-between gap-3 text-xs text-slate-500">
                <span>Seed {{ image.seed }}</span>
                <span>{{ controls?.quality ?? 'Balanced' }}</span>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <button type="button" :class="actionButtonClass" @click="$emit('preview', image)">Preview</button>
                <button type="button" :class="actionButtonClass" @click="$emit('download', image)">Download</button>
                <button type="button" :class="actionButtonClass" @click="$emit('copy-prompt', prompt)">Copy prompt</button>
                <button type="button" :class="actionButtonClass" @click="$emit('regenerate', { prompt, controls })">Regenerate</button>
            </div>
        </div>
    </article>
</template>

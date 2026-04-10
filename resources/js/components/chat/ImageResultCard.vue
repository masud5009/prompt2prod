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
        </button>

        <div class="space-y-4 p-4">
            <div class="grid grid-cols-2 gap-2">
                <button type="button" :class="actionButtonClass" @click="$emit('preview', image)">Preview</button>
                <button type="button" :class="actionButtonClass" @click="$emit('download', image)">Download</button>
                <button type="button" :class="actionButtonClass" @click="$emit('copy-prompt', prompt)">Copy prompt</button>
                <button type="button" :class="actionButtonClass" @click="$emit('regenerate', { prompt, controls })">Regenerate</button>
            </div>
        </div>
    </article>
</template>

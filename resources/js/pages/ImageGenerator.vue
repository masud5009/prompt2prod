<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

import ChatHeader from '@/components/chat/ChatHeader.vue';
import ChatMessage from '@/components/chat/ChatMessage.vue';
import ChatSidebar from '@/components/chat/ChatSidebar.vue';
import PromptInput from '@/components/chat/PromptInput.vue';
import { generateGeminiImages } from '@/lib/geminiImageGenerator';
import type { ChatMessage as ChatMessageType, ChatSession, GeneratedImage, GenerationControls } from '@/types/image-generator';

interface PreviewState {
    image: GeneratedImage;
    prompt: string;
    controls?: GenerationControls;
}

interface Toast {
    id: string;
    message: string;
    tone: 'success' | 'error' | 'neutral';
}

let idSequence = 0;

const defaultControls: GenerationControls = {
    aspectRatio: '4:5',
    stylePreset: 'Editorial',
    quality: 'High detail',
    imageCount: 1,
};

const draftControls = ref<GenerationControls>({ ...defaultControls });
const prompt = ref('');
const isSidebarOpen = ref(false);
const previewState = ref<PreviewState | null>(null);
const scrollAnchorRef = ref<HTMLDivElement | null>(null);
const composerRef = ref<{ focus: () => void } | null>(null);
const pendingController = ref<AbortController | null>(null);
const toasts = ref<Toast[]>([]);

const sessions = ref<ChatSession[]>(createInitialSessions());
const currentSessionId = ref(sessions.value[0]?.id ?? '');

const currentSession = computed(() => sessions.value.find((session) => session.id === currentSessionId.value));
const hasPendingGeneration = computed(() => sessions.value.some((session) => session.messages.some((message) => message.status === 'loading')));
const currentMessageCount = computed(() => currentSession.value?.messages.length ?? 0);

watch(
    () => currentSessionId.value,
    () => {
        void scrollToBottom('auto');
    },
);

watch(
    () => currentSession.value?.messages.map((message) => `${message.id}:${message.status}:${message.images?.length ?? 0}`).join('|') ?? '',
    () => {
        void scrollToBottom(currentMessageCount.value > 0 ? 'smooth' : 'auto');
    },
    { flush: 'post' },
);

onBeforeUnmount(() => {
    pendingController.value?.abort();
});

function createInitialSessions() {
    const now = Date.now();

    return [
        {
            id: createId('session'),
            title: 'New chat',
            preview: 'Start with a detailed visual brief',
            updatedAt: new Date(now).toISOString(),
            messages: [],
            isDraft: true,
        },
    ];
}

function createUserMessage(content: string, createdAt: string): ChatMessageType {
    return {
        id: createId('user'),
        role: 'user',
        content,
        createdAt,
        status: 'complete',
    };
}

async function submitPrompt(promptOverride?: string, controlsOverride?: GenerationControls) {
    const session = currentSession.value;
    const content = (promptOverride ?? prompt.value).trim();

    if (!session || !content || hasPendingGeneration.value) {
        return;
    }

    const controlsSnapshot = {
        ...(controlsOverride ?? draftControls.value),
        imageCount: 1 as const,
    };
    const createdAt = new Date().toISOString();
    const userMessage = createUserMessage(content, createdAt);
    const assistantMessage: ChatMessageType = {
        id: createId('assistant'),
        role: 'assistant',
        content,
        createdAt,
        status: 'loading',
        controls: controlsSnapshot,
    };

    session.messages.push(userMessage, assistantMessage);
    touchSession(session, content, createdAt);
    sortSessions();
    isSidebarOpen.value = false;

    if (!promptOverride || prompt.value.trim() === content) {
        prompt.value = '';
    }

    await scrollToBottom('smooth');
    void runGeneration(session.id, assistantMessage.id, content, controlsSnapshot);
}

async function runGeneration(sessionId: string, messageId: string, content: string, controls: GenerationControls) {
    const controller = new AbortController();
    pendingController.value = controller;

    try {
        const result = await generateGeminiImages(content, controls, { signal: controller.signal });
        const message = findAssistantMessage(sessionId, messageId);

        if (!message) {
            return;
        }

        message.status = 'complete';
        message.images = result.images;
        message.revisedPrompt = result.revisedPrompt;
        message.latencyMs = result.latencyMs;
        message.errorMessage = undefined;
        pushToast('Images generated successfully with Gemini.', 'success');
    } catch (error) {
        if (error instanceof DOMException && error.name === 'AbortError') {
            return;
        }

        const message = findAssistantMessage(sessionId, messageId);

        if (message) {
            message.status = 'error';
            message.errorMessage = error instanceof Error ? error.message : 'Gemini could not finish this request.';
            message.images = undefined;
            message.revisedPrompt = undefined;
            message.latencyMs = undefined;
        }

        pushToast('Generation failed. Retry the prompt.', 'error');
    } finally {
        if (pendingController.value === controller) {
            pendingController.value = null;
        }
    }
}

function retryMessage(messageId: string) {
    if (hasPendingGeneration.value) {
        return;
    }

    const session = currentSession.value;
    const message = session?.messages.find((item) => item.id === messageId && item.role === 'assistant');

    if (!session || !message?.controls) {
        return;
    }

    message.status = 'loading';
    message.errorMessage = undefined;
    message.images = undefined;
    message.revisedPrompt = undefined;
    message.latencyMs = undefined;
    touchSession(session, message.content, new Date().toISOString(), false);
    sortSessions();
    void runGeneration(session.id, message.id, message.content, message.controls);
}

function findAssistantMessage(sessionId: string, messageId: string) {
    const session = sessions.value.find((item) => item.id === sessionId);

    return session?.messages.find((item) => item.id === messageId && item.role === 'assistant');
}

function startNewChat() {
    const existingDraft = sessions.value.find((session) => session.isDraft && session.messages.length === 0);

    if (existingDraft) {
        currentSessionId.value = existingDraft.id;
    } else {
        const newSession: ChatSession = {
            id: createId('session'),
            title: 'New chat',
            preview: 'Start with a detailed visual brief',
            updatedAt: new Date().toISOString(),
            messages: [],
            isDraft: true,
        };

        sessions.value = [newSession, ...sessions.value];
        currentSessionId.value = newSession.id;
    }

    isSidebarOpen.value = false;
    void nextTick(() => composerRef.value?.focus());
}

function selectSession(sessionId: string) {
    currentSessionId.value = sessionId;
    isSidebarOpen.value = false;
}

function handleRegenerate(payload: { prompt: string; controls?: GenerationControls }) {
    void submitPrompt(payload.prompt, payload.controls);
}

function handlePreview(payload: PreviewState) {
    previewState.value = payload;
}

function closePreview() {
    previewState.value = null;
}

function touchSession(session: ChatSession, preview: string, updatedAt: string, allowRename = true) {
    session.preview = preview;
    session.updatedAt = updatedAt;

    if (allowRename && session.isDraft) {
        session.title = createSessionTitle(preview);
        session.isDraft = false;
    }
}

function sortSessions() {
    sessions.value = [...sessions.value].sort((left, right) => new Date(right.updatedAt).getTime() - new Date(left.updatedAt).getTime());
}

function createSessionTitle(value: string) {
    return value.trim().replace(/\s+/g, ' ').split(' ').slice(0, 4).join(' ');
}

async function copyPrompt(value: string) {
    try {
        await navigator.clipboard.writeText(value);
        pushToast('Prompt copied to clipboard.', 'success');
    } catch {
        pushToast('Clipboard access is unavailable in this browser.', 'error');
    }
}

function downloadImage(image: GeneratedImage) {
    const link = document.createElement('a');
    link.href = image.url;
    link.download = `${slugify(image.prompt)}-${image.label.toLowerCase().replace(/\s+/g, '-')}.svg`;
    link.click();
    pushToast('Mock asset downloaded.', 'neutral');
}

function pushToast(message: string, tone: Toast['tone']) {
    const toast = {
        id: createId('toast'),
        message,
        tone,
    };

    toasts.value = [...toasts.value, toast];
    globalThis.setTimeout(() => {
        toasts.value = toasts.value.filter((item) => item.id !== toast.id);
    }, 2600);
}

async function scrollToBottom(behavior: ScrollBehavior) {
    await nextTick();
    scrollAnchorRef.value?.scrollIntoView({
        behavior,
        block: 'end',
    });
}

function createId(prefix: string) {
    idSequence += 1;

    return `${prefix}-${idSequence}`;
}

function slugify(value: string) {
    return value
        .toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-')
        .slice(0, 48);
}
</script>

<template>
    <Head title="AI Image Studio" />

    <div class="relative min-h-screen overflow-hidden bg-[linear-gradient(180deg,#F6F3EC_0%,#EEF5F8_46%,#F5F7FB_100%)] text-slate-900">
        <div class="pointer-events-none absolute inset-0">
            <div class="soft-grid absolute inset-0 opacity-60" />
            <div class="absolute top-[-8%] left-[-12%] h-[420px] w-[420px] rounded-full bg-amber-200/[0.45] blur-3xl" />
            <div class="absolute right-[-8%] bottom-[-14%] h-[440px] w-[440px] rounded-full bg-teal-200/[0.45] blur-3xl" />
            <div class="absolute top-[12%] right-[16%] h-[260px] w-[260px] rounded-full bg-sky-200/30 blur-3xl" />
        </div>

        <div class="relative flex min-h-screen">
            <ChatSidebar
                :active-session-id="currentSessionId"
                :is-open="isSidebarOpen"
                :sessions="sessions"
                @close="isSidebarOpen = false"
                @new-chat="startNewChat"
                @select="selectSession"
            />

            <div class="relative flex min-h-screen min-w-0 flex-1 flex-col">
                <ChatHeader
                    :is-generating="hasPendingGeneration"
                    :last-updated-at="currentSession?.updatedAt"
                    :message-count="currentMessageCount"
                    :title="currentSession?.title ?? 'Image Studio'"
                    @new-chat="startNewChat"
                    @toggle-sidebar="isSidebarOpen = true"
                />

                <main class="min-h-0 flex-1 overflow-y-auto px-4 py-6 sm:px-6 lg:px-10">
                    <div class="mx-auto flex min-h-full max-w-5xl flex-col">
                        <Transition name="fade-up" mode="out-in">
                            <section
                                v-if="!currentSession?.messages.length"
                                key="empty"
                                class="relative my-auto overflow-hidden rounded-[40px] border border-white/75 bg-white/[0.68] p-8 shadow-[0_28px_120px_rgba(15,23,42,0.1)] backdrop-blur-2xl sm:p-10"
                            >
                                <div
                                    class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(245,158,11,0.14),transparent_28%),radial-gradient(circle_at_bottom_left,rgba(13,148,136,0.14),transparent_26%)]"
                                />
                                <div class="relative">
                                    <span
                                        class="inline-flex rounded-full bg-slate-950 px-4 py-2 text-[11px] font-medium tracking-[0.24em] text-white uppercase"
                                    >
                                        Premium AI image workflows
                                    </span>

                                    <h1 class="mt-6 max-w-3xl font-display text-4xl leading-tight font-semibold text-slate-950 sm:text-5xl">
                                        Build campaign-quality image directions from a single prompt.
                                    </h1>

                                    <p class="mt-5 max-w-2xl text-base leading-8 text-slate-600 sm:text-lg">
                                        Write your own prompt below and Gemini will generate images only when you submit.
                                    </p>

                                    <div class="mt-8 grid gap-4 md:grid-cols-3">
                                        <div class="glass-panel rounded-[28px] p-5">
                                            <p class="text-[11px] font-medium tracking-[0.2em] text-slate-400 uppercase">Workspace</p>
                                            <p class="mt-3 font-display text-2xl text-slate-950">Session history</p>
                                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                                Every prompt and render stays threaded for easy iteration.
                                            </p>
                                        </div>

                                        <div class="glass-panel rounded-[28px] p-5">
                                            <p class="text-[11px] font-medium tracking-[0.2em] text-slate-400 uppercase">Controls</p>
                                            <p class="mt-3 font-display text-2xl text-slate-950">Creative presets</p>
                                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                                Tune style, ratio, quality, and output count without leaving the composer.
                                            </p>
                                        </div>

                                        <div class="glass-panel rounded-[28px] p-5">
                                            <p class="text-[11px] font-medium tracking-[0.2em] text-slate-400 uppercase">Delivery</p>
                                            <p class="mt-3 font-display text-2xl text-slate-950">API-ready shape</p>
                                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                                Replace the mock renderer with a backend call and keep the same message model.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-10 rounded-[28px] border border-white/80 bg-white/80 p-5">
                                        <p class="text-sm leading-7 text-slate-600">
                                            No demo prompts are preloaded. Use the composer to send a prompt and start generating real images.
                                        </p>
                                    </div>
                                </div>
                            </section>

                            <section v-else key="conversation" class="pb-4">
                                <TransitionGroup name="message-fade" tag="div" class="space-y-6">
                                    <ChatMessage
                                        v-for="message in currentSession?.messages"
                                        :key="message.id"
                                        :message="message"
                                        @copy-prompt="copyPrompt"
                                        @download="downloadImage"
                                        @preview="handlePreview"
                                        @regenerate="handleRegenerate"
                                        @retry="retryMessage"
                                    />
                                </TransitionGroup>

                                <div ref="scrollAnchorRef" />
                            </section>
                        </Transition>
                    </div>
                </main>

                <PromptInput
                    ref="composerRef"
                    v-model="prompt"
                    :controls="draftControls"
                    :disabled="hasPendingGeneration"
                    :is-generating="hasPendingGeneration"
                    @submit="submitPrompt"
                    @update:controls="draftControls = $event"
                />
            </div>
        </div>

        <Transition name="fade-scale">
            <div
                v-if="previewState"
                class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/[0.55] p-4 backdrop-blur-md"
                @click.self="closePreview"
            >
                <div
                    class="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-[34px] border border-white/[0.15] bg-slate-950 text-white shadow-[0_30px_120px_rgba(2,6,23,0.55)] lg:flex-row"
                >
                    <div class="flex-1 bg-[linear-gradient(180deg,#0F172A_0%,#111827_100%)] p-4 sm:p-6">
                        <div class="h-full overflow-hidden rounded-[28px] border border-white/10 bg-slate-900">
                            <img :alt="previewState.image.alt" :src="previewState.image.url" class="h-full w-full object-contain" />
                        </div>
                    </div>

                    <aside class="w-full border-t border-white/10 bg-white/[0.03] p-6 lg:w-[360px] lg:border-t-0 lg:border-l">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[11px] font-medium tracking-[0.24em] text-white/[0.45] uppercase">
                                    {{ previewState.image.label }}
                                </p>
                                <h3 class="mt-2 font-display text-3xl font-semibold text-white">Preview</h3>
                            </div>

                            <button
                                type="button"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-white/70 transition hover:bg-white/10 hover:text-white"
                                @click="closePreview"
                            >
                                <span class="sr-only">Close preview</span>
                                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" d="M6 6L18 18M18 6L6 18" />
                                </svg>
                            </button>
                        </div>

                        <p class="mt-5 text-sm leading-7 text-white/[0.72]">
                            {{ previewState.prompt }}
                        </p>

                        <div class="mt-6 grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-[22px] border border-white/10 bg-white/[0.04] p-4">
                                <p class="text-[11px] font-medium tracking-[0.2em] text-white/40 uppercase">Size</p>
                                <p class="mt-2 text-white">{{ previewState.image.width }} × {{ previewState.image.height }}</p>
                            </div>
                            <div class="rounded-[22px] border border-white/10 bg-white/[0.04] p-4">
                                <p class="text-[11px] font-medium tracking-[0.2em] text-white/40 uppercase">Seed</p>
                                <p class="mt-2 text-white">{{ previewState.image.seed }}</p>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-2">
                            <span
                                v-for="color in previewState.image.palette"
                                :key="color"
                                class="h-8 w-8 rounded-full border border-white/20"
                                :style="{ backgroundColor: color }"
                            />
                        </div>

                        <div class="mt-8 grid gap-3">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-[22px] bg-white px-4 py-3 text-sm font-medium text-slate-950 transition hover:bg-slate-100"
                                @click="downloadImage(previewState.image)"
                            >
                                Download
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-[22px] border border-white/[0.12] bg-white/5 px-4 py-3 text-sm font-medium text-white transition hover:bg-white/10"
                                @click="copyPrompt(previewState.prompt)"
                            >
                                Copy prompt
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-[22px] border border-white/[0.12] bg-white/5 px-4 py-3 text-sm font-medium text-white transition hover:bg-white/10"
                                @click="handleRegenerate({ prompt: previewState.prompt, controls: previewState.controls })"
                            >
                                Regenerate
                            </button>
                        </div>
                    </aside>
                </div>
            </div>
        </Transition>

        <TransitionGroup name="toast-stack" tag="div" class="fixed top-4 right-4 z-[70] space-y-3">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                :class="
                    toast.tone === 'success'
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-900'
                        : toast.tone === 'error'
                          ? 'border-red-200 bg-red-50 text-red-900'
                          : 'border-slate-200 bg-white text-slate-900'
                "
                class="rounded-[22px] border px-4 py-3 shadow-[0_18px_40px_rgba(15,23,42,0.12)] backdrop-blur"
            >
                {{ toast.message }}
            </div>
        </TransitionGroup>
    </div>
</template>

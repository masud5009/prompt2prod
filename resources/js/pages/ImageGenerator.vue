<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';

import ChatHeader from '@/components/chat/ChatHeader.vue';
import ChatMessage from '@/components/chat/ChatMessage.vue';
import ChatSidebar from '@/components/chat/ChatSidebar.vue';
import PromptInput from '@/components/chat/PromptInput.vue';
import { downloadImageAsPNG } from '@/lib/imageOverlay';
import type { ChatMessage as ChatMessageType, ChatSession, GeneratedImage, GenerationControls } from '@/types/image-generator';

const props = defineProps<{
    initialSessions?: ChatSession[];
}>();

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
const toasts = ref<Toast[]>([]);

const sessions = ref<ChatSession[]>(normalizeInitialSessions(props.initialSessions));
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

function normalizeInitialSessions(rawSessions?: ChatSession[]) {
    if (!rawSessions || rawSessions.length === 0) {
        return createInitialSessions();
    }

    return rawSessions.map((session: any) => ({
        id: session.id,
        title: session.title,
        preview: session.preview ?? '',
        updatedAt: session.updatedAt ?? session.updated_at ?? new Date().toISOString(),
        messages: (session.messages ?? []).map((message: any) => ({
            id: message.id,
            role: message.role,
            content: message.content,
            createdAt: message.createdAt ?? message.created_at ?? new Date().toISOString(),
            status: message.status,
            controls: message.controls,
            errorMessage: message.errorMessage ?? message.error_message,
            revisedPrompt: message.revisedPrompt ?? message.revised_prompt,
            latencyMs: message.latencyMs ?? message.latency_ms,
            images: (message.images ?? []).map((image: any) => ({
                id: image.id,
                label: image.label,
                url: image.url,
                alt: image.alt,
                width: image.width,
                height: image.height,
                prompt: image.prompt,
                revisedPrompt: image.revisedPrompt ?? image.revised_prompt,
                seed: image.seed,
                palette: Array.isArray(image.palette) ? image.palette : [],
                format: image.format,
            })),
        })),
        isDraft: false,
    }));
}

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

    try {
        const response = await postChatGenerate(session.id, content, controlsSnapshot);
        replaceSession(session.id, response);
        currentSessionId.value = response.id;
        pushToast('Images generated and saved.', 'success');
    } catch (error) {
        const message = findAssistantMessage(session.id, assistantMessage.id);

        if (message) {
            message.status = 'error';
            message.errorMessage = error instanceof Error ? error.message : 'Generation failed.';
            message.images = undefined;
            message.revisedPrompt = undefined;
            message.latencyMs = undefined;
        }

        pushToast('Generation failed. Retry the prompt.', 'error');
    }
}

async function postChatGenerate(sessionId: string | number, content: string, controls: GenerationControls) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const response = await fetch('/chat/generate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
        body: JSON.stringify({
            session_id: typeof sessionId === 'number' ? sessionId : null,
            prompt: content,
            controls,
        }),
    });

    const payload = await response.json();

    if (!response.ok) {
        throw new Error(payload.message ?? 'Generation failed.');
    }

    return normalizeInitialSessions([payload.session])[0];
}

function replaceSession(previousId: string | number, updatedSession: ChatSession) {
    const index = sessions.value.findIndex((item) => item.id === previousId || item.id === updatedSession.id);

    if (index >= 0) {
        const updated = [...sessions.value];
        updated[index] = updatedSession;
        sessions.value = updated.sort((left, right) => new Date(right.updatedAt).getTime() - new Date(left.updatedAt).getTime());
        return;
    }

    sessions.value = [updatedSession, ...sessions.value].sort((left, right) => new Date(right.updatedAt).getTime() - new Date(left.updatedAt).getTime());
}

function retryMessage(messageId: string | number) {
    if (hasPendingGeneration.value) {
        return;
    }

    const session = currentSession.value;
    const message = session?.messages.find((item) => item.id === messageId && item.role === 'assistant');

    if (!session || !message?.controls) {
        return;
    }

    void submitPrompt(message.content, message.controls);
}

function findAssistantMessage(sessionId: string | number, messageId: string | number) {
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

function selectSession(sessionId: string | number) {
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

async function downloadImage(image: GeneratedImage) {
    try {
        await downloadImageAsPNG(image);
        pushToast('Image downloaded.', 'success');
    } catch {
        pushToast('Failed to download image.', 'error');
    }
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
                        <section class="pb-4">
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
                    class="relative overflow-hidden rounded-[28px] border border-white/[0.15] bg-slate-950 p-2 text-white shadow-[0_30px_120px_rgba(2,6,23,0.55)]"
                >
                    <button
                        type="button"
                        class="absolute top-4 right-4 z-10 inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-slate-900/70 text-white/80 transition hover:bg-slate-800 hover:text-white"
                        @click="closePreview"
                    >
                        <span class="sr-only">Close preview</span>
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" d="M6 6L18 18M18 6L6 18" />
                        </svg>
                    </button>

                    <img
                        :alt="previewState.image.alt"
                        :src="previewState.image.url"
                        class="block max-h-[92vh] max-w-[94vw] rounded-[22px] object-contain"
                    />
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

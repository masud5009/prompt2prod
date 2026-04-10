export type AspectRatio = '1:1' | '4:5' | '3:4' | '16:9' | '9:16';

export type StylePreset = 'Editorial' | 'Cinematic' | 'Futurist' | 'Minimal' | 'Analog';

export type QualityPreset = 'Balanced' | 'High detail' | 'Ultra';

export type MessageRole = 'user' | 'assistant';

export type MessageStatus = 'complete' | 'loading' | 'error';

export interface GenerationControls {
    aspectRatio: AspectRatio;
    stylePreset: StylePreset;
    quality: QualityPreset;
    imageCount: 1;
}

export interface GeneratedImage {
    id: string;
    label: string;
    url: string;
    alt: string;
    width: number;
    height: number;
    prompt: string;
    revisedPrompt: string;
    seed: number;
    palette: string[];
    format: 'svg' | 'png' | 'jpeg' | 'webp';
}

export interface ChatMessage {
    id: string;
    role: MessageRole;
    content: string;
    createdAt: string;
    status: MessageStatus;
    controls?: GenerationControls;
    errorMessage?: string;
    images?: GeneratedImage[];
    revisedPrompt?: string;
    latencyMs?: number;
}

export interface ChatSession {
    id: string;
    title: string;
    preview: string;
    updatedAt: string;
    messages: ChatMessage[];
    isDraft?: boolean;
}

export interface MockGenerationResult {
    images: GeneratedImage[];
    revisedPrompt: string;
    latencyMs: number;
}

import type { GenerationControls, MockGenerationResult } from '@/types/image-generator';

interface GenerateImageResponse extends MockGenerationResult {
    message?: string;
}

export async function generateGeminiImages(
    prompt: string,
    controls: GenerationControls,
    options: { signal?: AbortSignal } = {},
): Promise<MockGenerationResult> {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    const response = await fetch('/image/generate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
        body: JSON.stringify({ prompt, controls }),
        signal: options.signal,
    });

    const payload = (await response.json()) as GenerateImageResponse;

    if (!response.ok) {
        throw new Error(payload.message ?? 'Image generation failed.');
    }

    return {
        images: payload.images,
        revisedPrompt: payload.revisedPrompt,
        latencyMs: payload.latencyMs,
    };
}

<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Services\GeminiImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatGenerationController extends \App\Http\Controllers\Controller
{
    public function __invoke(Request $request, GeminiImageService $gemini): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['nullable', 'integer'],
            'prompt' => ['required', 'string', 'max:2000'],
            'controls' => ['required', 'array'],
            'controls.aspectRatio' => ['required', 'in:1:1,4:5,3:4,16:9,9:16'],
            'controls.stylePreset' => ['required', 'in:Editorial,Cinematic,Futurist,Minimal,Analog'],
            'controls.quality' => ['required', 'in:Balanced,High detail,Ultra'],
            'controls.imageCount' => ['required', 'integer', 'in:1'],
        ]);

        $user = $request->user();

        $session = null;

        if (!empty($validated['session_id'])) {
            $session = ChatSession::query()
                ->where('id', (int) $validated['session_id'])
                ->where('user_id', $user->id)
                ->first();
        }

        if (!$session) {
            $session = ChatSession::create([
                'user_id' => $user->id,
                'title' => $this->createSessionTitle($validated['prompt']),
                'preview' => $validated['prompt'],
            ]);
        }

        ChatMessage::create([
            'chat_session_id' => $session->id,
            'role' => 'user',
            'content' => $validated['prompt'],
            'status' => 'complete',
        ]);

        try {
            $result = $gemini->generate($validated['prompt'], $validated['controls']);

            $assistantMessage = ChatMessage::create([
                'chat_session_id' => $session->id,
                'role' => 'assistant',
                'content' => $validated['prompt'],
                'status' => 'complete',
                'controls' => $validated['controls'],
                'revised_prompt' => $result['revisedPrompt'] ?? null,
                'latency_ms' => $result['latencyMs'] ?? null,
            ]);

            foreach (($result['images'] ?? []) as $image) {
                $assistantMessage->images()->create([
                    'label' => $this->sanitizeImageLabel((string) ($image['label'] ?? 'Variant 1')),
                    'url' => (string) ($image['url'] ?? ''),
                    'alt' => $this->sanitizeImageAlt((string) ($image['alt'] ?? 'Generated image')),
                    'width' => (int) ($image['width'] ?? 1200),
                    'height' => (int) ($image['height'] ?? 1500),
                    'prompt' => (string) ($image['prompt'] ?? $validated['prompt']),
                    'revised_prompt' => (string) ($image['revisedPrompt'] ?? $result['revisedPrompt'] ?? $validated['prompt']),
                    'seed' => (int) ($image['seed'] ?? random_int(10000, 99999999)),
                    'palette' => is_array($image['palette'] ?? null) ? $image['palette'] : [],
                    'format' => $this->sanitizeImageFormat((string) ($image['format'] ?? 'png')),
                ]);
            }

            $session->update([
                'preview' => $validated['prompt'],
                'updated_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $safeErrorMessage = $this->sanitizeErrorMessage($exception->getMessage());

            ChatMessage::create([
                'chat_session_id' => $session->id,
                'role' => 'assistant',
                'content' => $validated['prompt'],
                'status' => 'error',
                'controls' => $validated['controls'],
                'error_message' => $safeErrorMessage,
            ]);

            return response()->json([
                'message' => $safeErrorMessage,
            ], 502);
        }

        $session->load([
            'messages' => fn ($query) => $query->with('images')->orderBy('created_at'),
        ]);

        return response()->json([
            'session' => $session,
        ]);
    }

    private function createSessionTitle(string $value): string
    {
        return trim(collect(preg_split('/\s+/', preg_replace('/\s+/', ' ', $value) ?: ''))->filter()->take(4)->implode(' '));
    }

    private function sanitizeErrorMessage(string $message): string
    {
        $trimmed = trim($message);

        if ($trimmed === '') {
            return 'Image generation failed.';
        }

        // Keep DB payload well below TEXT limit for utf8mb4.
        return Str::limit($trimmed, 15000, '...');
    }

    private function sanitizeImageLabel(string $label): string
    {
        $trimmed = trim($label);

        return $trimmed !== '' ? Str::limit($trimmed, 190, '') : 'Variant 1';
    }

    private function sanitizeImageAlt(string $alt): string
    {
        $trimmed = trim($alt);

        return $trimmed !== '' ? Str::limit($trimmed, 240, '') : 'Generated image';
    }

    private function sanitizeImageFormat(string $format): string
    {
        $trimmed = strtolower(trim($format));

        if ($trimmed === '') {
            return 'png';
        }

        return Str::limit($trimmed, 20, '');
    }
}

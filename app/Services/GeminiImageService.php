<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GeminiImageService
{
    private const DEFAULT_IMAGE_MODELS = [
        'gemini-2.5-flash-image-preview',
        'gemini-2.5-flash',
        'imagen-4.0-generate-preview-06-06',
        'imagen-4.0-generate-001',
    ];

    public function generate(string $prompt, array $controls, ?string $requestedModel = null): array
    {
        $apiKey = trim((string) config('services.gemini.key', ''));

        if ($apiKey === '') {
            throw new \RuntimeException('GEMINI_API_KEY is missing. Add it to your .env file.');
        }

        $requestedModel = trim((string) ($requestedModel ?? config('services.gemini.image_model', '')));
        $startedAt = microtime(true);
        $result = $this->requestGeminiImageWithFallback($requestedModel, $apiKey, $this->buildVariantPrompt($prompt, $controls, 1));
        $payload = $result['payload'];

        return [
            'images' => [
                $this->mapImagePayload(
                    imageData: $payload['imageData'],
                    mimeType: $payload['mimeType'],
                    prompt: $prompt,
                    revisedPrompt: !empty($payload['text']) ? (string) $payload['text'] : $this->buildRevisedPrompt($prompt, $controls),
                    aspectRatio: (string) ($controls['aspectRatio'] ?? '4:5'),
                    stylePreset: (string) ($controls['stylePreset'] ?? 'Editorial'),
                    index: 1,
                ),
            ],
            'revisedPrompt' => !empty($payload['text']) ? (string) $payload['text'] : $this->buildRevisedPrompt($prompt, $controls),
            'latencyMs' => (int) round((microtime(true) - $startedAt) * 1000),
            'model' => $result['model'],
        ];
    }

    private function requestGeminiImageWithFallback(string $requestedModel, string $apiKey, string $prompt): array
    {
        $availableModels = $this->listGenerateContentModels($apiKey);
        $candidates = $this->candidateModels($requestedModel, $availableModels);
        $lastError = null;

        foreach ($candidates as $candidate) {
            try {
                return [
                    'model' => $candidate,
                    'payload' => $this->requestGeminiImage($candidate, $apiKey, $prompt),
                ];
            } catch (\RuntimeException $exception) {
                $lastError = $exception;

                if (!$this->isRetryableModelError($exception->getMessage())) {
                    throw $exception;
                }
            }
        }

        if ($availableModels !== []) {
            throw new \RuntimeException(
                'No working image model found for this API key. Set GEMINI_IMAGE_MODEL to one of: '.implode(', ', array_slice($availableModels, 0, 12))
            );
        }

        if ($lastError instanceof \RuntimeException) {
            throw $lastError;
        }

        throw new \RuntimeException('Unable to resolve an available Gemini model for image generation.');
    }

    private function requestGeminiImage(string $model, string $apiKey, string $prompt): array
    {
        $attempts = [
            ['TEXT', 'IMAGE'],
            ['IMAGE'],
            null,
        ];

        $lastError = null;

        foreach ($attempts as $responseModalities) {
            $response = $this->sendGenerateContentRequest($model, $apiKey, $prompt, $responseModalities);

            if ($response->failed()) {
                $body = $response->json();
                $message = data_get($body, 'error.message')
                    ?? data_get($body, 'message')
                    ?? 'Gemini API request failed.';

                $lastError = (string) $message;

                if ($this->isRetryableModalityError($lastError)) {
                    continue;
                }

                throw new \RuntimeException($lastError);
            }

            $parsed = $this->parseGenerateContentResponse($response->json());

            if ($parsed !== null) {
                return $parsed;
            }

            $lastError = "Model {$model} did not return image binary data.";
        }

        throw new \RuntimeException($lastError ?? 'Gemini API request failed.');
    }

    private function sendGenerateContentRequest(string $model, string $apiKey, string $prompt, ?array $responseModalities): Response
    {
        $payload = [
            'contents' => [[
                'role' => 'user',
                'parts' => [
                    ['text' => $prompt],
                ],
            ]],
        ];

        if (is_array($responseModalities)) {
            $payload['generationConfig'] = [
                'responseModalities' => $responseModalities,
            ];
        }

        /** @var Response $response */
        $response = Http::timeout(90)
            ->acceptJson()
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", $payload);

        return $response;
    }

    private function parseGenerateContentResponse(array $body): ?array
    {
        $parts = data_get($body, 'candidates.0.content.parts', []);

        if (!is_array($parts) || $parts === []) {
            return null;
        }

        $text = '';
        $imageData = null;
        $mimeType = null;

        foreach ($parts as $part) {
            if (!$text && is_string(data_get($part, 'text'))) {
                $text = (string) data_get($part, 'text');
            }

            $inlineData = data_get($part, 'inlineData') ?? data_get($part, 'inline_data');
            $candidateData = is_array($inlineData) ? data_get($inlineData, 'data') : null;
            $candidateMime = is_array($inlineData) ? data_get($inlineData, 'mimeType') ?? data_get($inlineData, 'mime_type') : null;

            if (is_string($candidateData) && $candidateData !== '') {
                $imageData = $candidateData;
                $mimeType = is_string($candidateMime) ? $candidateMime : 'image/png';
                break;
            }
        }

        if (!is_string($imageData) || $imageData === '') {
            return null;
        }

        return [
            'text' => $text,
            'imageData' => $imageData,
            'mimeType' => is_string($mimeType) ? $mimeType : 'image/png',
        ];
    }

    private function isRetryableModalityError(string $message): bool
    {
        $normalized = strtolower($message);

        return str_contains($normalized, 'response modalities')
            || str_contains($normalized, 'responsemodalities')
            || str_contains($normalized, 'does not support the requested response modalities')
            || str_contains($normalized, 'invalid value at');
    }

    private function candidateModels(string $requestedModel, array $availableModels): array
    {
        $candidates = [];

        if ($requestedModel !== '') {
            $candidates[] = $requestedModel;
        }

        $imageCapableFromAccount = array_values(array_filter(
            $availableModels,
            static fn (mixed $name): bool => is_string($name)
                && (str_contains(strtolower($name), 'image') || str_contains(strtolower($name), 'imagen'))
        ));

        $nonImageFromAccount = array_values(array_filter(
            $availableModels,
            static fn (mixed $name): bool => is_string($name)
                && !(str_contains(strtolower($name), 'image') || str_contains(strtolower($name), 'imagen'))
        ));

        return array_values(array_unique([
            ...$candidates,
            ...$imageCapableFromAccount,
            ...self::DEFAULT_IMAGE_MODELS,
            ...$nonImageFromAccount,
        ]));
    }

    private function isRetryableModelError(string $message): bool
    {
        $normalized = strtolower($message);

        return str_contains($normalized, 'not found for api version')
            || str_contains($normalized, 'not supported for generatecontent')
            || str_contains($normalized, 'does not support the requested response modalities')
            || str_contains($normalized, 'no longer available to new users')
            || str_contains($normalized, 'did not return image binary data');
    }

    private function listGenerateContentModels(string $apiKey): array
    {
        /** @var Response $response */
        $response = Http::timeout(30)
            ->acceptJson()
            ->get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");

        if ($response->failed()) {
            return [];
        }

        $models = data_get($response->json(), 'models', []);

        if (!is_array($models)) {
            return [];
        }

        $supported = [];

        foreach ($models as $model) {
            $name = data_get($model, 'name');
            $methods = data_get($model, 'supportedGenerationMethods', []);

            if (!is_string($name) || !is_array($methods)) {
                continue;
            }

            if (!in_array('generateContent', $methods, true)) {
                continue;
            }

            $supported[] = str_starts_with($name, 'models/') ? substr($name, 7) : $name;
        }

        usort($supported, static function (string $left, string $right): int {
            $leftLooksImage = str_contains($left, 'image') || str_contains($left, 'imagen');
            $rightLooksImage = str_contains($right, 'image') || str_contains($right, 'imagen');

            if ($leftLooksImage === $rightLooksImage) {
                return strcmp($left, $right);
            }

            return $leftLooksImage ? -1 : 1;
        });

        return array_values(array_unique($supported));
    }

    private function mapImagePayload(
        string $imageData,
        string $mimeType,
        string $prompt,
        string $revisedPrompt,
        string $aspectRatio,
        string $stylePreset,
        int $index,
    ): array {
        $size = $this->sizeForAspect($aspectRatio);
        $seed = random_int(10000, 99999999);
        $format = str_contains($mimeType, '/') ? explode('/', $mimeType, 2)[1] : 'png';

        return [
            'id' => null,
            'label' => 'Variant '.$index,
            'url' => "data:{$mimeType};base64,{$imageData}",
            'alt' => "{$stylePreset} generated image",
            'width' => $size['width'],
            'height' => $size['height'],
            'prompt' => $prompt,
            'revisedPrompt' => $revisedPrompt,
            'seed' => $seed,
            'palette' => $this->paletteForStyle($stylePreset),
            'format' => in_array($format, ['png', 'jpeg', 'webp', 'svg'], true) ? $format : 'png',
        ];
    }

    private function buildVariantPrompt(string $prompt, array $controls, int $variant): string
    {
        return trim(implode("\n", [
            $prompt,
            'Style preset: '.($controls['stylePreset'] ?? 'Editorial'),
            'Aspect ratio: '.($controls['aspectRatio'] ?? '4:5'),
            'Quality: '.($controls['quality'] ?? 'High detail'),
            "Create variation #{$variant} with distinct composition while keeping the same main idea.",
            'Return one high quality image output.',
        ]));
    }

    private function buildRevisedPrompt(string $prompt, array $controls): string
    {
        return trim(sprintf(
            '%s, %s style, %s composition, %s quality',
            $prompt,
            $controls['stylePreset'] ?? 'Editorial',
            $controls['aspectRatio'] ?? '4:5',
            $controls['quality'] ?? 'High detail'
        ));
    }

    private function sizeForAspect(string $aspectRatio): array
    {
        return match ($aspectRatio) {
            '1:1' => ['width' => 1200, 'height' => 1200],
            '4:5' => ['width' => 1200, 'height' => 1500],
            '3:4' => ['width' => 1200, 'height' => 1600],
            '16:9' => ['width' => 1600, 'height' => 900],
            '9:16' => ['width' => 1080, 'height' => 1920],
            default => ['width' => 1200, 'height' => 1500],
        };
    }

    private function paletteForStyle(string $stylePreset): array
    {
        return match ($stylePreset) {
            'Editorial' => ['#F7F1E6', '#D59A62', '#22304A', '#9A5A3A'],
            'Cinematic' => ['#0E2438', '#245E73', '#F2A65A', '#F5E9D0'],
            'Futurist' => ['#071B2A', '#1C6E8C', '#8CD7D8', '#F5F4EE'],
            'Minimal' => ['#F6F4EF', '#D4C4A1', '#2D3748', '#AAB5C3'],
            'Analog' => ['#F1E6D2', '#A26D52', '#41566B', '#7EA095'],
            default => ['#CBD5E1', '#94A3B8', '#475569', '#E2E8F0'],
        };
    }
}

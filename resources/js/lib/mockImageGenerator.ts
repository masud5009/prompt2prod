import type { AspectRatio, GeneratedImage, GenerationControls, MockGenerationResult, StylePreset } from '@/types/image-generator';

const SIZE_MAP: Record<AspectRatio, { width: number; height: number }> = {
    '1:1': { width: 1200, height: 1200 },
    '4:5': { width: 1200, height: 1500 },
    '3:4': { width: 1200, height: 1600 },
    '16:9': { width: 1600, height: 900 },
    '9:16': { width: 1080, height: 1920 },
};

const STYLE_PALETTES: Record<StylePreset, string[][]> = {
    Editorial: [
        ['#F7F1E6', '#D59A62', '#22304A', '#9A5A3A'],
        ['#F4EFE7', '#6E8FA6', '#132238', '#DDBA90'],
        ['#F8F2EC', '#BA7C54', '#24465E', '#98AFB7'],
    ],
    Cinematic: [
        ['#0E2438', '#245E73', '#F2A65A', '#F5E9D0'],
        ['#1E2E46', '#7C583E', '#EAC98C', '#9CC2C6'],
        ['#162033', '#B06C49', '#E7D7B1', '#4F8F92'],
    ],
    Futurist: [
        ['#071B2A', '#1C6E8C', '#8CD7D8', '#F5F4EE'],
        ['#0D2230', '#2F8891', '#F0B56A', '#D8EEF1'],
        ['#0B1D30', '#3A7AA0', '#A4DCE5', '#F7E3C7'],
    ],
    Minimal: [
        ['#F6F4EF', '#D4C4A1', '#2D3748', '#AAB5C3'],
        ['#F7F3EA', '#CFC2B1', '#455468', '#8FA8A8'],
        ['#FBF7F0', '#D8C39C', '#324154', '#BCC7D1'],
    ],
    Analog: [
        ['#F1E6D2', '#A26D52', '#41566B', '#7EA095'],
        ['#EEE1C7', '#B77A5F', '#31485B', '#B9B185'],
        ['#F4E7D8', '#8D5E4A', '#51677C', '#C7A26A'],
    ],
};

const STYLE_DESCRIPTORS: Record<StylePreset, string> = {
    Editorial: 'editorial art direction with premium product styling',
    Cinematic: 'cinematic lighting with dramatic depth and atmosphere',
    Futurist: 'future-facing design language with refined luminous surfaces',
    Minimal: 'minimal set design with restrained luxury composition',
    Analog: 'analog texture with tactile materials and filmic softness',
};

export async function generateMockImages(prompt: string, controls: GenerationControls, options: { signal?: AbortSignal } = {}) {
    const result = buildMockGeneration(prompt, controls);
    const shouldFail = /error|fail|broken|invalid/i.test(prompt);

    await wait(result.latencyMs, options.signal);

    if (shouldFail) {
        throw new Error('The mock renderer hit a temporary failure. Retry the prompt or adjust the style settings.');
    }

    return result;
}

export function buildMockGeneration(prompt: string, controls: GenerationControls, options: { seedOffset?: number } = {}): MockGenerationResult {
    const baseSeed = Math.abs(hashString(`${prompt}:${JSON.stringify(controls)}:${options.seedOffset ?? 0}`));
    const rng = mulberry32(baseSeed);
    const latencyMs = 1400 + Math.round(rng() * 1700);
    const revisedPrompt = buildRevisedPrompt(prompt, controls);
    const images = Array.from({ length: controls.imageCount }, (_, index) =>
        buildImageVariant({
            prompt,
            revisedPrompt,
            controls,
            baseSeed,
            index,
        }),
    );

    return {
        images,
        revisedPrompt,
        latencyMs,
    };
}

function buildImageVariant({
    prompt,
    revisedPrompt,
    controls,
    baseSeed,
    index,
}: {
    prompt: string;
    revisedPrompt: string;
    controls: GenerationControls;
    baseSeed: number;
    index: number;
}): GeneratedImage {
    const variantSeed = baseSeed + (index + 1) * 97;
    const rng = mulberry32(variantSeed);
    const paletteSet = STYLE_PALETTES[controls.stylePreset];
    const palette = paletteSet[Math.floor(rng() * paletteSet.length)] ?? paletteSet[0];
    const size = SIZE_MAP[controls.aspectRatio];
    const title = createShortLabel(prompt, index);

    return {
        id: `img-${variantSeed}`,
        label: `Variant ${index + 1}`,
        url: buildSvgDataUrl({
            prompt,
            title,
            palette,
            width: size.width,
            height: size.height,
            seed: variantSeed,
            stylePreset: controls.stylePreset,
            quality: controls.quality,
        }),
        alt: `${controls.stylePreset} concept for ${prompt}`,
        width: size.width,
        height: size.height,
        prompt,
        revisedPrompt,
        seed: variantSeed,
        palette,
        format: 'svg',
    };
}

function buildSvgDataUrl({
    prompt,
    title,
    palette,
    width,
    height,
    seed,
    stylePreset,
    quality,
}: {
    prompt: string;
    title: string;
    palette: string[];
    width: number;
    height: number;
    seed: number;
    stylePreset: StylePreset;
    quality: GenerationControls['quality'];
}) {
    const rng = mulberry32(seed);
    const shapeA = {
        x: Math.round(width * (0.1 + rng() * 0.25)),
        y: Math.round(height * (0.16 + rng() * 0.2)),
        r: Math.round(width * (0.16 + rng() * 0.12)),
    };
    const shapeB = {
        x: Math.round(width * (0.6 + rng() * 0.18)),
        y: Math.round(height * (0.2 + rng() * 0.18)),
        r: Math.round(width * (0.14 + rng() * 0.16)),
    };
    const glowX = Math.round(width * (0.25 + rng() * 0.45));
    const glowY = Math.round(height * (0.55 + rng() * 0.2));
    const promptLines = chunkText(prompt, 4);
    const strokeOpacity = (0.08 + rng() * 0.12).toFixed(2);

    const svg = `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${width} ${height}" width="${width}" height="${height}" fill="none">
            <defs>
                <linearGradient id="bg" x1="0" y1="0" x2="${width}" y2="${height}" gradientUnits="userSpaceOnUse">
                    <stop stop-color="${palette[0]}"/>
                    <stop offset="0.52" stop-color="${palette[1]}"/>
                    <stop offset="1" stop-color="${palette[2]}"/>
                </linearGradient>
                <radialGradient id="glow" cx="${glowX}" cy="${glowY}" r="${Math.round(width * 0.5)}" gradientUnits="userSpaceOnUse">
                    <stop stop-color="${palette[3]}" stop-opacity="0.95"/>
                    <stop offset="1" stop-color="${palette[3]}" stop-opacity="0"/>
                </radialGradient>
                <filter id="blur">
                    <feGaussianBlur stdDeviation="${Math.max(width, height) * 0.03}"/>
                </filter>
            </defs>

            <rect width="${width}" height="${height}" rx="${Math.round(width * 0.065)}" fill="url(#bg)"/>
            <circle cx="${shapeA.x}" cy="${shapeA.y}" r="${shapeA.r}" fill="${palette[3]}" fill-opacity="0.4" filter="url(#blur)"/>
            <circle cx="${shapeB.x}" cy="${shapeB.y}" r="${shapeB.r}" fill="${palette[0]}" fill-opacity="0.28" filter="url(#blur)"/>
            <circle cx="${glowX}" cy="${glowY}" r="${Math.round(width * 0.35)}" fill="url(#glow)"/>

            <path d="M0 ${Math.round(height * 0.72)} C ${Math.round(width * 0.18)} ${Math.round(height * 0.63)}, ${Math.round(width * 0.35)} ${Math.round(height * 0.84)}, ${Math.round(width * 0.5)} ${Math.round(height * 0.72)} S ${Math.round(width * 0.82)} ${Math.round(height * 0.56)}, ${width} ${Math.round(height * 0.74)} V ${height} H0 Z" fill="${palette[2]}" fill-opacity="0.26"/>
            <path d="M${Math.round(width * 0.12)} ${Math.round(height * 0.2)} C ${Math.round(width * 0.32)} ${Math.round(height * 0.08)}, ${Math.round(width * 0.58)} ${Math.round(height * 0.26)}, ${Math.round(width * 0.85)} ${Math.round(height * 0.16)}" stroke="white" stroke-opacity="${strokeOpacity}" stroke-width="${Math.max(width, height) * 0.012}" stroke-linecap="round"/>

            ${Array.from({ length: 7 }, (_, line) => {
                const y = Math.round(height * (0.16 + line * 0.105));
                return `<line x1="${Math.round(width * 0.08)}" y1="${y}" x2="${Math.round(width * 0.92)}" y2="${y}" stroke="rgba(255,255,255,0.14)" stroke-width="2"/>`;
            }).join('')}

            <rect x="${Math.round(width * 0.08)}" y="${Math.round(height * 0.08)}" width="${Math.round(width * 0.84)}" height="${Math.round(height * 0.84)}" rx="${Math.round(width * 0.045)}" stroke="rgba(255,255,255,0.24)" stroke-width="3"/>

            <text x="${Math.round(width * 0.1)}" y="${Math.round(height * 0.12)}" fill="rgba(255,255,255,0.76)" font-family="Instrument Sans, Arial, sans-serif" font-size="${Math.round(width * 0.024)}" letter-spacing="${width * 0.005}">
                ${stylePreset.toUpperCase()} / ${quality.toUpperCase()}
            </text>
            <text x="${Math.round(width * 0.1)}" y="${Math.round(height * 0.74)}" fill="white" font-family="Instrument Sans, Arial, sans-serif" font-size="${Math.round(width * 0.07)}" font-weight="700">
                ${escapeXml(title)}
            </text>
            ${promptLines
                .map(
                    (line, index) =>
                        `<text x="${Math.round(width * 0.1)}" y="${Math.round(height * (0.82 + index * 0.045))}" fill="rgba(255,255,255,0.82)" font-family="Instrument Sans, Arial, sans-serif" font-size="${Math.round(width * 0.026)}">${escapeXml(line)}</text>`,
                )
                .join('')}
        </svg>
    `;

    return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg.replace(/\s+/g, ' ').trim())}`;
}

function buildRevisedPrompt(prompt: string, controls: GenerationControls) {
    return `${prompt}, ${STYLE_DESCRIPTORS[controls.stylePreset]}, ${controls.aspectRatio} composition, ${controls.quality.toLowerCase()} fidelity`;
}

function createShortLabel(prompt: string, index: number) {
    const cleaned = prompt
        .replace(/[^\w\s-]/g, '')
        .trim()
        .split(/\s+/)
        .slice(0, 3)
        .join(' ');

    return `${cleaned || 'Concept'} ${index + 1}`.toUpperCase();
}

function chunkText(text: string, maxWords: number) {
    const words = text.split(/\s+/).filter(Boolean).slice(0, 8);
    const lines: string[] = [];

    for (let index = 0; index < words.length; index += maxWords) {
        lines.push(words.slice(index, index + maxWords).join(' '));
    }

    return lines.slice(0, 2);
}

function wait(ms: number, signal?: AbortSignal) {
    return new Promise<void>((resolve, reject) => {
        const timeoutId = globalThis.setTimeout(resolve, ms);

        if (!signal) {
            return;
        }

        const abort = () => {
            globalThis.clearTimeout(timeoutId);
            reject(new DOMException('Aborted', 'AbortError'));
        };

        signal.addEventListener('abort', abort, { once: true });
    });
}

function escapeXml(value: string) {
    return value.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&apos;');
}

function hashString(value: string) {
    let hash = 0;

    for (let index = 0; index < value.length; index += 1) {
        hash = (hash << 5) - hash + value.charCodeAt(index);
        hash |= 0;
    }

    return hash;
}

function mulberry32(seed: number) {
    return function next() {
        let value = seed + 0x6d2b79f5;
        value = Math.imul(value ^ (value >>> 15), value | 1);
        value ^= value + Math.imul(value ^ (value >>> 7), value | 61);

        return ((value ^ (value >>> 14)) >>> 0) / 4294967296;
    };
}

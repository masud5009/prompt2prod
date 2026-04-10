import type { GeneratedImage } from '@/types/image-generator';

export interface ImageMetadata {
    description: string;
    size: { width: number; height: number };
    seed: number;
    palette: string[];
    stylePreset?: string;
    quality?: string;
}

/**
 * Add metadata overlay to an image and export as PNG
 */
export async function addMetadataOverlay(imageUrl: string, metadata: ImageMetadata): Promise<Blob> {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => {
            try {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');

                if (!ctx) {
                    throw new Error('Failed to get canvas context');
                }

                canvas.width = img.width;
                canvas.height = img.height;

                // Draw original image
                ctx.drawImage(img, 0, 0);

                // Add dark overlay at bottom for text
                const overlayHeight = 260;
                ctx.fillStyle = 'rgba(15, 23, 42, 0.85)';
                ctx.fillRect(0, canvas.height - overlayHeight, canvas.width, overlayHeight);

                // Add subtle border
                ctx.strokeStyle = 'rgba(255, 255, 255, 0.15)';
                ctx.lineWidth = 2;
                ctx.strokeRect(0, canvas.height - overlayHeight, canvas.width, overlayHeight);

                // Setup text
                const padding = 20;
                const x = padding;
                let y = canvas.height - overlayHeight + padding;
                const lineHeight = 28;

                ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
                ctx.font = 'bold 22px "Instrument Sans", Arial, sans-serif';

                // Draw description (truncated)
                const descriptionLines = wrapText(ctx, metadata.description, canvas.width - padding * 2, 18);
                ctx.font = '16px "Instrument Sans", Arial, sans-serif';
                ctx.fillStyle = 'rgba(255, 255, 255, 0.7)';

                for (let i = 0; i < Math.min(descriptionLines.length, 2); i++) {
                    ctx.fillText(descriptionLines[i], x, y);
                    y += lineHeight - 8;
                }

                y += 12;

                // Draw metadata row (Size, Seed, Quality)
                ctx.font = '14px "Instrument Sans", Arial, sans-serif';
                ctx.fillStyle = 'rgba(255, 255, 255, 0.6)';

                const sizeText = `${metadata.size.width}×${metadata.size.height}`;
                const seedText = `Seed: ${metadata.seed}`;
                const qualityText = metadata.quality || 'High detail';

                const metadataGap = 40;
                ctx.fillText('Size:', x, y);
                ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
                ctx.fillText(sizeText, x + 50, y);

                ctx.fillStyle = 'rgba(255, 255, 255, 0.6)';
                ctx.fillText('Seed:', x + metadataGap + 100, y);
                ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
                ctx.fillText(seedText.split(': ')[1], x + metadataGap + 150, y);

                y += lineHeight;

                // Draw palette
                const paletteY = y + 8;
                const colorSize = 16;
                const colorGap = 8;
                let colorX = x;

                ctx.fillStyle = 'rgba(255, 255, 255, 0.6)';
                ctx.font = '12px "Instrument Sans", Arial, sans-serif';
                ctx.fillText('Palette:', x, paletteY - 4);
                colorX = x + 70;

                for (const color of metadata.palette) {
                    // Color swatch with border
                    ctx.fillStyle = color;
                    ctx.fillRect(colorX, paletteY, colorSize, colorSize);

                    ctx.strokeStyle = 'rgba(255, 255, 255, 0.3)';
                    ctx.lineWidth = 1;
                    ctx.strokeRect(colorX, paletteY, colorSize, colorSize);

                    colorX += colorSize + colorGap;
                }

                // Convert to PNG blob
                canvas.toBlob(
                    (blob) => {
                        if (blob) {
                            resolve(blob);
                        } else {
                            reject(new Error('Failed to create blob'));
                        }
                    },
                    'image/png',
                    0.95,
                );
            } catch (error) {
                reject(error);
            }
        };

        img.onerror = () => {
            reject(new Error('Failed to load image'));
        };

        img.src = imageUrl;
    });
}

/**
 * Wrap text to fit within a maximum width
 */
function wrapText(ctx: CanvasRenderingContext2D, text: string, maxWidth: number, lineCount: number): string[] {
    const words = text.split(' ');
    const lines: string[] = [];
    let currentLine = '';

    for (const word of words) {
        const testLine = currentLine + (currentLine ? ' ' : '') + word;
        const metrics = ctx.measureText(testLine);

        if (metrics.width > maxWidth && currentLine) {
            lines.push(currentLine);
            currentLine = word;

            if (lines.length >= lineCount) {
                break;
            }
        } else {
            currentLine = testLine;
        }
    }

    if (currentLine && lines.length < lineCount) {
        lines.push(currentLine.length > maxWidth / 8 ? currentLine.substring(0, Math.floor(currentLine.length * 0.8)) + '...' : currentLine);
    }

    return lines;
}

/**
 * Download image with metadata overlay as PNG
 */
export async function downloadImageAsPNG(image: GeneratedImage, prompt: string, format: string = 'png'): Promise<void> {
    try {
        const blob = await addMetadataOverlay(image.url, {
            description: prompt,
            size: { width: image.width, height: image.height },
            seed: image.seed,
            palette: image.palette,
        });

        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `${slugify(prompt)}-${image.label.toLowerCase().replace(/\s+/g, '-')}.png`;
        link.click();

        URL.revokeObjectURL(url);
    } catch (error) {
        throw new Error(`Failed to download image: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
}

/**
 * Slugify text for filenames
 */
function slugify(value: string): string {
    return value
        .toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-')
        .slice(0, 48);
}

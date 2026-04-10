import type { GeneratedImage } from '@/types/image-generator';

/**
 * Download the original generated image without any overlay text
 */
export async function downloadImageAsPNG(image: GeneratedImage): Promise<void> {
    try {
        const response = await fetch(image.url);

        if (!response.ok) {
            throw new Error('Failed to fetch image for download');
        }

        const blob = await response.blob();
        const extension = extensionFromBlob(blob, image.format);

        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `${slugify(image.prompt)}-${image.label.toLowerCase().replace(/\s+/g, '-')}.${extension}`;
        link.click();

        URL.revokeObjectURL(url);
    } catch (error) {
        throw new Error(`Failed to download image: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
}

function extensionFromBlob(blob: Blob, fallback?: string): string {
    const mime = blob.type.toLowerCase();

    if (mime.includes('png')) {
        return 'png';
    }

    if (mime.includes('jpeg') || mime.includes('jpg')) {
        return 'jpg';
    }

    if (mime.includes('webp')) {
        return 'webp';
    }

    const cleanedFallback = (fallback ?? '').toLowerCase().trim();
    return cleanedFallback || 'png';
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

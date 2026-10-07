import type { InertiaLinkProps } from '@inertiajs/react';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

/**
 * Validates a redirect URI from an untrusted source (URL params, hash).
 * Blocks javascript:, data:, vbscript: and other dangerous schemes.
 * Only allows http: (localhost dev) and https:.
 */
export function isSafeRedirectUri(uri: string | null): uri is string {
    if (!uri) return false;
    try {
        const url = new URL(uri);
        if (url.protocol === 'https:') return true;
        // allow http only for localhost (dev environments)
        if (url.protocol === 'http:') {
            return url.hostname === 'localhost' || url.hostname === '127.0.0.1';
        }
        return false;
    } catch {
        return false;
    }
}

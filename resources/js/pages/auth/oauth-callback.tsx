import { useEffect } from 'react';
import { useSearchParams } from '@inertiajs/react';
import axios from 'axios';
import { saveAccount } from '@/lib/auth-storage';
import type { AuthResponse } from '@/types/auth';

/**
 * Landing page setelah OAuth redirect dari backend.
 * Backend redirect ke /auth/callback?token=...&refresh=...
 */
export default function OAuthCallback() {
    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const token = params.get('token');
        const refresh = params.get('refresh');
        const expiresIn = parseInt(params.get('expires_in') ?? '3600', 10);

        if (!token || !refresh) {
            window.location.href = '/login?error=oauth_failed';
            return;
        }

        // fetch user info with the new token
        axios
            .get<{ data: AuthResponse['user'] }>('/api/auth/me', {
                headers: { Authorization: `Bearer ${token}` },
            })
            .then((res) => {
                saveAccount({
                    user: res.data as any,
                    access_token: token,
                    refresh_token: refresh,
                    expires_at: Date.now() + expiresIn * 1000,
                });
                const redirectTo = params.get('redirect') ?? '/dashboard';
                window.location.href = redirectTo;
            })
            .catch(() => {
                window.location.href = '/login?error=oauth_failed';
            });
    }, []);

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-50">
            <div className="text-center">
                <div className="mx-auto mb-4 h-8 w-8 animate-spin rounded-full border-2 border-gray-300 border-t-gray-900" />
                <p className="text-sm text-gray-600">Finishing sign in...</p>
            </div>
        </div>
    );
}

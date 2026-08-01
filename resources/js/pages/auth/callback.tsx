import { useEffect } from 'react';
import { saveAccount } from '@/lib/auth-storage';
import axios from 'axios';

export default function OAuthCallback() {
    useEffect(() => {
        async function run() {
            function getParams() {
                if (window.location.hash.length > 1) {
                    return new URLSearchParams(
                        window.location.hash.substring(1),
                    );
                }

                return new URLSearchParams(window.location.search);
            }

            const params = getParams();

            const accessToken = params.get('access_token');
            const refreshToken = params.get('refresh_token');
            const sessionId = params.get('session_id');
            const expiresIn = params.get('expires_in');
            const redirectUri = params.get('redirect_uri');
            const state = params.get('state');

            if (!accessToken) {
                window.location.href = '/login?error=oauth_failed';
                return;
            }

            try {
                const { data } = await axios.get('/api/auth/me', {
                    headers: {
                        Authorization: `Bearer ${accessToken}`,
                    },
                });

                saveAccount({
                    user: data.user,
                    access_token: accessToken,
                    refresh_token: refreshToken ?? '',
                    expires_at: Date.now() + Number(expiresIn ?? 3600) * 1000,
                });
                if (redirectUri) {
                    const target =
                        redirectUri +
                        '#' +
                        new URLSearchParams({
                            access_token: accessToken,
                            refresh_token: refreshToken ?? '',
                            session_id: sessionId ?? '',
                            expires_in: expiresIn ?? '3600',
                            state: state ?? '',
                        });

                    window.location.replace(target);
                    return;
                }

                window.location.replace('/dashboard');
            } catch (e: any) {
                window.location.href = '/login?error=oauth_failed';
                return;
            }
        }

        run();
    }, []);

    return (
        <div className="flex min-h-screen items-center justify-center">
            <p>Signing you in...</p>
        </div>
    );
}

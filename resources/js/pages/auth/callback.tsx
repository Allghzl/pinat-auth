import { useEffect, useState } from 'react';
import { saveAccount } from '@/lib/auth-storage';
import { isSafeRedirectUri } from '@/lib/utils';
import axios from 'axios';
import AppLogoIcon from '@/components/app-logo-icon';

const STEPS = [
    'Connecting to PinatAuth…',
    'Verifying your identity…',
    'Setting up your session…',
];

function OAuthCallback() {
    const [stepIdx, setStepIdx] = useState(0);

    useEffect(() => {
        const ticker = setInterval(
            () => setStepIdx((s) => Math.min(s + 1, STEPS.length - 1)),
            900,
        );

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
                    headers: { Authorization: `Bearer ${accessToken}` },
                });

                saveAccount({
                    user: data.user,
                    access_token: accessToken,
                    refresh_token: refreshToken ?? '',
                    expires_at: Date.now() + Number(expiresIn ?? 3600) * 1000,
                });

                if (redirectUri) {
                    if (!isSafeRedirectUri(redirectUri)) {
                        window.location.href = '/login?error=oauth_failed';
                        return;
                    }
                    window.location.replace(
                        redirectUri +
                            '#' +
                            new URLSearchParams({
                                access_token: accessToken,
                                refresh_token: refreshToken ?? '',
                                session_id: sessionId ?? '',
                                expires_in: expiresIn ?? '3600',
                                state: state ?? '',
                            }),
                    );
                    return;
                }

                window.location.replace('/dashboard');
            } catch {
                window.location.href = '/login?error=oauth_failed';
            }
        }

        run();
        return () => clearInterval(ticker);
    }, []);

    return (
        <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-background">
            {/* Ambient glow */}
            <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                <div className="h-[600px] w-[600px] animate-pulse rounded-full bg-primary/[0.04] blur-3xl" />
            </div>

            <div
                className="animate-in fade-in slide-in-from-bottom-3 relative flex flex-col items-center gap-10 duration-500"
            >
                {/* Logo */}
                <div className="opacity-70">
                    <AppLogoIcon className="block h-10 w-10" />
                </div>

                {/* Spinner ring */}
                <div className="relative h-12 w-12">
                    {/* Track */}
                    <div className="absolute inset-0 rounded-full border-[2.5px] border-border" />
                    {/* Spinner */}
                    <div
                        className="absolute inset-0 animate-spin rounded-full border-[2.5px] border-transparent border-t-foreground"
                        style={{ animationDuration: '0.75s' }}
                    />
                </div>

                {/* Status */}
                <div className="flex flex-col items-center gap-4">
                    <p
                        key={stepIdx}
                        className="animate-in fade-in duration-300 text-sm font-medium text-foreground"
                    >
                        {STEPS[stepIdx]}
                    </p>

                    {/* Dot progress */}
                    <div className="flex items-center gap-1.5">
                        {STEPS.map((_, i) => (
                            <div
                                key={i}
                                className={[
                                    'h-1.5 rounded-full transition-all duration-500',
                                    i < stepIdx
                                        ? 'w-4 bg-foreground/60'
                                        : i === stepIdx
                                          ? 'w-6 bg-foreground'
                                          : 'w-1.5 bg-foreground/20',
                                ].join(' ')}
                            />
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}

OAuthCallback.layout = null;
export default OAuthCallback;

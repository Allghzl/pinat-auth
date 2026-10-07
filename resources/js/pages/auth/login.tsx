import { useState, useEffect } from 'react';
import axios from 'axios';
import { Link } from '@inertiajs/react';
import {
    getSavedAccounts,
    saveAccount,
    removeAccount,
} from '@/lib/auth-storage';
import { isSafeRedirectUri } from '@/lib/utils';
import { AccountCard } from '@/components/account-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLogoIcon from '@/components/app-logo-icon';
import type { AuthResponse } from '@/types/auth';

const GoogleIcon = () => (
    <svg className="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
        <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
        <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
        <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
    </svg>
);

const GitHubIcon = () => (
    <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z" />
    </svg>
);

function Login() {
    const [accounts] = useState(getSavedAccounts());
    const [showForm, setShowForm] = useState(accounts.length === 0);
    const [form, setForm] = useState({ email: '', password: '' });
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const [oauthLoading, setOauthLoading] = useState<'google' | 'github' | null>(null);

    const params = new URLSearchParams(window.location.search);
    const clientId = params.get('client_id');
    const redirectUri = params.get('redirect_uri');
    const state = params.get('state');

    useEffect(() => {
        if (new URLSearchParams(window.location.search).get('error') === 'oauth_failed') {
            setError('OAuth authentication failed. Please try again.');
        }
    }, []);

    function redirectAfterLogin(res: {
        access_token: string;
        refresh_token: string;
        session_id: string;
        expires_in: number;
    }) {
        if (clientId && redirectUri) {
            if (!isSafeRedirectUri(redirectUri)) {
                setError('Invalid redirect URI.');
                return;
            }
            window.location.href =
                redirectUri +
                '#' +
                new URLSearchParams({
                    access_token: res.access_token,
                    refresh_token: res.refresh_token,
                    session_id: res.session_id,
                    expires_in: String(res.expires_in),
                    state: state ?? '',
                });
        } else {
            window.location.href = '/auth/session?token=' + encodeURIComponent(res.access_token);
        }
    }

    async function handleAccountSelect(idx: number) {
        const account = accounts[idx];
        try {
            await axios.get('/api/auth/me', {
                headers: { Authorization: `Bearer ${account.access_token}` },
            });
            redirectAfterLogin(account as any);
        } catch {
            try {
                const res = await axios.post<AuthResponse>('/api/auth/refresh', {
                    refresh_token: account.refresh_token,
                });
                saveAccount({
                    user: res.data.user,
                    access_token: res.data.access_token,
                    refresh_token: res.data.refresh_token,
                    expires_at: Date.now() + res.data.expires_in * 1000,
                });
                redirectAfterLogin({
                    access_token: res.data.access_token,
                    refresh_token: res.data.refresh_token,
                    session_id: (res.data as any).session_id || '',
                    expires_in: res.data.expires_in,
                });
            } catch {
                removeAccount(account.user.id);
                setError('Session expired. Please log in again.');
                setShowForm(true);
            }
        }
    }

    async function handleLogin(e: React.FormEvent) {
        e.preventDefault();
        setLoading(true);
        setError('');
        try {
            const res = await axios.post<AuthResponse>('/api/auth/login', form);
            saveAccount({
                user: res.data.user,
                access_token: res.data.access_token,
                refresh_token: res.data.refresh_token,
                expires_at: Date.now() + res.data.expires_in * 1000,
            });
            redirectAfterLogin({
                access_token: res.data.access_token,
                refresh_token: res.data.refresh_token,
                session_id: (res.data as any).session_id || '',
                expires_in: res.data.expires_in,
            });
        } catch (err: any) {
            setError(err.response?.data?.message || 'Login failed');
        } finally {
            setLoading(false);
        }
    }

    function handleOAuth(provider: 'google' | 'github') {
        setOauthLoading(provider);
        const p = new URLSearchParams();
        if (clientId) p.set('client_id', clientId);
        if (redirectUri) p.set('redirect_uri', redirectUri);
        if (state) p.set('state', state);
        window.location.href = `/api/auth/oauth/${provider}?${p.toString()}`;
    }

    if (!showForm && accounts.length > 0) {
        return (
            <AuthShell>
                <div className="animate-in fade-in slide-in-from-bottom-2 duration-300 w-full space-y-5">
                    <div className="flex flex-col items-center gap-3">
                        <LogoLink />
                        <div className="text-center">
                            <h1 className="text-xl font-semibold tracking-tight">Choose an account</h1>
                            <p className="mt-1 text-sm text-muted-foreground">to continue to PinatAuth</p>
                        </div>
                    </div>

                    <div className="space-y-2">
                        {accounts.map((acc, i) => (
                            <AccountCard
                                key={acc.user.id}
                                account={acc}
                                onSelect={() => handleAccountSelect(i)}
                                onRemove={() => {
                                    removeAccount(acc.user.id);
                                    if (accounts.length === 1) setShowForm(true);
                                }}
                            />
                        ))}
                    </div>

                    <Button
                        variant="outline"
                        className="w-full"
                        onClick={() => setShowForm(true)}
                    >
                        Use another account
                    </Button>
                </div>
            </AuthShell>
        );
    }

    return (
        <AuthShell>
            <div className="animate-in fade-in slide-in-from-bottom-2 duration-300 w-full space-y-6">
                <div className="flex flex-col items-center gap-3">
                    <LogoLink />
                    <div className="text-center">
                        <h1 className="text-xl font-semibold tracking-tight">Sign in</h1>
                        <p className="mt-0.5 text-sm text-muted-foreground">to continue to PinatAuth</p>
                    </div>
                </div>

                {error && (
                    <div
                        role="alert"
                        className="animate-in fade-in duration-200 rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive"
                    >
                        {error}
                    </div>
                )}

                <div className="space-y-2">
                    <OAuthButton
                        onClick={() => handleOAuth('google')}
                        loading={oauthLoading === 'google'}
                        disabled={!!oauthLoading}
                        icon={<GoogleIcon />}
                    >
                        Continue with Google
                    </OAuthButton>
                    <OAuthButton
                        onClick={() => handleOAuth('github')}
                        loading={oauthLoading === 'github'}
                        disabled={!!oauthLoading}
                        icon={<GitHubIcon />}
                    >
                        Continue with GitHub
                    </OAuthButton>
                </div>

                <Divider />

                <form onSubmit={handleLogin} className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            autoComplete="email"
                            value={form.email}
                            onChange={(e) => setForm({ ...form, email: e.target.value })}
                            required
                        />
                    </div>

                    <div className="space-y-1.5">
                        <div className="flex items-center justify-between">
                            <Label htmlFor="password">Password</Label>
                            <Link
                                href="/forgot-password"
                                className="text-xs text-muted-foreground hover:text-foreground transition-colors"
                            >
                                Forgot password?
                            </Link>
                        </div>
                        <Input
                            id="password"
                            type="password"
                            autoComplete="current-password"
                            value={form.password}
                            onChange={(e) => setForm({ ...form, password: e.target.value })}
                            required
                        />
                    </div>

                    <Button
                        type="submit"
                        className="w-full"
                        disabled={loading}
                    >
                        {loading ? <Spinner /> : 'Sign in'}
                    </Button>
                </form>

                {accounts.length > 0 && (
                    <Button
                        variant="ghost"
                        className="w-full text-sm"
                        onClick={() => setShowForm(false)}
                    >
                        ← Back to accounts
                    </Button>
                )}

                <p className="text-center text-sm text-muted-foreground">
                    No account?{' '}
                    <Link href="/register" className="font-medium text-foreground hover:underline">
                        Sign up
                    </Link>
                </p>
            </div>
        </AuthShell>
    );
}

Login.layout = null;
export default Login;

function LogoLink() {
    return (
        <Link href="/" className="opacity-80 hover:opacity-100 transition-opacity">
            <AppLogoIcon className="block h-10 w-10" />
        </Link>
    );
}

function AuthShell({ children }: { children: React.ReactNode }) {
    return (
        <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-background p-4">
            <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                <div className="h-[500px] w-[500px] rounded-full bg-primary/[0.03] blur-3xl" />
            </div>
            <div className="relative w-full max-w-sm">
                {children}
            </div>
        </div>
    );
}

function Divider() {
    return (
        <div className="flex items-center gap-3">
            <div className="h-px flex-1 bg-border" />
            <span className="text-xs text-muted-foreground">or</span>
            <div className="h-px flex-1 bg-border" />
        </div>
    );
}

function Spinner() {
    return (
        <span className="flex items-center gap-2">
            <span
                className="inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"
                aria-hidden="true"
            />
            Signing in…
        </span>
    );
}

function OAuthButton({
    children,
    icon,
    onClick,
    loading,
    disabled,
}: {
    children: React.ReactNode;
    icon: React.ReactNode;
    onClick: () => void;
    loading: boolean;
    disabled: boolean;
}) {
    return (
        <Button
            variant="outline"
            className="relative w-full"
            onClick={onClick}
            disabled={disabled}
            type="button"
        >
            {loading ? (
                <span
                    className="absolute left-4 inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"
                    aria-hidden="true"
                />
            ) : (
                <span className="absolute left-4">{icon}</span>
            )}
            {children}
        </Button>
    );
}

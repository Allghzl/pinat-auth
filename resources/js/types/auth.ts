export type User = {
    id: string;
    name: string;
    email: string;
    username?: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type SavedAccount = {
    user: User;
    access_token: string;
    refresh_token: string;
    expires_at: number;
};

export type AuthResponse = {
    user: User;
    access_token: string;
    refresh_token: string;
    token_type: string;
    expires_in: number;
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};

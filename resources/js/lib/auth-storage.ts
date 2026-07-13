import type { SavedAccount } from '@/types/auth';

const KEY = 'pinat_accounts';

export function getSavedAccounts(): SavedAccount[] {
    const raw = localStorage.getItem(KEY);
    return raw ? JSON.parse(raw) : [];
}

export function saveAccount(account: SavedAccount) {
    const accounts = getSavedAccounts();
    const idx = accounts.findIndex((a) => a.user.id === account.user.id);

    if (idx >= 0) {
        accounts[idx] = account;
    } else {
        accounts.push(account);
    }

    localStorage.setItem(KEY, JSON.stringify(accounts));
}

export function removeAccount(userId: number) {
    const accounts = getSavedAccounts().filter((a) => a.user.id !== userId);
    localStorage.setItem(KEY, JSON.stringify(accounts));
}

export function clearAccounts() {
    localStorage.removeItem(KEY);
}

import type { SavedAccount } from '@/types/auth';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';

type Props = {
    account: SavedAccount;
    onSelect: () => void;
    onRemove: () => void;
};

export function AccountCard({ account, onSelect, onRemove }: Props) {
    const initials = account.user.name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

    return (
        <button
            onClick={onSelect}
            className="group relative flex w-full cursor-pointer items-center gap-3 rounded-xl border border-border bg-card px-4 py-3 text-left transition-all duration-150 hover:border-foreground/20 hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
        >
            <Avatar className="h-10 w-10 shrink-0">
                <AvatarImage src={account.user.avatar} alt={account.user.name} />
                <AvatarFallback className="text-xs font-medium">{initials}</AvatarFallback>
            </Avatar>

            <div className="min-w-0 flex-1">
                <div className="truncate text-sm font-medium">{account.user.name}</div>
                <div className="truncate text-xs text-muted-foreground">{account.user.email}</div>
            </div>

            <Button
                variant="ghost"
                size="icon"
                onClick={(e) => {
                    e.stopPropagation();
                    onRemove();
                }}
                aria-label={`Remove ${account.user.name}`}
                className="h-7 w-7 shrink-0 opacity-0 transition-opacity group-hover:opacity-100 focus-visible:opacity-100"
            >
                <svg className="h-3.5 w-3.5" viewBox="0 0 14 14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true">
                    <path d="M1 1l12 12M13 1L1 13" />
                </svg>
            </Button>
        </button>
    );
}

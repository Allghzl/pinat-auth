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
            className="group relative flex w-full items-center gap-3 rounded-lg border bg-white p-4 text-left transition hover:border-blue-500 hover:shadow-md"
        >
            <Avatar className="h-12 w-12">
                <AvatarImage src={account.user.avatar} alt={account.user.name} />
                <AvatarFallback>{initials}</AvatarFallback>
            </Avatar>
            <div className="flex-1">
                <div className="font-medium">{account.user.name}</div>
                <div className="text-sm text-gray-500">{account.user.email}</div>
            </div>
            <Button
                variant="ghost"
                size="sm"
                onClick={(e) => {
                    e.stopPropagation();
                    onRemove();
                }}
                className="opacity-0 group-hover:opacity-100"
            >
                ✕
            </Button>
        </button>
    );
}

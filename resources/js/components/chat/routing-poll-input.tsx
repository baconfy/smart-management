import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { RoutingPoll } from '@/types/chat';

export function RoutingPollInput({ poll, onSelect }: { poll: RoutingPoll; onSelect: (agentIds: number[]) => void }) {
    const [selected, setSelected] = useState<number[]>([]);

    function toggle(id: number) {
        setSelected((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
    }

    return (
        <div className="mx-auto flex w-full max-w-5xl flex-col gap-3 border-t px-12 py-4">
            <p className="font-mono text-base tracking-tight text-muted-foreground/75">{poll.reasoning}</p>

            <div className="flex flex-wrap items-center gap-2">
                {poll.candidates.map((candidate) => (
                    <Badge
                        key={candidate.id}
                        variant={selected.includes(candidate.id) ? 'default' : 'outline'}
                        className={cn('cursor-pointer font-bold select-none text-shadow-2xs')}
                        onClick={() => toggle(candidate.id)}
                    >
                        {candidate.name}
                        <span className="ml-1 opacity-50">{Math.round(candidate.confidence * 100)}%</span>
                    </Badge>
                ))}
            </div>

            <div className="flex justify-end">
                <Button size="sm" disabled={selected.length === 0} onClick={() => onSelect(selected)}>
                    Ask selected
                </Button>
            </div>
        </div>
    );
}

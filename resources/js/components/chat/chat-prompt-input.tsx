import { SquareIcon } from 'lucide-react';
import { useRef, useState } from 'react';

import { PromptInput, PromptInputFooter, PromptInputSubmit, PromptInputTextarea } from '@/components/ai-elements/prompt-input';
import { Badge } from '@/components/ui/badge';
import { InputGroupButton } from '@/components/ui/input-group';
import { cn } from '@/lib/utils';
import type { ProjectAgent } from '@/types';

export function ChatPromptInput({ onSend, isDisabled, onAbort, agents, defaultSelectedAgentIds = [] }: { onSend: (content: string, agentIds?: number[]) => void; isDisabled: boolean; onAbort?: () => void; agents: ProjectAgent[]; defaultSelectedAgentIds?: number[] }) {
    const [selectedAgentIds, setSelectedAgentIds] = useState<number[]>(defaultSelectedAgentIds);
    const textareaRef = useRef<HTMLTextAreaElement>(null);

    function toggleAgent(id: number) {
        setSelectedAgentIds((prev) => (prev.includes(id) ? prev.filter((a) => a !== id) : [...prev, id]));
        textareaRef.current?.focus();
    }

    function handleSubmit({ text }: { text: string }) {
        if (!text.trim()) {
            return;
        }

        onSend(text.trim(), selectedAgentIds.length > 0 ? selectedAgentIds : undefined);
    }

    return (
        <PromptInput onSubmit={handleSubmit}>
            <PromptInputTextarea ref={textareaRef} placeholder="Send a message..." disabled={isDisabled} className="px-4 py-3.5" autoFocus={true} />

            <PromptInputFooter>
                <div className={cn('flex items-center gap-2', { 'cursor-not-allowed opacity-50': isDisabled })}>
                    {agents.map((agent) => (
                        <Badge key={agent.id} variant={selectedAgentIds.includes(agent.id) ? 'default' : 'outline'} className={cn('font-bold select-none text-shadow-2xs', { 'cursor-pointer': !isDisabled })} onClick={() => !isDisabled && toggleAgent(agent.id)}>
                            {agent.name}
                        </Badge>
                    ))}
                </div>
                {onAbort ? (
                    <InputGroupButton size="icon-sm" type="button" onClick={onAbort} aria-label="Stop">
                        <SquareIcon className="size-4" />
                    </InputGroupButton>
                ) : (
                    <PromptInputSubmit disabled={isDisabled} />
                )}
            </PromptInputFooter>
        </PromptInput>
    );
}

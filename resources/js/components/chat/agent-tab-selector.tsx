import { cn } from '@/lib/utils';

export interface AgentTab {
    agentId: number;
    name: string;
    type: string;
    isStreaming?: boolean;
    hasError?: boolean;
}

interface AgentTabSelectorProps {
    agents: AgentTab[];
    activeIndex: number;
    onSelect: (index: number) => void;
}

export function AgentTabSelector({ agents, activeIndex, onSelect }: AgentTabSelectorProps) {
    if (agents.length <= 1) return null;

    return (
        <div
            role="tablist"
            aria-label="Agent responses"
            className="flex items-center gap-1 border-b py-1.5"
            onKeyDown={(e) => {
                if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    onSelect(Math.min(activeIndex + 1, agents.length - 1));
                } else if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    onSelect(Math.max(activeIndex - 1, 0));
                }
            }}
        >
            {agents.map((agent, index) => (
                <button key={agent.agentId} role="tab" aria-selected={index === activeIndex} aria-controls={`agent-panel-${agent.agentId}`} tabIndex={index === activeIndex ? 0 : -1} onClick={() => onSelect(index)} className={cn('flex h-7 clickable items-center justify-center px-2 transition-colors', 'gap-0.5 rounded text-xs leading-none font-bold uppercase', index === activeIndex ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted', agent.isStreaming && index !== activeIndex && 'animate-pulse', agent.hasError && 'text-destructive')}>
                    {agent.name}
                    {agent.isStreaming && (
                        <span className="ml-1.5 inline-flex gap-0.5">
                            <span className="size-1 animate-bounce rounded-full bg-current [animation-delay:-0.3s]" />
                            <span className="size-1 animate-bounce rounded-full bg-current [animation-delay:-0.15s]" />
                            <span className="size-1 animate-bounce rounded-full bg-current" />
                        </span>
                    )}
                </button>
            ))}
        </div>
    );
}

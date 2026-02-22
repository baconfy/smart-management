import { Bot } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import type { ProjectAgent } from '@/types';

export function AgentCard({ agent, onEdit }: { agent: ProjectAgent; onEdit: () => void }) {
    return (
        <div className="clickable flex items-start justify-between gap-4 rounded-lg border border-border bg-card p-4" onClick={onEdit}>
            <div className="flex items-start gap-3">
                <div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <Bot className="size-4" />
                </div>
                <div className="space-y-1">
                    <div className="flex items-center gap-2">
                        <span className="text-sm font-semibold">{agent.name}</span>
                        <Badge variant={agent.is_default ? 'secondary' : 'outline'}>{agent.is_default ? agent.type : 'custom'}</Badge>
                    </div>
                    {agent.model && <p className="text-xs text-muted-foreground">Model: {agent.model}</p>}
                    {agent.tools.length > 0 && (
                        <div className="flex flex-wrap gap-1 pt-1">
                            {agent.tools.map((tool) => (
                                <Badge key={tool} variant="outline" className="text-[10px]">
                                    {tool}
                                </Badge>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

import { BotIcon } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { ProjectAgent } from '@/types';

export function AgentCard({ agent, onEdit }: { agent: ProjectAgent; onEdit: () => void }) {
    return (
        <Card onClick={onEdit} className="clickable">
            <CardHeader className="flex items-center gap-2 border-b">
                <div className="flex size-12 items-center justify-center rounded-lg bg-primary/25">
                    <BotIcon className="size-8 text-primary" />
                </div>

                <div>
                    <CardTitle>{agent.name}</CardTitle>
                    <CardDescription>Model: {agent.model ?? 'default'}</CardDescription>
                </div>
            </CardHeader>
            <CardContent>
                {agent.tools.length <= 0 ? (
                    <p className="text-center text-muted-foreground">No tool selected</p>
                ) : (
                    <div className="space-y-2">
                        <h1 className="font-bold text-lg">Available tools:</h1>
                        <ul className="opacity-50 list-disc px-6">
                            {agent.tools.map((tool) => (
                                <li className="font-medium tracking-wide text-base">{tool}</li>
                            ))}
                        </ul>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

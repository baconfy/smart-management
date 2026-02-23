import { Link } from '@inertiajs/react';
import { GavelIcon, MessageCircleMore } from 'lucide-react';
import { useState } from 'react';
import { ProjectNavPanel } from '@/components/navigation/project-nav-panel';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { show } from '@/routes/projects';
import { index as conversations } from '@/routes/projects/conversations';
import { index } from '@/routes/projects/decisions';
import type { BreadcrumbItem, Decision, Project } from '@/types';

const rotations = ['-rotate-1', 'rotate-1', '-rotate-0.5', 'rotate-0.5', '-rotate-1.5', 'rotate-1.5'] as const;
const statusColors = {
    active: { bg: 'bg-emerald-400/90', text: 'text-emerald-950', badge: 'bg-emerald-600/20 text-emerald-950/70' },
    superseded: { bg: 'bg-amber-300/90', text: 'text-amber-950', badge: 'bg-amber-600/20 text-amber-950/70' },
    deprecated: { bg: 'bg-red-300/90', text: 'text-red-950', badge: 'bg-red-600/20 text-red-950/70' },
} as const;

export default function DecisionIndex({ project, decisions }: { project: Project; decisions: Decision[] }) {
    const [selected, setSelected] = useState<Decision | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: 'Decisions', href: index(project.ulid).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ProjectNavPanel project={project} />}>
            <div className="mx-auto w-full max-w-5xl flex-1 p-6">
                {decisions.length === 0 ? (
                    <Empty className="flex h-full items-center justify-center">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <GavelIcon />
                            </EmptyMedia>
                            <EmptyTitle>No decisions yet.</EmptyTitle>
                            <EmptyDescription>Chat with the Architect agent to create decisions.</EmptyDescription>
                        </EmptyHeader>
                        <EmptyContent className="flex-row justify-center gap-2">
                            <Button size="lg" render={<Link href={conversations({ project: project.ulid })} />}>
                                <MessageCircleMore /> Start a conversation
                            </Button>
                        </EmptyContent>
                    </Empty>
                ) : (
                    <div className="grid grid-cols-2 gap-5 lg:grid-cols-4">
                        {decisions.map((decision, i) => {
                            const colors = statusColors[decision.status];
                            const rotation = rotations[i % rotations.length];

                            return (
                                <button key={decision.id} type="button" onClick={() => setSelected(decision)} className={`${colors.bg} ${rotation} flex aspect-square clickable flex-col rounded-sm p-5 text-left shadow-md transition-all hover:scale-105 hover:rotate-0 hover:shadow-lg`}>
                                    <h3 className={`mt-2 line-clamp-3 text-sm leading-snug font-bold ${colors.text}`}>{decision.title}</h3>
                                    <p className={`mt-2 line-clamp-5 text-xs leading-relaxed ${colors.text} opacity-60`}>{decision.reasoning}</p>
                                </button>
                            );
                        })}
                    </div>
                )}
            </div>

            <Dialog open={!!selected} onOpenChange={(open) => !open && setSelected(null)}>
                {selected && (
                    <DialogContent className="w-full max-w-3xl" showCloseButton={false}>
                        <DialogHeader>
                            <DialogTitle>{selected.title}</DialogTitle>
                            <DialogDescription>{selected.choice}</DialogDescription>
                        </DialogHeader>

                        <Badge className="leading-relaxed font-bold tracking-tighter uppercase">{selected.status}</Badge>

                        <div className="space-y-4">
                            <div>
                                <h4 className="text-xs leading-relaxed font-bold tracking-tighter text-muted-foreground/50 uppercase">Reasoning</h4>
                                <p className="text-sm leading-relaxed text-muted-foreground">{selected.reasoning}</p>
                            </div>

                            {selected.alternatives_considered && (
                                <div>
                                    <h4 className="text-xs leading-relaxed font-bold tracking-tighter text-muted-foreground/50 uppercase">Alternatives considered</h4>
                                    <p className="text-sm leading-relaxed text-muted-foreground">{Array.isArray(selected.alternatives_considered) ? selected.alternatives_considered.join(', ') : selected.alternatives_considered}</p>
                                </div>
                            )}

                            {selected.context && (
                                <div>
                                    <h4 className="text-xs leading-relaxed font-bold tracking-tighter text-muted-foreground/50 uppercase">Context</h4>
                                    <p className="text-sm leading-relaxed text-muted-foreground">{selected.context}</p>
                                </div>
                            )}
                        </div>
                    </DialogContent>
                )}
            </Dialog>
        </AppLayout>
    );
}

import { Link } from '@inertiajs/react';
import { GavelIcon, MessageCircleMore } from 'lucide-react';
import { ProjectNavPanel } from '@/components/navigation/project-nav-panel';
import { Button } from '@/components/ui/button';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { show } from '@/routes/projects';
import { index as conversations } from '@/routes/projects/conversations';
import type { BreadcrumbItem } from '@/types';
import type { BusinessRule, Project } from '@/types';

type Props = {
    project: Project;
    businessRules: BusinessRule[];
};

const STATUS_COLORS: Record<string, string> = {
    active: 'bg-green-500/10 border-green-500/30 text-green-400',
    deprecated: 'bg-red-500/10 border-red-500/30 text-red-400',
};

const CATEGORY_COLORS: Record<string, string> = {
    billing: 'bg-blue-500/20 text-blue-300',
    security: 'bg-amber-500/20 text-amber-300',
    compliance: 'bg-purple-500/20 text-purple-300',
    operations: 'bg-teal-500/20 text-teal-300',
};

export default function BusinessRulesIndex({ project, businessRules }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: 'Business Rules', href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ProjectNavPanel project={project} />}>
            <div className="mx-auto w-full max-w-5xl space-y-6 p-6">
                {businessRules.length === 0 ? (
                    <Empty className="flex h-full items-center justify-center">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <GavelIcon />
                            </EmptyMedia>
                            <EmptyTitle>No business rules yet.</EmptyTitle>
                            <EmptyDescription>Chat with the Analyst agent to create decisions.</EmptyDescription>
                        </EmptyHeader>
                        <EmptyContent className="flex-row justify-center gap-2">
                            <Button size="lg" render={<Link href={conversations(project.ulid)} />}>
                                <MessageCircleMore /> Start a conversation
                            </Button>
                        </EmptyContent>
                    </Empty>
                ) : (
                    <div className="space-y-3">
                        {businessRules.map((rule) => (
                            <div key={rule.id} className={`rounded-xl border px-5 py-4 ${STATUS_COLORS[rule.status] ?? 'border-border bg-muted text-muted-foreground'}`}>
                                <div className="flex items-start justify-between gap-4">
                                    <div className="min-w-0 flex-1">
                                        <h3 className="font-medium tracking-tight">{rule.title}</h3>
                                        <p className="mt-1 text-sm opacity-80">{rule.description}</p>
                                    </div>
                                    <div className="flex shrink-0 items-center gap-2">
                                        <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${CATEGORY_COLORS[rule.category] ?? 'bg-muted text-muted-foreground'}`}>{rule.category}</span>
                                        <span className="rounded-full bg-white/5 px-2.5 py-0.5 text-xs font-medium">{rule.status}</span>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

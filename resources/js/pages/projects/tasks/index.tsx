import { Link } from '@inertiajs/react';
import { GavelIcon, MessageCircleMore } from 'lucide-react';
import { ProjectNavPanel } from '@/components/navigation/project-nav-panel';
import { Button } from '@/components/ui/button';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { show } from '@/routes/projects';
import { index as conversations } from '@/routes/projects/conversations';
import { show as showTask } from '@/routes/projects/tasks';
import type { BreadcrumbItem } from '@/types';
import type { Project, Task } from '@/types/models';

type Props = {
    project: Project;
    tasks: Task[];
};

const STATUS_COLORS: Record<string, string> = {
    backlog: 'bg-zinc-500/20 text-zinc-300',
    in_progress: 'bg-blue-500/20 text-blue-300',
    done: 'bg-green-500/20 text-green-300',
    blocked: 'bg-red-500/20 text-red-300',
};

const PRIORITY_COLORS: Record<string, string> = {
    high: 'bg-red-500/20 text-red-300',
    medium: 'bg-amber-500/20 text-amber-300',
    low: 'bg-zinc-500/20 text-zinc-300',
};

export default function TasksIndex({ project, tasks }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: 'Tasks', href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ProjectNavPanel project={project} />}>
            <div className="mx-auto w-full max-w-5xl p-6">
                {tasks.length === 0 ? (
                    <Empty className="flex h-full items-center justify-center">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <GavelIcon />
                            </EmptyMedia>
                            <EmptyTitle>No tasks yet.</EmptyTitle>
                            <EmptyDescription>Chat with the Architect agent to create decisions.</EmptyDescription>
                        </EmptyHeader>
                        <EmptyContent className="flex-row justify-center gap-2">
                            <Button size="lg" render={<Link href={conversations(project.ulid)} />}>
                                <MessageCircleMore /> Start a conversation
                            </Button>
                        </EmptyContent>
                    </Empty>
                ) : (
                    <div className="space-y-2">
                        {tasks.map((task) => (
                            <Link key={task.id} href={showTask({ project: project.ulid, task: task.ulid }).url} className="flex items-center gap-3 rounded-xl border border-border bg-muted/50 px-5 py-4 transition-colors hover:bg-muted">
                                <div className="min-w-0 flex-1">
                                    <h3 className="font-medium tracking-tight text-foreground">{task.title}</h3>
                                    {task.phase && <span className="text-xs text-muted-foreground">{task.phase}</span>}
                                </div>
                                <div className="flex shrink-0 items-center gap-2">
                                    <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${PRIORITY_COLORS[task.priority] ?? ''}`}>{task.priority}</span>
                                    <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${STATUS_COLORS[task.status] ?? ''}`}>{task.status}</span>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

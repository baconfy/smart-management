import { Link } from '@inertiajs/react';
import { ProjectNavPanel } from '@/components/navigation/project-nav-panel';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { show } from '@/routes/projects';
import { index as tasksIndex, show as showTask } from '@/routes/projects/tasks';
import type { BreadcrumbItem } from '@/types';
import type { ImplementationNote, Project, Task } from '@/types/models';

type Props = {
    project: Project;
    task: Task;
    subtasks: Task[];
    implementationNotes: ImplementationNote[];
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

export default function TaskShow({ project, task, subtasks, implementationNotes }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: 'Tasks', href: tasksIndex(project.ulid).url },
        { title: task.title, href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ProjectNavPanel project={project} />}>
            <div className="mx-auto w-full max-w-5xl space-y-8 p-6">
                {/* Header */}
                <div className="space-y-3">
                    <h1 className="text-2xl font-semibold tracking-tight">{task.title}</h1>
                    <div className="flex items-center gap-2">
                        <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${STATUS_COLORS[task.status] ?? ''}`}>{task.status}</span>
                        <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${PRIORITY_COLORS[task.priority] ?? ''}`}>{task.priority}</span>
                        {task.phase && <span className="rounded-full bg-muted px-2.5 py-0.5 text-xs text-muted-foreground">{task.phase}</span>}
                        {task.milestone && <span className="rounded-full bg-muted px-2.5 py-0.5 text-xs text-muted-foreground">{task.milestone}</span>}
                        {task.estimate && <span className="rounded-full bg-muted px-2.5 py-0.5 text-xs text-muted-foreground">{task.estimate}</span>}
                    </div>
                    <p className="text-sm text-muted-foreground">{task.description}</p>
                </div>

                {/* Subtasks */}
                {subtasks.length > 0 && (
                    <div className="space-y-3">
                        <h2 className="text-sm font-medium tracking-wider text-muted-foreground uppercase">Subtasks</h2>
                        <div className="space-y-2">
                            {subtasks.map((sub) => (
                                <Link key={sub.id} href={showTask({ project: project.ulid, task: sub.ulid }).url} className="flex items-center gap-3 rounded-lg border border-border bg-muted/50 px-4 py-3 transition-colors hover:bg-muted">
                                    <span className="flex-1 text-sm font-medium">{sub.title}</span>
                                    <span className={`rounded-full px-2 py-0.5 text-xs ${STATUS_COLORS[sub.status] ?? ''}`}>{sub.status}</span>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}

                {/* Implementation Notes */}
                {implementationNotes.length > 0 && (
                    <div className="space-y-3">
                        <h2 className="text-sm font-medium tracking-wider text-muted-foreground uppercase">Implementation Notes</h2>
                        <Accordion>
                            {implementationNotes.map((note) => (
                                <AccordionItem key={note.id} value={String(note.id)}>
                                    <AccordionTrigger>{note.title}</AccordionTrigger>
                                    <AccordionContent>
                                        <p className="text-muted-foreground">{note.content}</p>
                                        {note.code_snippets?.map((snippet, i) => (
                                            <pre key={i} className="mt-3 overflow-x-auto rounded-lg bg-black/30 p-4 text-xs">
                                                <code>{snippet.code}</code>
                                            </pre>
                                        ))}
                                    </AccordionContent>
                                </AccordionItem>
                            ))}
                        </Accordion>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

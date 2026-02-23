import { Link } from '@inertiajs/react';
import { CircleCheck, Gavel, ListChecks, MessageSquare, Scale } from 'lucide-react';
import { ProjectNavPanel } from '@/components/navigation/project-nav-panel';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { show } from '@/routes/projects';
import { index as tasksIndex } from '@/routes/projects/tasks';
import { index as decisionsIndex } from '@/routes/projects/decisions';
import type { BreadcrumbItem } from '@/types';
import type { ProjectDashboard } from '@/types/models';

function timeAgo(date: string): string {
    const seconds = Math.floor((Date.now() - new Date(date).getTime()) / 1000);
    if (seconds < 60) return 'just now';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    return `${days}d ago`;
}

export default function ProjectShow({ project }: { project: ProjectDashboard }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ProjectNavPanel project={project} />}>
            <div className="space-y-4">
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <StatCard icon={ListChecks} label="Open Tasks" value={project.tasks_open_count} />
                    <StatCard icon={CircleCheck} label="Completed Tasks" value={project.tasks_closed_count} />
                    <StatCard icon={Scale} label="Active Decisions" value={project.decisions_count} />
                    <StatCard icon={MessageSquare} label="Conversations" value={project.conversations_count} />
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader className="border-b">
                            <div className="flex items-center justify-between">
                                <CardTitle>Recent Tasks</CardTitle>
                                {project.tasks_count > 0 && (
                                    <Link href={tasksIndex(project.ulid).url} className="text-primary text-sm hover:underline">
                                        View all
                                    </Link>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent className="pt-4">
                            {project.tasks.length > 0 ? (
                                <ul className="space-y-3">
                                    {project.tasks.map((task, i) => (
                                        <li key={i} className="flex items-center justify-between gap-2">
                                            <p className="flex-1 truncate text-sm font-medium">{task.title}</p>
                                            <div className="flex shrink-0 items-center gap-2">
                                                {task.status && (
                                                    <Badge variant="secondary" className="text-xs" style={{ backgroundColor: task.status.color + '20', color: task.status.color }}>
                                                        {task.status.name}
                                                    </Badge>
                                                )}
                                                <span className="text-muted-foreground text-xs">{timeAgo(task.updated_at)}</span>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-muted-foreground py-4 text-center text-sm">No tasks yet.</p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="border-b">
                            <div className="flex items-center justify-between">
                                <CardTitle>Active Decisions</CardTitle>
                                {project.decisions_count > 0 && (
                                    <Link href={decisionsIndex(project.ulid).url} className="text-primary text-sm hover:underline">
                                        View all
                                    </Link>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent className="pt-4">
                            {project.decisions.length > 0 ? (
                                <ul className="space-y-3">
                                    {project.decisions.map((decision, i) => (
                                        <li key={i} className="flex items-center justify-between gap-2">
                                            <div className="flex min-w-0 items-center gap-2">
                                                <Gavel className="text-muted-foreground size-4 shrink-0" />
                                                <p className="truncate text-sm font-medium">{decision.title}</p>
                                            </div>
                                            <span className="text-muted-foreground shrink-0 text-xs">{timeAgo(decision.created_at)}</span>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-muted-foreground py-4 text-center text-sm">No active decisions yet.</p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

function StatCard({ icon: Icon, label, value }: { icon: React.ComponentType<React.SVGProps<SVGSVGElement>>; label: string; value: number }) {
    return (
        <Card>
            <CardContent className="flex items-center justify-between p-6">
                <div className="bg-muted flex size-14 shrink-0 items-center justify-center rounded-xl">
                    <Icon className="text-muted-foreground size-7" />
                </div>
                <div className="text-right">
                    <p className="text-4xl font-bold tabular-nums">{value}</p>
                    <p className="text-muted-foreground text-sm">{label}</p>
                </div>
            </CardContent>
        </Card>
    );
}

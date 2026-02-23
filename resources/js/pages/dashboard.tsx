import { Link } from '@inertiajs/react';
import { CircleCheck, FolderCode, ListChecks, MessageSquare, Scale, ScrollText } from 'lucide-react';
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type ChartConfig, ChartContainer, ChartLegend, ChartLegendContent, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { show } from '@/routes/projects';
import type { BreadcrumbItem } from '@/types';
import type { DashboardProject } from '@/types/models';

type Props = {
    totals: {
        projects: number;
        tasks_open: number;
        tasks_closed: number;
        decisions: number;
    };
    projects: DashboardProject[];
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }];

const chartConfig = {
    tasks_open_count: {
        label: 'Open',
        color: 'oklch(0.795 0.184 86.047)',
    },
    tasks_closed_count: {
        label: 'Completed',
        color: 'oklch(0.765 0.177 163.223)',
    },
} satisfies ChartConfig;

export default function Dashboard({ totals, projects }: Props) {
    const hasProjects = projects.length > 0;
    const hasTaskData = projects.some((p) => p.tasks_count > 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            {hasProjects ? (
                <div className="space-y-4">
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <StatCard icon={FolderCode} label="Total Projects" value={totals.projects} />
                        <StatCard icon={ListChecks} label="Open Tasks" value={totals.tasks_open} />
                        <StatCard icon={CircleCheck} label="Completed Tasks" value={totals.tasks_closed} />
                        <StatCard icon={Scale} label="Active Decisions" value={totals.decisions} />
                    </div>

                    {hasTaskData && (
                        <Card>
                            <CardHeader className="border-b">
                                <CardTitle>Tasks Overview</CardTitle>
                                <CardDescription>Open vs completed tasks per project.</CardDescription>
                            </CardHeader>
                            <CardContent className="pt-6">
                                <ChartContainer config={chartConfig} className="aspect-auto h-64 w-full">
                                    <BarChart data={projects.filter((p) => p.tasks_count > 0)} layout="vertical" margin={{ left: 0 }}>
                                        <CartesianGrid horizontal={false} />
                                        <YAxis dataKey="name" type="category" width={120} tickLine={false} axisLine={false} className="text-xs" />
                                        <XAxis type="number" tickLine={false} axisLine={false} />
                                        <ChartTooltip content={<ChartTooltipContent />} />
                                        <ChartLegend content={<ChartLegendContent />} />
                                        <Bar dataKey="tasks_open_count" fill="var(--color-tasks_open_count)" radius={[0, 4, 4, 0]} />
                                        <Bar dataKey="tasks_closed_count" fill="var(--color-tasks_closed_count)" radius={[0, 4, 4, 0]} />
                                    </BarChart>
                                </ChartContainer>
                            </CardContent>
                        </Card>
                    )}

                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {projects.map((project) => (
                            <ProjectCard key={project.ulid} project={project} />
                        ))}
                    </div>
                </div>
            ) : (
                <Empty>
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <FolderCode />
                        </EmptyMedia>
                        <EmptyTitle>No projects yet</EmptyTitle>
                        <EmptyDescription>Create your first project to start tracking tasks, decisions, and business rules.</EmptyDescription>
                    </EmptyHeader>
                    <EmptyContent>
                        <Link href={show.definition.url.replace('/{project}', '')} className="text-primary text-sm font-medium underline underline-offset-4">
                            Go to Projects
                        </Link>
                    </EmptyContent>
                </Empty>
            )}
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

function ProjectCard({ project }: { project: DashboardProject }) {
    const latestTask = project.tasks[0] ?? null;

    return (
        <Card className="transition-shadow hover:shadow-md">
            <CardHeader className="border-b">
                <div className="flex items-center gap-2">
                    <span className="size-3 shrink-0 rounded-full" style={{ backgroundColor: project.color }} />
                    <CardTitle className="truncate">
                        <Link href={show(project.ulid).url} className="hover:underline">
                            {project.name}
                        </Link>
                    </CardTitle>
                </div>
            </CardHeader>
            <CardContent className="space-y-4 pt-4">
                <div className="grid grid-cols-2 gap-3 text-sm">
                    <div className="flex items-center gap-1.5 text-muted-foreground">
                        <ListChecks className="size-4" />
                        <span>{project.tasks_open_count} open</span>
                    </div>
                    <div className="flex items-center gap-1.5 text-muted-foreground">
                        <CircleCheck className="size-4" />
                        <span>{project.tasks_closed_count} done</span>
                    </div>
                    <div className="flex items-center gap-1.5 text-muted-foreground">
                        <Scale className="size-4" />
                        <span>{project.decisions_count} decisions</span>
                    </div>
                    <div className="flex items-center gap-1.5 text-muted-foreground">
                        <ScrollText className="size-4" />
                        <span>{project.business_rules_count} rules</span>
                    </div>
                    <div className="flex items-center gap-1.5 text-muted-foreground">
                        <MessageSquare className="size-4" />
                        <span>{project.conversations_count} chats</span>
                    </div>
                </div>

                {latestTask && (
                    <div className="border-t pt-3">
                        <p className="text-muted-foreground mb-1 text-xs">Latest task</p>
                        <div className="flex items-center gap-2">
                            <p className="flex-1 truncate text-sm font-medium">{latestTask.title}</p>
                            {latestTask.status && (
                                <Badge variant="secondary" className="shrink-0 text-xs" style={{ backgroundColor: latestTask.status.color + '20', color: latestTask.status.color }}>
                                    {latestTask.status.name}
                                </Badge>
                            )}
                        </div>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

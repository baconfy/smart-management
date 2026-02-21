import { ProjectNavPanel } from '@/components/navigation/project-nav-panel';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { show } from '@/routes/projects';
import { index as tasksIndex } from '@/routes/projects/tasks';
import type { BreadcrumbItem, Project, Task } from '@/types';

export default function TaskShow({ project, task }: { project: Project; task: Task }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: 'Tasks', href: tasksIndex(project.ulid).url },
        { title: task.title, href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ProjectNavPanel project={project} />}>
            <h1>Tasks</h1>
        </AppLayout>
    );
}

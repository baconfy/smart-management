import { ProjectNavPanel } from '@/components/navigation/project-nav-panel';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { index, show } from '@/routes/projects';
import type { BreadcrumbItem } from '@/types';
import type { Project } from '@/types/models';

export default function ProjectShow({ project }: { project: Project }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ProjectNavPanel project={project} />}>
            <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                <div className="aspect-video rounded-xl bg-muted" />
                <div className="aspect-video rounded-xl bg-muted" />
                <div className="aspect-video rounded-xl bg-muted" />
            </div>

            <div className="min-h-screen flex-1 rounded-xl bg-muted md:min-h-min" />
        </AppLayout>
    );
}

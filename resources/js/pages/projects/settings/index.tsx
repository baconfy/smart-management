import SettingsLayout from '@/layouts/project/settings-layout';
import { dashboard } from '@/routes';
import { show } from '@/routes/projects';
import type { BreadcrumbItem, Project } from '@/types';

export default function ProjectsPage({ project }: { project: Project}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: 'Settings', href: '#' },
    ];

    return (
        <SettingsLayout breadcrumbs={breadcrumbs} project={project}>
            <div className="space-y-4">
                <h2 className="text-lg font-semibold">General</h2>
                <p className="text-sm text-muted-foreground">Project settings will be available here soon.</p>
            </div>
        </SettingsLayout>
    );
}

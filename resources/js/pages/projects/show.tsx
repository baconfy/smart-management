import AppLayout from '@/layouts/app-layout';
import { index, show } from '@/routes/projects';
import type { BreadcrumbItem } from '@/types';
import type { Project } from '@/types/models';

export default function ProjectShow({ project }: { project: Project }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Projects', href: index().url },
        { title: project.name, href: show(project.ulid).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <div>
                <h1>{project.name}</h1>
                {/* TODO: Chat area + sidebar with agents/artifacts */}
            </div>
        </AppLayout>
    );
}

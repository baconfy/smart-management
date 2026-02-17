import { Link } from '@inertiajs/react';
import { FolderCodeIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { index, show } from '@/routes/projects';
import type { BreadcrumbItem } from '@/types';
import type { Project } from '@/types/models';

export default function ProjectsPage({ projects }: { projects: Project[] }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Projects', href: index().url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            {projects.length <= 0 ? (
                <div className="h-full flex items-center justify-center">
                    <Empty>
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FolderCodeIcon />
                            </EmptyMedia>
                            <EmptyTitle>No Projects Yet</EmptyTitle>
                            <EmptyDescription>You haven&apos;t created any projects yet. Get started by creating your first project.</EmptyDescription>
                        </EmptyHeader>
                        <EmptyContent className="flex-row justify-center gap-2">
                            <Button>Create Project</Button>
                        </EmptyContent>
                    </Empty>
                </div>
            ) : (
                <ul>
                    {projects.map((project) => (
                        <li key={project.ulid}>
                            <Link href={show(project)}>{project.name}</Link>
                        </li>
                    ))}
                </ul>
            )}
        </AppLayout>
    );
}

import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

export default function ProjectsPage() {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <h1>Agents</h1>
        </AppLayout>
    );
}

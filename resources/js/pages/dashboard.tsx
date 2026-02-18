import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { index } from '@/routes/projects';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                <div className="aspect-video rounded-xl bg-muted" />
                <div className="aspect-video rounded-xl bg-muted" />
                <div className="aspect-video rounded-xl bg-muted" />
            </div>

            <div className="min-h-screen flex-1 rounded-xl bg-muted md:min-h-min" />
        </AppLayout>
    );
}

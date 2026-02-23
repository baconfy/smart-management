import React from 'react';
import { ProfilePanel } from '@/components/navigation/profile-panel';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

export default function ProfileLayout({ breadcrumbs, children }: { breadcrumbs?: BreadcrumbItem[]; children?: React.ReactNode }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ProfilePanel />}>
            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-8">{children}</div>
        </AppLayout>
    );
}

import type React from 'react';
import type { BreadcrumbItem } from '@/types/navigation';

export type AppLayoutProps = {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    sidebar?: React.ReactNode;
};

export type AuthLayoutProps = {
    children?: React.ReactNode;
    name?: string;
    title?: string;
    description?: string;
};

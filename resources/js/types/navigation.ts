import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

export type BreadcrumbMenuAction = {
    title: string;
    icon?: LucideIcon;
    onClick: () => void;
    variant?: 'default' | 'destructive';
};

export type BreadcrumbItem = {
    title: string;
    href: string;
    menu?: BreadcrumbMenuAction[];
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
};

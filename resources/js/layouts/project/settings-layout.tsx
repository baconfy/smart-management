import { Link, usePage } from '@inertiajs/react';
import React from 'react';
import { ProjectNavPanel } from '@/components/navigation/project-nav-panel';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { settings } from '@/routes/projects';
import { index } from '@/routes/projects/agents';
import type { BreadcrumbItem, Project } from '@/types';

type Tab = { title: string; href: string };

export default function SettingsLayout({ breadcrumbs, project, children }: { breadcrumbs?: BreadcrumbItem[]; project: Project; children?: React.ReactNode }) {
    const { url } = usePage();
    const tabs: Tab[] = [
        { title: 'General', href: settings(project.ulid).url },
        { title: 'Agents', href: index(project.ulid).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ProjectNavPanel project={project} />}>
            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-8">
                <nav className="flex gap-1 border-b border-border">
                    {tabs.map((tab) => {
                        const isActive = url === tab.href || (tab.href !== `/p/${project.ulid}/s` && url.startsWith(tab.href));

                        return (
                            <Link key={tab.href} href={tab.href} className={cn('relative px-4 py-2 text-sm font-medium transition-colors', isActive ? 'text-foreground after:absolute after:inset-x-0 after:bottom-0 after:h-0.5 after:bg-primary' : 'text-muted-foreground hover:text-foreground')}>
                                {tab.title}
                            </Link>
                        );
                    })}
                </nav>

                {children}
            </div>
        </AppLayout>
    );
}

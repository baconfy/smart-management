import { Link, usePage } from '@inertiajs/react';
import { ChevronsLeft, ChevronsRight, FolderCodeIcon } from 'lucide-react';
import React from 'react';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { SidebarGroup, SidebarGroupContent, SidebarGroupLabel, SidebarGroupAction, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import ProjectCreate from '@/pages/projects/create';
import { index } from '@/routes/projects';
import type { SharedData } from '@/types';

export function ProjectsPanel() {
    const { projects } = usePage<SharedData>().props;

    return (
        <SidebarGroup className="h-full justify-between">
            <SidebarGroupContent>
                <div className="flex items-center justify-between gap-2">
                    <SidebarGroupLabel>Projects</SidebarGroupLabel>
                    <SidebarGroupLabel render={<Link className="text-primary hover:underline" href={index()} />}>
                        View all <ChevronsRight />
                    </SidebarGroupLabel>
                </div>
                <SidebarMenu>
                    {projects.length === 0 ? (
                        <Empty>
                            <EmptyHeader>
                                <EmptyMedia variant="icon">
                                    <FolderCodeIcon />
                                </EmptyMedia>
                                <EmptyTitle>No projects yet</EmptyTitle>
                            </EmptyHeader>
                        </Empty>
                    ) : (
                        <>
                            {projects.map((project) => (
                                <SidebarMenuItem key={project.id}>
                                    <SidebarMenuButton render={<Link className="flex items-center gap-2" href={`/projects/${project.ulid}`} />}>
                                        <p className="size-3 shrink-0 rounded-full" style={{ backgroundColor: project.color }} />
                                        <p className="truncate font-bold line-clamp-1">{project.name}</p>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            ))}
                        </>
                    )}
                </SidebarMenu>
            </SidebarGroupContent>

            <SidebarGroupAction render={<ProjectCreate />} />
        </SidebarGroup>
    );
}

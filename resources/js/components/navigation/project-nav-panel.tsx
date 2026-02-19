import { Link, usePage } from '@inertiajs/react';
import { BookOpen, ChevronsLeft, Gavel, ListTodo, MessageSquare, Settings } from 'lucide-react';
import React from 'react';
import { SidebarGroup, SidebarGroupContent, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as businessRules } from '@/routes/projects/business-rules';
import { index as conversations } from '@/routes/projects/conversations';
import { index as decisions } from '@/routes/projects/decisions';
import { index as tasks } from '@/routes/projects/tasks';
import type { Project } from '@/types';

type NavItem = {
    title: string;
    icon: React.ElementType;
    href: string;
    enabled: boolean;
};

export function ProjectNavPanel({ project }: { project: Project }) {
    const { url } = usePage();

    const items: NavItem[] = [
        { title: 'Conversations', icon: MessageSquare, href: conversations(project.ulid).url, enabled: true },
        { title: 'Tasks', icon: ListTodo, href: tasks(project.ulid).url, enabled: true },
        { title: 'Decisions', icon: Gavel, href: decisions(project.ulid).url, enabled: true },
        { title: 'Business Rules', icon: BookOpen, href: businessRules(project.ulid).url, enabled: true },
        { title: 'Settings', icon: Settings, href: `/projects/${project.ulid}/settings`, enabled: false },
    ];

    return (
        <SidebarGroup>
            <SidebarGroupContent>
                <SidebarGroupLabel render={<Link href={dashboard()} />}>
                    <ChevronsLeft /> Dashboard
                </SidebarGroupLabel>
                <SidebarMenu>
                    {items.map((item) => (
                        <SidebarMenuItem key={item.title} className="font-bold">
                            {item.enabled ? (
                                <SidebarMenuButton render={<Link href={item.href} />} isActive={url === item.href || url.startsWith(item.href + '/')}>
                                    <item.icon />
                                    <span>{item.title}</span>
                                </SidebarMenuButton>
                            ) : (
                                <SidebarMenuButton disabled className="flex items-center opacity-40">
                                    <item.icon />
                                    <span>{item.title}</span>
                                </SidebarMenuButton>
                            )}
                        </SidebarMenuItem>
                    ))}
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}

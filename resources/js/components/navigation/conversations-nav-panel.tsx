import { Link, usePage } from '@inertiajs/react';
import { ChevronsLeft } from 'lucide-react';
import React from 'react';
import { SidebarGroup, SidebarGroupContent, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import type { Conversation, Project } from '@/types';
import { show as showProject } from '@/routes/projects';
import { show as showConversation } from '@/routes/projects/conversations';
import { cn } from '@/lib/utils';

export function ConversationsNavPanel({ project, conversations }: { project: Project; conversations: Conversation[] }) {
    const { url } = usePage();

    return (
        <SidebarGroup>
            <SidebarGroupContent>
                <SidebarGroupLabel render={<Link href={showProject(project)} />}>
                    <ChevronsLeft /> {project.name}
                </SidebarGroupLabel>
                <SidebarMenu>
                    {conversations.map((conversation) => (
                        <Link className={cn('rounded-lg px-3 py-4 font-bold text-base', { 'bg-primary': url.endsWith(conversation.id), 'clickable hover:bg-muted': !url.endsWith(conversation.id) })} key={conversation.id} href={showConversation({ project: { ulid: project.ulid }, conversation: { id: conversation.id } })}>
                            <span className="line-clamp-1 leading-none">{conversation.title}</span>
                        </Link>
                    ))}
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}

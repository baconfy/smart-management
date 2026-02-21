import { Link, usePage } from '@inertiajs/react';
import { ChevronsLeft, MessageSquare, Plus } from 'lucide-react';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { SidebarGroup, SidebarGroupContent, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { show } from '@/routes/projects';
import { index as conversationsIndex } from '@/routes/projects/conversations';
import type { CursorPaginated } from '@/types';
import type { Conversation, Project } from '@/types/models';

export function ConversationsNavPanel({ project, conversations }: { project: Project; conversations: CursorPaginated<Conversation> }) {
    const { url } = usePage();

    return (
        <SidebarGroup className="h-full flex-col">
            <SidebarGroupContent className="flex flex-1 flex-col overflow-hidden">
                <SidebarGroupLabel render={<Link href={show(project.ulid)} />}>
                    <ChevronsLeft /> {project.name}
                </SidebarGroupLabel>

                <SidebarMenu className="flex-1 overflow-y-auto">
                    {conversations.data.length === 0 ? (
                        <Empty>
                            <EmptyHeader>
                                <EmptyMedia variant="icon">
                                    <MessageSquare />
                                </EmptyMedia>
                                <EmptyTitle>No conversations yet</EmptyTitle>
                            </EmptyHeader>
                        </Empty>
                    ) : (
                        conversations.data.map((conversation) => (
                            <SidebarMenuItem key={conversation.id}>
                                <SidebarMenuButton render={<Link className="flex items-center" href={conversationsIndex({ project: project.ulid, conversation: conversation.id }).url} />} isActive={url === conversationsIndex({ project: project.ulid, conversation: conversation.id }).url}>
                                    <span className="truncate">{conversation.title}</span>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        ))
                    )}
                </SidebarMenu>
            </SidebarGroupContent>

            <SidebarMenu className="shrink-0 pt-2">
                <SidebarMenuItem>
                    <SidebarMenuButton render={<Link className="flex items-center" href={conversationsIndex({ project: project.ulid }).url} />} isActive={url === conversationsIndex({ project: project.ulid }).url}>
                        <Plus /> <span>New Conversation</span>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>
    );
}

import { Link, usePage } from '@inertiajs/react';
import { ChevronsLeft, Plus } from 'lucide-react';
import { SidebarGroup, SidebarGroupContent, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { index as conversationsIndex, show as conversationShow } from '@/routes/projects/conversations';
import type { Conversation, Project } from '@/types/models';
import type { CursorPaginated } from '@/types';

type Props = {
    project: Project;
    conversations: CursorPaginated<Conversation>;
};

export function ConversationsNavPanel({ project, conversations }: Props) {
    const { url } = usePage();

    return (
        <SidebarGroup className="h-full flex-col">
            <SidebarGroupContent className="flex flex-1 flex-col">
                <SidebarGroupLabel render={<Link href={`/projects/${project.ulid}`} />}>
                    <ChevronsLeft /> {project.name}
                </SidebarGroupLabel>

                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton render={<Link className="flex items-center" href={conversationsIndex(project.ulid).url} />} isActive={url === conversationsIndex(project.ulid).url}>
                            <Plus /> <span>New Conversation</span>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    {conversations.data.map((conversation) => (
                        <SidebarMenuItem key={conversation.id}>
                            <SidebarMenuButton render={<Link className="flex items-center" href={conversationShow({ project: project.ulid, conversation: conversation.id }).url} />} isActive={url === conversationShow({ project: project.ulid, conversation: conversation.id }).url}>
                                <span className="truncate">{conversation.title}</span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    ))}
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}

import { Form } from '@inertiajs/react';
import { ChatInput } from '@/components/chat/chat-input';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { chat, show } from '@/routes/projects';
import type { BreadcrumbItem, Conversation, CursorPaginated } from '@/types';
import type { Project, ProjectAgent } from '@/types/models';
import { ConversationsNavPanel } from '@/components/navigation/conversations-nav-panel';
import { index } from '@/routes/projects/conversations';

export default function ConversationIndex({ project, agents, conversations }: { project: Project; agents: ProjectAgent[]; conversations: CursorPaginated<Conversation> }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: 'Conversations', href: index(project.ulid).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ConversationsNavPanel project={project} conversations={conversations} />}>
            <div className="flex flex-1 flex-col items-center justify-center gap-4">
                <h1 className="text-2xl font-bold">What can I help with?</h1>
                <Form {...chat.form(project.ulid)} resetOnSuccess={['message']} className="w-full max-w-3xl">
                    {({ processing }) => <ChatInput agents={agents} processing={processing} />}
                </Form>
            </div>
        </AppLayout>
    );
}

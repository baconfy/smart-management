import { Form } from '@inertiajs/react';
import { ChatInput } from '@/components/chat/chat-input';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { chat, index as projects, show } from '@/routes/projects';
import type { BreadcrumbItem, Conversation } from '@/types';
import type { Project, ProjectAgent } from '@/types/models';
import { ConversationsNavPanel } from '@/components/navigation/conversations-nav-panel';
import { index } from '@/routes/projects/conversations';

export default function ConversationIndex({ project, agents, conversations }: { project: Project; agents: ProjectAgent[]; conversations: { data: Conversation[] } }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: 'Conversations', href: index(project.ulid).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ConversationsNavPanel project={project} conversations={conversations.data} />}>
            <div className="flex flex-1 flex-col items-center justify-center">
                <Form {...chat.form(project.ulid)} resetOnSuccess={['message']} className="w-full max-w-3xl">
                    {({ processing, isDirty }) => <ChatInput agents={agents} processing={processing} dirty={isDirty} />}
                </Form>
            </div>
        </AppLayout>
    );
}

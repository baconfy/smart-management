import { Form } from '@inertiajs/react';

import { ChatProvider, useChat, ChatMessages, ChatInput } from '@/components/chat';
import { ConversationsNavPanel } from '@/components/navigation/conversations-nav-panel';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { chat, show } from '@/routes/projects';
import type { BreadcrumbItem, CursorPaginated } from '@/types';
import type { Conversation, ConversationMessage, Project, ProjectAgent } from '@/types/models';

type Props = {
    project: Project;
    agents: ProjectAgent[];
    conversation: Conversation;
    conversations: CursorPaginated<Conversation>;
    messages: ConversationMessage[];
};

function ConversationChatInner({ project, conversations }: { project: Project; conversations: CursorPaginated<Conversation> }) {
    const { title, handleFormSuccess } = useChat();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: title, href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ConversationsNavPanel project={project} conversations={conversations} />}>
            <div className="mx-auto flex min-h-0 w-full flex-1 flex-col">
                <ChatMessages />

                <div className="mx-auto w-full max-w-5xl shrink-0 px-12 pb-4">
                    <Form {...chat.form(project.ulid)} resetOnSuccess={['message']} onSuccess={handleFormSuccess} options={{ preserveState: true, preserveScroll: true }}>
                        {({ processing }) => <ChatInput formProcessing={processing} />}
                    </Form>
                </div>
            </div>
        </AppLayout>
    );
}

export default function ConversationShow({ project, agents, conversation, conversations, messages }: Props) {
    return (
        <ChatProvider conversation={conversation} agents={agents} messages={messages} projectUlid={project.ulid}>
            <ConversationChatInner project={project} conversations={conversations} />
        </ChatProvider>
    );
}

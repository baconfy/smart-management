import { ChatProvider, useChat, ChatMessages, ChatInput } from '@/components/chat';
import { ConversationsNavPanel } from '@/components/navigation/conversations-nav-panel';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { show } from '@/routes/projects';
import { index, send } from '@/routes/projects/conversations';
import type { BreadcrumbItem, CursorPaginated } from '@/types';
import type { Conversation, ConversationMessage, Project, ProjectAgent } from '@/types/models';

type Props = {
    project: Project;
    agents: ProjectAgent[];
    conversations: CursorPaginated<Conversation>;
    conversation?: Conversation | null;
    messages?: ConversationMessage[];
};

function ConversationInner({ project, conversations }: { project: Project; conversations: CursorPaginated<Conversation> }) {
    const { title, messages } = useChat();
    const hasMessages = messages.length > 0;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: title || 'Conversations', href: index({ project: project.ulid }).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ConversationsNavPanel project={project} conversations={conversations} />}>
            <div className="mx-auto flex min-h-0 w-full flex-1 flex-col">
                {hasMessages ? (
                    <>
                        <ChatMessages />
                        <div className="mx-auto w-full max-w-5xl shrink-0 px-12 pb-4">
                            <ChatInput />
                        </div>
                    </>
                ) : (
                    <div className="flex flex-1 flex-col items-center justify-center gap-4">
                        <h1 className="text-2xl font-bold">What can I help with?</h1>
                        <div className="w-full max-w-3xl">
                            <ChatInput />
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

export default function ConversationIndex({ project, agents, conversations, conversation = null, messages = [] }: Props) {
    return (
        <ChatProvider conversation={conversation} agents={agents} messages={messages} projectUlid={project.ulid} sendUrl={send.url(project.ulid)} onConversationCreated={(id) => history.replaceState(null, '', `/p/${project.ulid}/c/${id}`)}>
            <ConversationInner project={project} conversations={conversations} />
        </ChatProvider>
    );
}

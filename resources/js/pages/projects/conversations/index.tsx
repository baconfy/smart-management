import { ChatProvider, useChat, ChatMessages, ChatInput } from '@/components/chat';
import { ConversationsNavPanel } from '@/components/navigation/conversations-nav-panel';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { show } from '@/routes/projects';
import { index, send } from '@/routes/projects/conversations';
import type { BreadcrumbItem, CursorPaginated } from '@/types';
import type { Conversation, ConversationMessage, Project, ProjectAgent } from '@/types';

type Props = {
    project: Project;
    agents: ProjectAgent[];
    conversations: CursorPaginated<Conversation>;
    conversation?: Conversation | null;
    messages?: ConversationMessage[];
};

function ConversationInner({ project, conversations }: { project: Project; conversations: CursorPaginated<Conversation> }) {
    const { title, messages, processingAgents } = useChat();
    const hasMessages = messages.length > 0 || processingAgents.length > 0;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: title || 'Conversations', href: index({ project: project.ulid }).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ConversationsNavPanel project={project} conversations={conversations} />}>
            <div className="flex min-h-0 w-full flex-1 flex-col">
                {/* Top spacer */}
                <div className={cn('transition-[flex] duration-500 ease-in-out', hasMessages ? 'flex-none' : 'flex-1')} />

                {/* Title */}
                <div className={cn('flex justify-center overflow-hidden transition-all duration-500 ease-in-out', hasMessages ? 'max-h-0 opacity-0' : 'max-h-20 pb-4 opacity-100')}>
                    <h1 className="text-2xl font-bold">What can I help with?</h1>
                </div>

                {/* Messages */}
                <div className={cn('flex flex-col overflow-hidden transition-[flex] duration-500 ease-in-out', hasMessages ? 'min-h-0 flex-1' : 'flex-none')}>{hasMessages && <ChatMessages />}</div>

                {/* Input — shrink-0 keeps it fixed at the bottom */}
                <div className={cn('mx-auto w-full shrink-0 pb-4 transition-all duration-500 ease-in-out', hasMessages ? 'max-w-5xl px-12' : 'max-w-3xl px-4')}>
                    <ChatInput />
                </div>

                {/* Bottom spacer */}
                <div className={cn('transition-[flex] duration-500 ease-in-out', hasMessages ? 'flex-none' : 'flex-1')} />
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

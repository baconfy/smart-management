import { Conversation, ConversationContent, ConversationScrollButton } from '@/components/ai-elements/conversation';
import { Shimmer } from '@/components/ai-elements/shimmer';
import { ChatPromptInput, RoutingPollInput, StreamingTurnRenderer, TurnRenderer } from '@/components/chat';
import { ConversationsNavPanel } from '@/components/navigation/conversations-nav-panel';
import { useMultiAgentChat } from '@/hooks/use-multi-agent-chat';
import AppLayout from '@/layouts/app-layout';
import { groupIntoTurns } from '@/lib/chat-utils';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { show } from '@/routes/projects';
import { index } from '@/routes/projects/conversations';
import type { BreadcrumbItem, CursorPaginated } from '@/types';
import type { Conversation as ConversationType, ConversationMessage, Project, ProjectAgent } from '@/types';
import type { ChatMessage } from '@/types/chat';

type Props = {
    project: Project;
    agents: ProjectAgent[];
    conversations: CursorPaginated<ConversationType>;
    conversation?: ConversationType | null;
    messages?: CursorPaginated<ConversationMessage> | ConversationMessage[];
};

function toInitialMessages(input: CursorPaginated<ConversationMessage> | ConversationMessage[] | undefined): ChatMessage[] {
    const raw = !input ? [] : Array.isArray(input) ? input : (input.data ?? []);
    return raw.map((m) => ({
        id: m.id,
        role: m.role,
        content: m.content,
        agentId: m.project_agent_id ?? undefined,
        agentName: m.agent ?? undefined,
        attachments: m.attachments?.length ? m.attachments : undefined,
    }));
}

export default function ConversationIndex({ project, agents, conversations, conversation = null, messages: initialMessages = [] }: Props) {
    const { messages, agentStreams, status, routingPoll, send, selectAgents, abort, error } = useMultiAgentChat({
        initialMessages: toInitialMessages(initialMessages),
        conversationId: conversation?.id ?? null,
        projectUlid: project.ulid,
        onConversationCreated: (id) => {
            history.replaceState(null, '', `/p/${project.ulid}/c/${id}`);
        },
    });

    const turns = groupIntoTurns(messages);
    const hasActivity = messages.length > 0 || agentStreams.size > 0 || status !== 'idle';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: conversation?.title || 'Conversations', href: index({ project: project.ulid }).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ConversationsNavPanel project={project} conversations={conversations} />}>
            <div className="flex min-h-0 w-full flex-1 flex-col">
                {/* Top spacer */}
                <div className={cn('transition-[flex] duration-500 ease-in-out', hasActivity ? 'flex-none' : 'flex-1')} />

                {/* Title */}
                <div className={cn('flex justify-center overflow-hidden transition-all duration-500 ease-in-out', hasActivity ? 'max-h-0 opacity-0' : 'max-h-20 pb-4 opacity-100')}>
                    <h1 className="text-2xl font-bold">What can I help with?</h1>
                </div>

                {/* Messages */}
                <div className={cn('flex flex-col overflow-hidden transition-[flex] duration-500 ease-in-out', hasActivity ? 'min-h-0 flex-1' : 'flex-none')}>
                    {hasActivity && (
                        <Conversation>
                            <ConversationContent className="mx-auto w-full max-w-4xl">
                                {turns.map((turn) => (
                                    <TurnRenderer key={turn.id} turn={turn} />
                                ))}

                                {agentStreams.size > 0 && <StreamingTurnRenderer agentStreams={agentStreams} />}

                                {status === 'routing' && agentStreams.size === 0 && <Shimmer className="text-sm">Routing your message...</Shimmer>}

                                {error && <div className="rounded-xl border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">{error}</div>}
                            </ConversationContent>
                            <ConversationScrollButton />
                        </Conversation>
                    )}
                </div>

                {/* Poll UI */}
                {status === 'polling' && routingPoll && <RoutingPollInput poll={routingPoll} onSelect={selectAgents} />}

                {/* Input */}
                {status !== 'polling' && (
                    <div className={cn('mx-auto w-full shrink-0 pb-4 transition-all duration-500 ease-in-out', hasActivity ? 'max-w-4xl' : 'max-w-3xl px-4')}>
                        <ChatPromptInput onSend={send} isDisabled={status === 'streaming' || status === 'routing'} onAbort={status === 'streaming' ? abort : undefined} agents={agents} />
                    </div>
                )}

                {/* Bottom spacer */}
                <div className={cn('transition-[flex] duration-500 ease-in-out', hasActivity ? 'flex-none' : 'flex-1')} />
            </div>
        </AppLayout>
    );
}

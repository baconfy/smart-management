import { Form } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';
import ReactMarkdown from 'react-markdown';

import { ConversationsNavPanel } from '@/components/navigation/conversations-nav-panel';
import { InputChat } from '@/components/ui/input-chat';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { chat, show } from '@/routes/projects';
import type { BreadcrumbItem, CursorPaginated } from '@/types';
import type { Conversation, ConversationMessage, Project, ProjectAgent } from '@/types/models';

// --- Types ---

type ProcessingAgent = { id: number; name: string };

type PollCandidate = { type: string; confidence: number };
type PollState = { candidates: PollCandidate[]; reasoning: string };

type Turn = {
    userMessage: ConversationMessage;
    assistantMessages: ConversationMessage[];
};

type Props = {
    project: Project;
    agents: ProjectAgent[];
    conversation: Conversation;
    conversations: CursorPaginated<Conversation>;
    messages: ConversationMessage[];
};

// --- Helpers ---

function groupIntoTurns(messages: ConversationMessage[]): Turn[] {
    const turns: Turn[] = [];

    for (const msg of messages) {
        if (msg.role === 'user') {
            turns.push({ userMessage: msg, assistantMessages: [] });
        } else if (turns.length > 0) {
            turns[turns.length - 1].assistantMessages.push(msg);
        }
    }

    return turns;
}

// --- Sub-components ---

function ThinkingDots() {
    return (
        <span className="flex items-center gap-1">
            <span className="size-1 animate-bounce rounded-full bg-primary/75 [animation-delay:-0.3s]" />
            <span className="size-1 animate-bounce rounded-full bg-primary/75 [animation-delay:-0.15s]" />
            <span className="size-1 animate-bounce rounded-full bg-primary/75" />
        </span>
    );
}

function AgentTabs({ assistantMessages, processingAgents, agents }: { assistantMessages: ConversationMessage[]; processingAgents: ProcessingAgent[]; agents: ProjectAgent[] }) {
    const allTabs = [
        ...assistantMessages.map((msg) => ({
            id: String(msg.project_agent_id),
            name: agents.find((a) => a.id === msg.project_agent_id)?.name ?? 'Agent',
            message: msg,
            isProcessing: false,
        })),
        ...processingAgents
            .filter((pa) => !assistantMessages.some((m) => m.project_agent_id === pa.id))
            .map((pa) => ({
                id: String(pa.id),
                name: pa.name,
                message: null,
                isProcessing: true,
            })),
    ];

    if (allTabs.length === 0) return null;

    return (
        <div className="flex justify-start">
            <Tabs defaultValue={allTabs[0].id} className="max-w-[80%] flex-col">
                <div className="sticky top-0 z-10 shadow-2xl">
                    <TabsList>
                        {allTabs.map((tab) => (
                            <TabsTrigger key={tab.id} value={tab.id}>
                                {tab.name}
                                {tab.isProcessing && <ThinkingDots />}
                            </TabsTrigger>
                        ))}
                    </TabsList>
                </div>

                {allTabs.map((tab) => (
                    <TabsContent key={tab.id} value={tab.id}>
                        <div className="rounded-xl bg-muted px-4 py-3 text-muted-foreground">
                            {tab.isProcessing ? (
                                <ThinkingDots />
                            ) : (
                                <div className="prose prose-base max-w-none prose-invert">
                                    <ReactMarkdown>{tab.message!.content}</ReactMarkdown>
                                </div>
                            )}
                        </div>
                    </TabsContent>
                ))}
            </Tabs>
        </div>
    );
}

// --- Page ---

export default function ConversationShow({ project, agents, conversation, messages: initialMessages, conversations }: Props) {
    const [messages, setMessages] = useState<ConversationMessage[]>(initialMessages);
    const [processingAgents, setProcessingAgents] = useState<ProcessingAgent[]>([]);
    const [poll, setPoll] = useState<PollState | null>(null);
    const [title, setTitle] = useState(conversation.title);
    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const routingTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const [isRouting, setIsRouting] = useState(false);
    const selectedAgentIdsRef = useRef<number[]>([]);

    const turns = groupIntoTurns(messages);
    const isBusy = isRouting || processingAgents.length > 0 || poll !== null;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: title, href: '#' },
    ];

    function clearRoutingTimeout() {
        if (routingTimeoutRef.current) {
            clearTimeout(routingTimeoutRef.current);
            routingTimeoutRef.current = null;
        }
    }

    function handleSuccess() {
        if (selectedAgentIdsRef.current.length === 0) {
            setIsRouting(true);
            routingTimeoutRef.current = setTimeout(() => setIsRouting(false), 30_000);
        }
    }

    async function handleSelectAgents(agentIds: number[]) {
        setPoll(null);
        setIsRouting(true);
        routingTimeoutRef.current = setTimeout(() => setIsRouting(false), 30_000);

        const lastUserMsg = [...messages].reverse().find((m) => m.role === 'user');

        await axios.post(`/p/${project.ulid}/c/${conversation.id}/select-agents`, {
            agent_ids: agentIds,
            message: lastUserMsg?.content ?? '',
        });
    }

    useEffect(() => {
        setMessages(initialMessages);
    }, [initialMessages]);

    useEffect(() => {
        setTitle(conversation.title);
    }, [conversation.title]);

    useEffect(() => {
        const channel = window.Echo.private(`conversation.${conversation.id}`);

        channel.listen('.agents.processing', (e: { agents: ProcessingAgent[] }) => {
            clearRoutingTimeout();
            setIsRouting(false);
            setProcessingAgents(e.agents);
        });

        channel.listen('.agent.selection.required', (e: { candidates: PollCandidate[]; reasoning: string }) => {
            clearRoutingTimeout();
            setIsRouting(false);
            setPoll(e);
        });

        channel.listen('.message.received', (e: { message: ConversationMessage }) => {
            clearRoutingTimeout();
            setIsRouting(false);
            setMessages((prev) => {
                if (prev.some((m) => m.id === e.message.id)) return prev;
                return [...prev, e.message];
            });
            setProcessingAgents((prev) => prev.filter((a) => Number(a.id) !== Number(e.message.project_agent_id)));
        });

        channel.listen('.title.updated', (e: { id: string; title: string }) => {
            setTitle(e.title);
        });

        return () => {
            clearRoutingTimeout();
            window.Echo.leave(`conversation.${conversation.id}`);
        };
    }, [conversation.id]);

    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ConversationsNavPanel project={project} conversations={conversations} />}>
            <div className="mx-auto flex min-h-0 w-full max-w-5xl flex-1 flex-col">
                <div className="no-scrollbar flex flex-1 flex-col-reverse overflow-y-auto pb-4">
                    <div className="mx-auto w-full space-y-4">
                        {turns.map((turn, i) => {
                            const isLastTurn = i === turns.length - 1;
                            const turnProcessing = isLastTurn ? processingAgents : [];
                            const totalResponders = turn.assistantMessages.length + turnProcessing.length;
                            const isMultiAgent = totalResponders > 1;

                            return (
                                <div key={turn.userMessage.id} className="space-y-4">
                                    {/* User message */}
                                    <div className="flex justify-end">
                                        <div className="max-w-[75%] rounded-xl bg-primary px-4 py-3 text-primary-foreground text-shadow-2xs">
                                            <div className="prose prose-base max-w-none prose-invert">
                                                <ReactMarkdown>{turn.userMessage.content}</ReactMarkdown>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Routing indicator */}
                                    {isLastTurn && isRouting && (
                                        <div className="flex justify-start">
                                            <div className="flex items-center gap-2 rounded-xl bg-muted px-4 py-3">
                                                <ThinkingDots />
                                            </div>
                                        </div>
                                    )}

                                    {/* Multi-agent: tabs */}
                                    {isMultiAgent && <AgentTabs assistantMessages={turn.assistantMessages} processingAgents={turnProcessing} agents={agents} />}

                                    {/* Single agent: inline */}
                                    {!isMultiAgent && turn.assistantMessages.length === 1 && (
                                        <div className="flex justify-start">
                                            <div className="max-w-[75%] rounded-xl bg-muted px-4 py-3 text-muted-foreground">
                                                {turn.assistantMessages[0].project_agent_id && <span className="text-sm font-medium tracking-tighter text-primary">{agents.find((a) => a.id === turn.assistantMessages[0].project_agent_id)?.name}</span>}
                                                <div className="prose prose-base max-w-none prose-invert">
                                                    <ReactMarkdown>{turn.assistantMessages[0].content}</ReactMarkdown>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {/* Single agent still processing */}
                                    {!isMultiAgent && turn.assistantMessages.length === 0 && turnProcessing.length === 1 && (
                                        <div className="flex justify-start">
                                            <div className="flex items-center gap-2 rounded-xl bg-muted px-4 py-3">
                                                <span className="text-sm font-medium tracking-tighter text-primary">{turnProcessing[0].name}</span>
                                                <ThinkingDots />
                                            </div>
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </div>

                <div className="mx-auto w-full shrink-0 px-12 pb-4">
                    <Form {...chat.form(project.ulid)} resetOnSuccess={['message']} onSuccess={handleSuccess} options={{ preserveState: true, preserveScroll: true }}>
                        {({ processing }) => <InputChat textareaRef={textareaRef} agents={agents} conversationId={conversation.id} processing={processing || isBusy} poll={poll} onSelectAgents={handleSelectAgents} onAgentsChange={(ids) => (selectedAgentIdsRef.current = ids)} />}
                    </Form>
                </div>
            </div>
        </AppLayout>
    );
}

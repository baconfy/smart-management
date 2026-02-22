import axios from 'axios';
import React, { createContext, useContext, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { selectAgents } from '@/routes/projects/conversations';
import type { Conversation, ConversationMessage, CursorPaginated, ProjectAgent } from '@/types';

// --- Types ---

export type ProcessingAgent = { id: number; name: string };
export type PollCandidate = { type: string; confidence: number };
export type PollState = { candidates: PollCandidate[]; reasoning: string };

export type Turn = {
    userMessage: ConversationMessage | null;
    assistantMessages: ConversationMessage[];
};

type ChatContextValue = {
    conversationId: string | null;
    agents: ProjectAgent[];
    messages: ConversationMessage[];
    turns: Turn[];
    processingAgents: ProcessingAgent[];
    isRouting: boolean;
    isSending: boolean;
    isBusy: boolean;
    poll: PollState | null;
    title: string;
    error: string | null;
    hasMoreMessages: boolean;
    isLoadingMore: boolean;
    selectedAgentIds: number[];
    toggleAgent: (id: number) => void;
    sendMessage: (content: string) => Promise<void>;
    handleSelectAgents: (agentIds: number[]) => void;
    clearError: () => void;
    loadMoreMessages: () => Promise<void>;
};

// --- Helpers ---

function groupIntoTurns(messages: ConversationMessage[]): Turn[] {
    const turns: Turn[] = [];

    for (const msg of messages) {
        if (msg.role === 'user') {
            turns.push({ userMessage: msg, assistantMessages: [] });
        } else if (turns.length > 0) {
            turns[turns.length - 1].assistantMessages.push(msg);
        } else {
            turns.push({ userMessage: null, assistantMessages: [msg] });
        }
    }

    return turns;
}

function extractMessages(input: CursorPaginated<ConversationMessage> | ConversationMessage[] | undefined): ConversationMessage[] {
    if (!input) return [];
    if (Array.isArray(input)) return input;
    return input.data ?? [];
}

function extractNextPageUrl(input: CursorPaginated<ConversationMessage> | ConversationMessage[] | undefined): string | null {
    if (!input || Array.isArray(input)) return null;
    return input.next_page_url ?? null;
}

// --- Context ---

const ChatContext = createContext<ChatContextValue | null>(null);

export function useChat() {
    const ctx = useContext(ChatContext);
    if (!ctx) throw new Error('useChat must be used within ChatProvider');
    return ctx;
}

// --- Provider ---

type ChatProviderProps = {
    conversation?: Conversation | null;
    agents: ProjectAgent[];
    messages?: CursorPaginated<ConversationMessage> | ConversationMessage[];
    projectUlid: string;
    sendUrl: string;
    defaultSelectedAgentIds?: number[];
    initialProcessingAgents?: ProcessingAgent[];
    onConversationCreated?: (conversationId: string) => void;
    children: React.ReactNode;
};

export function ChatProvider({ conversation = null, agents, messages: initialMessages, projectUlid, sendUrl, onConversationCreated, defaultSelectedAgentIds = [], initialProcessingAgents = [], children }: ChatProviderProps) {
    const [conversationId, setConversationId] = useState<string | null>(conversation?.id ?? null);
    const [messages, setMessages] = useState<ConversationMessage[]>(extractMessages(initialMessages));
    const [processingAgents, setProcessingAgents] = useState<ProcessingAgent[]>(initialProcessingAgents ?? []);
    const [poll, setPoll] = useState<PollState | null>(null);
    const [title, setTitle] = useState(conversation?.title ?? '');
    const [isRouting, setIsRouting] = useState(false);
    const [isSending, setIsSending] = useState(false);
    const [selectedAgentIds, setSelectedAgentIds] = useState<number[]>(defaultSelectedAgentIds);
    const [error, setError] = useState<string | null>(null);
    const [nextPageUrl, setNextPageUrl] = useState<string | null>(extractNextPageUrl(initialMessages));
    const [isLoadingMore, setIsLoadingMore] = useState(false);
    const routingTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const turns = groupIntoTurns(messages);
    const isBusy = isRouting || isSending || processingAgents.length > 0 || poll !== null;
    const hasMoreMessages = nextPageUrl !== null;

    function clearRoutingTimeout() {
        if (routingTimeoutRef.current) {
            clearTimeout(routingTimeoutRef.current);
            routingTimeoutRef.current = null;
        }
    }

    function clearError() {
        setError(null);
    }

    function toggleAgent(id: number) {
        setSelectedAgentIds((prev) => (prev.includes(id) ? prev.filter((a) => a !== id) : [...prev, id]));
    }

    async function loadMoreMessages() {
        if (!nextPageUrl || isLoadingMore) return;

        setIsLoadingMore(true);
        try {
            const { data } = await axios.get<CursorPaginated<ConversationMessage>>(nextPageUrl);
            setMessages((prev) => [...data.data, ...prev]);
            setNextPageUrl(data.next_page_url ?? null);
        } finally {
            setIsLoadingMore(false);
        }
    }

    async function sendMessage(content: string) {
        const tempId = `temp-${Date.now()}`;

        setError(null);

        // Optimistic UI — add a user message immediately
        setMessages((prev) => [...prev, { id: tempId, role: 'user', content } as ConversationMessage]);
        setIsSending(true);

        try {
            const { data } = await axios.post(sendUrl, {
                message: content,
                conversation_id: conversationId,
                agent_ids: selectedAgentIds,
            });

            // The first message creates the conversation
            if (!conversationId) {
                setConversationId(data.conversation_id);
                onConversationCreated?.(data.conversation_id);
            }

            // Start routing indicator
            if (selectedAgentIds.length === 0) {
                setIsRouting(true);
                routingTimeoutRef.current = setTimeout(() => setIsRouting(false), 30_000);
            }
        } catch {
            setMessages((prev) => prev.filter((m) => m.id !== tempId));
            toast.error('Failed to send message. Please try again.');
        } finally {
            setIsSending(false);
        }
    }

    async function handleSelectAgents(agentIds: number[]) {
        if (!conversationId) return;

        setPoll(null);
        setIsRouting(true);
        routingTimeoutRef.current = setTimeout(() => setIsRouting(false), 30_000);

        const lastUserMsg = [...messages].reverse().find((m) => m.role === 'user');

        try {
            await axios.post(selectAgents.url({ project: projectUlid, conversation: conversationId }), {
                agent_ids: agentIds,
                message: lastUserMsg?.content ?? '',
            });
        } catch {
            clearRoutingTimeout();
            setIsRouting(false);
            toast.error('Failed to submit agent selection. Please try again.');
        }
    }

    // Sync with server-provided data on navigation
    useEffect(() => {
        setMessages(extractMessages(initialMessages));
        setNextPageUrl(extractNextPageUrl(initialMessages));
    }, [initialMessages]);

    useEffect(() => {
        setConversationId(conversation?.id ?? null);
        setTitle(conversation?.title ?? '');
    }, [conversation]);

    // Echo — connect/disconnect based on conversationId
    useEffect(() => {
        if (!conversationId) return;

        // Safety timeout: clear stuck processing agents after 120s
        const processingTimeout = setTimeout(() => {
            setProcessingAgents((prev) => {
                if (prev.length > 0) {
                    toast.warning('Agent response timed out. Please try again.');
                    return [];
                }
                return prev;
            });
        }, 120_000);

        const channel = window.Echo.private(`conversation.${conversationId}`);

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

        channel.listen('.agent.processing.failed', (e: { agent_id: number; agent_name: string; error: string }) => {
            setProcessingAgents((prev) => prev.filter((a) => Number(a.id) !== Number(e.agent_id)));
            setError(e.error);
            clearRoutingTimeout();
            setIsRouting(false);
        });

        channel.listen('.routing.failed', (e: { error: string }) => {
            clearRoutingTimeout();
            setIsRouting(false);
            setProcessingAgents([]);
            setError(e.error);
        });

        channel.listen('.title.updated', (e: { id: string; title: string }) => {
            setTitle(e.title);
        });

        return () => {
            clearTimeout(processingTimeout);
            clearRoutingTimeout();
            window.Echo.leave(`conversation.${conversationId}`);
        };
    }, [conversationId]);

    return (
        <ChatContext.Provider
            value={{
                conversationId,
                agents,
                messages,
                turns,
                processingAgents,
                isRouting,
                isSending,
                isBusy,
                poll,
                title,
                error,
                hasMoreMessages,
                isLoadingMore,
                selectedAgentIds,
                toggleAgent,
                sendMessage,
                handleSelectAgents,
                clearError,
                loadMoreMessages,
            }}
        >
            {children}
        </ChatContext.Provider>
    );
}

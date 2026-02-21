import axios from 'axios';
import { createContext, useContext, useEffect, useRef, useState } from 'react';
import { selectAgents } from '@/routes/projects/conversations';
import type { Conversation, ConversationMessage, ProjectAgent } from '@/types/models';

// --- Types ---

export type ProcessingAgent = { id: number; name: string };
export type PollCandidate = { type: string; confidence: number };
export type PollState = { candidates: PollCandidate[]; reasoning: string };

export type Turn = {
    userMessage: ConversationMessage;
    assistantMessages: ConversationMessage[];
};

type ChatContextValue = {
    conversation: Conversation;
    agents: ProjectAgent[];
    messages: ConversationMessage[];
    turns: Turn[];
    processingAgents: ProcessingAgent[];
    isRouting: boolean;
    isBusy: boolean;
    poll: PollState | null;
    title: string;
    selectedAgentIds: number[];
    toggleAgent: (id: number) => void;
    handleFormSuccess: () => void;
    handleSelectAgents: (agentIds: number[]) => void;
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

// --- Context ---

const ChatContext = createContext<ChatContextValue | null>(null);

export function useChat() {
    const ctx = useContext(ChatContext);
    if (!ctx) throw new Error('useChat must be used within ChatProvider');
    return ctx;
}

// --- Provider ---

type ChatProviderProps = {
    conversation: Conversation;
    agents: ProjectAgent[];
    messages: ConversationMessage[];
    projectUlid: string;
    children: React.ReactNode;
};

export function ChatProvider({ conversation, agents, messages: initialMessages, projectUlid, children }: ChatProviderProps) {
    const [messages, setMessages] = useState<ConversationMessage[]>(initialMessages);
    const [processingAgents, setProcessingAgents] = useState<ProcessingAgent[]>([]);
    const [poll, setPoll] = useState<PollState | null>(null);
    const [title, setTitle] = useState(conversation.title);
    const [isRouting, setIsRouting] = useState(false);
    const [selectedAgentIds, setSelectedAgentIds] = useState<number[]>([]);
    const routingTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const turns = groupIntoTurns(messages);
    const isBusy = isRouting || processingAgents.length > 0 || poll !== null;

    function clearRoutingTimeout() {
        if (routingTimeoutRef.current) {
            clearTimeout(routingTimeoutRef.current);
            routingTimeoutRef.current = null;
        }
    }

    function toggleAgent(id: number) {
        setSelectedAgentIds((prev) => (prev.includes(id) ? prev.filter((a) => a !== id) : [...prev, id]));
    }

    function handleFormSuccess() {
        if (selectedAgentIds.length === 0) {
            setIsRouting(true);
            routingTimeoutRef.current = setTimeout(() => setIsRouting(false), 30_000);
        }
    }

    async function handleSelectAgents(agentIds: number[]) {
        setPoll(null);
        setIsRouting(true);
        routingTimeoutRef.current = setTimeout(() => setIsRouting(false), 30_000);

        const lastUserMsg = [...messages].reverse().find((m) => m.role === 'user');

        await axios.post(selectAgents.url({ project: projectUlid, conversation: conversation.id }), { agent_ids: agentIds, message: lastUserMsg?.content ?? '' });
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
        <ChatContext.Provider
            value={{
                conversation,
                agents,
                messages,
                turns,
                processingAgents,
                isRouting,
                isBusy,
                poll,
                title,
                selectedAgentIds,
                toggleAgent,
                handleFormSuccess,
                handleSelectAgents,
            }}
        >
            {children}
        </ChatContext.Provider>
    );
}

export interface ChatAttachment {
    filename: string;
    url: string;
    mediaType: string;
}

export interface ChatMessage {
    id: number | string;
    role: 'user' | 'assistant';
    content: string;
    agentId?: number;
    agentName?: string;
    agentType?: string;
    meta?: Record<string, unknown>;
    attachments?: ChatAttachment[];
}

export interface AgentStream {
    agentId: number;
    name: string;
    text: string;
    isStreaming: boolean;
    isDone: boolean;
    messageId?: number;
    error?: string;
}

export interface RoutingPoll {
    reasoning: string;
    candidates: PollCandidate[];
}

export interface PollCandidate {
    id: number;
    name: string;
    type: string;
    confidence: number;
}

export type ChatStatus = 'idle' | 'routing' | 'streaming' | 'polling' | 'error';

export type AgentType = 'architect' | 'developer' | 'reviewer' | 'designer' | 'manager';

export type Project = {
    id: number;
    ulid: string;
    name: string;
    color: string;
    description: string | null;
    created_at: string;
};

export type ProjectAgent = {
    id: number;
    project_id: number;
    name: string;
    type: AgentType;
    provider: string;
    model: string;
    is_active: boolean;
};

export type Conversation = {
    id: string;
    title: string;
    created_at: string;
    updated_at: string;
};

export type Decision = {
    id: string;
    title: string;
    choice: string;
    reasoning: string;
    alternatives_considered: string;
    context: string;
    status: 'active' | 'superseded' | 'deprecated';
    created_at: string;
    updated_at: string;
};

export type BusinessRule = {
    id: number;
    project_id: number;
    title: string;
    description: string;
    category: string;
    status: string;
    created_at: string;
    updated_at: string;
};

export type ConversationMessage = {
    id: string;
    conversation_id: string;
    user_id: number | null;
    project_agent_id: number | null;
    agent: string;
    role: 'user' | 'assistant';
    content: string;
    created_at: string;
};

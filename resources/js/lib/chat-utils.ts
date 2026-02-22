import type { ChatMessage } from '@/types/chat';

export interface Turn {
    id: string;
    userMessage: ChatMessage | null;
    agentMessages: ChatMessage[];
}

export function groupIntoTurns(messages: ChatMessage[]): Turn[] {
    const turns: Turn[] = [];
    let currentTurn: Turn | null = null;

    for (const msg of messages) {
        if (msg.role === 'user') {
            currentTurn = {
                id: `turn-${msg.id}`,
                userMessage: msg,
                agentMessages: [],
            };
            turns.push(currentTurn);
        } else {
            if (!currentTurn) {
                currentTurn = {
                    id: `turn-orphan-${msg.id}`,
                    userMessage: null,
                    agentMessages: [],
                };
                turns.push(currentTurn);
            }
            currentTurn.agentMessages.push(msg);
        }
    }

    return turns;
}

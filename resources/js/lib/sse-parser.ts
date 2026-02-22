import type { PollCandidate } from '@/types/chat';

export interface RoutedAgent {
    id: number;
    name: string;
    type: string;
}

export type SseEvent =
    | { type: 'conversation'; id: string; isNew: boolean }
    | { type: 'routing'; agents: RoutedAgent[]; reasoning: string }
    | { type: 'agent_start'; agentId: number; name: string }
    | { type: 'chunk'; agentId: number; text: string }
    | { type: 'agent_done'; agentId: number; messageId: number }
    | { type: 'routing_poll'; reasoning: string; candidates: PollCandidate[] }
    | { type: 'agent_error'; agentId: number; message: string }
    | { type: 'error'; message: string }
    | { type: 'done' };

export async function* parseSseStream(response: Response): AsyncGenerator<SseEvent> {
    const reader = response.body!.getReader();
    const decoder = new TextDecoder();
    let buffer = '';

    while (true) {
        const { done, value } = await reader.read();
        if (done) {
            break;
        }

        buffer += decoder.decode(value, { stream: true });
        const lines = buffer.split('\n');
        buffer = lines.pop() ?? '';

        for (const line of lines) {
            if (line.startsWith('data: ')) {
                const json = line.slice(6).trim();
                if (json) {
                    yield JSON.parse(json) as SseEvent;
                }
            }
        }
    }
}

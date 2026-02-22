import { Message, MessageContent, MessageResponse } from '@/components/ai-elements/message';
import type { Turn } from '@/lib/chat-utils';

export function TurnRenderer({ turn }: { turn: Turn }) {
    return (
        <div className="space-y-4">
            {turn.userMessage && (
                <Message from="user">
                    <MessageContent>{turn.userMessage.content}</MessageContent>
                </Message>
            )}

            {turn.agentMessages.map((msg) => (
                <Message key={msg.id} from="assistant" className="max-w-3xl">
                    <MessageContent>
                        {msg.agentName && <span className="text-sm font-medium tracking-tight text-primary">{msg.agentName}</span>}
                        <MessageResponse>{msg.content}</MessageResponse>
                    </MessageContent>
                </Message>
            ))}
        </div>
    );
}

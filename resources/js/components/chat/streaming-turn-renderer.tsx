import { Message, MessageContent, MessageResponse } from '@/components/ai-elements/message';
import { Shimmer } from '@/components/ai-elements/shimmer';
import type { AgentStream } from '@/types/chat';

export function StreamingTurnRenderer({ agentStreams }: { agentStreams: Map<number, AgentStream> }) {
    return (
        <div className="space-y-4">
            {Array.from(agentStreams.values()).map((stream) => (
                <Message key={stream.agentId} from="assistant">
                    <MessageContent>
                        <span className="text-sm font-medium tracking-tight text-primary">{stream.name}</span>
                        {stream.error ? (
                            <span className="text-sm text-destructive">{stream.error}</span>
                        ) : stream.text ? (
                            <MessageResponse>{stream.text}</MessageResponse>
                        ) : (
                            <Shimmer className="text-sm">Thinking...</Shimmer>
                        )}
                    </MessageContent>
                </Message>
            ))}
        </div>
    );
}

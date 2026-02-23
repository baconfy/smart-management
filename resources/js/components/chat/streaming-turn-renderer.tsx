import { useEffect, useRef, useState } from 'react';

import { Message, MessageContent, MessageResponse } from '@/components/ai-elements/message';
import { Shimmer } from '@/components/ai-elements/shimmer';
import type { AgentStream } from '@/types/chat';
import { AgentTabSelector } from './agent-tab-selector';

interface StreamingTurnRendererProps {
    agentStreams: Map<number, AgentStream>;
    lastActiveAgentId?: number | null;
    onActiveIndexChange?: (index: number) => void;
}

export function StreamingTurnRenderer({ agentStreams, lastActiveAgentId, onActiveIndexChange }: StreamingTurnRendererProps) {
    const streams = Array.from(agentStreams.values());
    const [manualIndex, setManualIndex] = useState<number | null>(null);
    const isMultiAgent = streams.length > 1;
    let activeIndex: number;

    if (manualIndex !== null) {
        activeIndex = Math.min(manualIndex, Math.max(0, streams.length - 1));
    } else if (lastActiveAgentId != null) {
        const idx = streams.findIndex((s) => s.agentId === lastActiveAgentId);
        activeIndex = idx >= 0 ? idx : 0;
    } else {
        activeIndex = 0;
    }

    const activeStream = streams[activeIndex] ?? streams[0];
    const prevActiveIndexRef = useRef(activeIndex);

    useEffect(() => {
        if (prevActiveIndexRef.current !== activeIndex) {
            prevActiveIndexRef.current = activeIndex;
            onActiveIndexChange?.(activeIndex);
        }
    }, [activeIndex, onActiveIndexChange]);

    if (streams.length === 0) return null;

    function handleSelect(index: number) {
        setManualIndex(index);
        onActiveIndexChange?.(index);
    }

    return (
        <Message from="assistant">
            <MessageContent>
                {isMultiAgent && <AgentTabSelector agents={streams.map((stream) => ({ agentId: stream.agentId, name: stream.name, type: stream.type ?? 'custom', isStreaming: stream.isStreaming, hasError: !!stream.error }))} activeIndex={activeIndex} onSelect={handleSelect} />}

                {!isMultiAgent && activeStream && <span className="text-base font-bold tracking-tight text-primary">{activeStream.name}</span>}

                {activeStream && (
                    <div id={isMultiAgent ? `agent-panel-${activeStream.agentId}` : undefined} role={isMultiAgent ? 'tabpanel' : undefined}>
                        {activeStream.error ? <span className="text-sm text-destructive">{activeStream.error}</span> : activeStream.text ? <MessageResponse>{activeStream.text}</MessageResponse> : <Shimmer className="text-sm">Thinking...</Shimmer>}
                    </div>
                )}
            </MessageContent>
        </Message>
    );
}

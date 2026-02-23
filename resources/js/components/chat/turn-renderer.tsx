import { useState } from 'react';

import { Attachment, AttachmentPreview, Attachments } from '@/components/ai-elements/attachments';
import { Message, MessageContent, MessageResponse } from '@/components/ai-elements/message';
import type { Turn } from '@/lib/chat-utils';
import { AgentTabSelector } from './agent-tab-selector';

interface TurnRendererProps {
    turn: Turn;
    initialActiveIndex?: number;
}

export function TurnRenderer({ turn, initialActiveIndex = 0 }: TurnRendererProps) {
    const [activeIndex, setActiveIndex] = useState(initialActiveIndex);
    const isMultiAgent = turn.agentMessages.length > 1;
    const activeMessage = turn.agentMessages[activeIndex] ?? turn.agentMessages[0];

    return (
        <div className="space-y-4">
            {turn.userMessage && (
                <Message from="user">
                    <MessageContent>
                        {turn.userMessage.content}
                        {turn.userMessage.attachments && turn.userMessage.attachments.length > 0 && (
                            <Attachments variant="grid" className="mt-2 ml-0">
                                {turn.userMessage.attachments.map((att, i) => (
                                    <Attachment key={i} data={{ id: String(i), type: 'file', url: att.url, filename: att.filename, mediaType: att.mediaType }}>
                                        <AttachmentPreview />
                                    </Attachment>
                                ))}
                            </Attachments>
                        )}
                    </MessageContent>
                </Message>
            )}

            {turn.agentMessages.length > 0 && (
                <Message from="assistant">
                    <MessageContent>
                        {isMultiAgent ? (
                            <>
                                <AgentTabSelector agents={turn.agentMessages.map((msg) => ({ agentId: msg.agentId ?? 0, name: msg.agentName ?? 'Agent', type: msg.agentType ?? 'custom' }))} activeIndex={activeIndex} onSelect={setActiveIndex} />
                                {activeMessage && (
                                    <div id={`agent-panel-${activeMessage.agentId}`} role="tabpanel">
                                        <MessageResponse>{activeMessage.content}</MessageResponse>
                                    </div>
                                )}
                            </>
                        ) : (
                            <>
                                {turn.agentMessages[0].agentName && <span className="text-sm font-medium tracking-tight text-primary">{turn.agentMessages[0].agentName}</span>}
                                <MessageResponse>{turn.agentMessages[0].content}</MessageResponse>
                            </>
                        )}
                    </MessageContent>
                </Message>
            )}
        </div>
    );
}

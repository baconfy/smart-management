import { Attachment, AttachmentPreview, Attachments } from '@/components/ai-elements/attachments';
import { Message, MessageContent, MessageResponse } from '@/components/ai-elements/message';
import type { Turn } from '@/lib/chat-utils';

export function TurnRenderer({ turn }: { turn: Turn }) {
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

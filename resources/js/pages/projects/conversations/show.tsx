// pages/projects/conversations/show.tsx

import { ChatInput } from '@/components/chat/chat-input';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { show as showProject } from '@/routes/projects';
import type { BreadcrumbItem } from '@/types';
import type { Conversation, ConversationMessage, Project, ProjectAgent } from '@/types/models';
import { useEffect, useRef } from 'react';

type Props = {
    project: Project;
    agents: ProjectAgent[];
    conversation: Conversation;
    messages: ConversationMessage[];
};

export default function ConversationShow({ project, agents, conversation, messages }: Props) {
    const bottomRef = useRef<HTMLDivElement>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: showProject(project.ulid).url },
        { title: conversation.title, href: '#' },
    ];

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    function handleSend(message: string, agentIds: number[], files: File[]) {
        // TODO: POST to chat endpoint
        console.log('send', { message, agentIds, files, conversationId: conversation.id });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <div className="flex flex-1 flex-col">
                <div className="flex-1 overflow-y-auto pb-4">
                    <div className="mx-auto w-full max-w-3xl space-y-4">
                        {messages.map((msg) => (
                            <div key={msg.id} className={msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'}>
                                <div className={`max-w-[80%] rounded-xl px-4 py-2.5 ${msg.role === 'user' ? 'bg-primary text-primary-foreground' : 'bg-muted'}`}>
                                    {msg.role === 'assistant' && msg.project_agent_id && <p className="mb-1 text-xs font-medium text-muted-foreground">{agents.find((a) => a.id === msg.project_agent_id)?.name}</p>}
                                    <p className="text-sm whitespace-pre-wrap">{msg.content}</p>
                                </div>
                            </div>
                        ))}
                        <div ref={bottomRef} />
                    </div>
                </div>

                <div className="mx-auto w-full max-w-3xl pb-4">
                    <ChatInput agents={agents} onSend={handleSend} />
                </div>
            </div>
        </AppLayout>
    );
}

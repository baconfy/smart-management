import { useRef } from 'react';
import { ChatInput } from '@/components/chat/chat-input';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { chat, show as showProject } from '@/routes/projects';
import type { BreadcrumbItem } from '@/types';
import type { Conversation, ConversationMessage, Project, ProjectAgent } from '@/types/models';
import ReactMarkdown from 'react-markdown';
import { Form } from '@inertiajs/react';

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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <div className="mx-auto flex min-h-0 max-w-5xl flex-1 flex-col">
                <div className="no-scrollbar flex flex-1 flex-col-reverse overflow-y-auto pb-4">
                    <div className="mx-auto w-full space-y-4">
                        {messages.map((msg) => (
                            <div key={msg.id} className={msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'}>
                                <div className={`max-w-[80%] rounded-xl px-4 py-2.5 ${msg.role === 'user' ? 'bg-primary text-primary-foreground' : 'bg-muted'}`}>
                                    {msg.role === 'assistant' && msg.project_agent_id && <p className="mb-1 text-xs font-medium text-muted-foreground">{agents.find((a) => a.id === msg.project_agent_id)?.name}</p>}
                                    <div className="prose prose-base max-w-none prose-invert">
                                        <ReactMarkdown>{msg.content}</ReactMarkdown>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="mx-auto w-full shrink-0 pb-4">
                    <Form {...chat.form(project.ulid)} resetOnSuccess={['message']}>
                        {({ processing, isDirty }) => <ChatInput agents={agents} conversationId={conversation.id} processing={processing} dirty={isDirty} />}
                    </Form>
                </div>
            </div>
        </AppLayout>
    );
}

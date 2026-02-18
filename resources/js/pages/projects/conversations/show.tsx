import { ChatInput } from '@/components/chat/chat-input';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { chat, show } from '@/routes/projects';
import type { BreadcrumbItem, CursorPaginated } from '@/types';
import type { Conversation, ConversationMessage, Project, ProjectAgent } from '@/types/models';
import ReactMarkdown from 'react-markdown';
import { Form } from '@inertiajs/react';
import { ConversationsNavPanel } from '@/components/navigation/conversations-nav-panel';
import { index } from '@/routes/projects/conversations';

type Props = {
    project: Project;
    agents: ProjectAgent[];
    conversation: Conversation;
    conversations: CursorPaginated<Conversation>;
    messages: ConversationMessage[];
};

export default function ConversationShow({ project, agents, conversation, messages, conversations }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: conversation.title, href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ConversationsNavPanel project={project} conversations={conversations} />}>
            <div className="mx-auto flex min-h-0 w-full max-w-5xl flex-1 flex-col">
                <div className="no-scrollbar flex flex-1 flex-col-reverse overflow-y-auto pb-4">
                    <div className="mx-auto w-full space-y-4">
                        {messages.map((msg) => (
                            <div key={msg.id} className={msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'}>
                                <div className={`max-w-[75%] rounded-xl px-4 py-3 ${msg.role === 'user' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'}`}>
                                    {msg.role === 'assistant' && msg.project_agent_id && <span className="text-sm font-medium tracking-tighter text-primary">{agents.find((a) => a.id === msg.project_agent_id)?.name}</span>}
                                    <div className="prose prose-base max-w-none prose-invert">
                                        <ReactMarkdown>{msg.content}</ReactMarkdown>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="mx-auto w-full shrink-0 px-12 pb-4">
                    <Form {...chat.form(project.ulid)} resetOnSuccess={['message']}>
                        {({ processing }) => <ChatInput agents={agents} conversationId={conversation.id} processing={processing} />}
                    </Form>
                </div>
            </div>
        </AppLayout>
    );
}

import { Form } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import ReactMarkdown from 'react-markdown';
import { ConversationsNavPanel } from '@/components/navigation/conversations-nav-panel';
import { InputChat } from '@/components/ui/input-chat';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { chat, show } from '@/routes/projects';
import type { BreadcrumbItem, CursorPaginated } from '@/types';
import type { Conversation, ConversationMessage, Project, ProjectAgent } from '@/types/models';

type ProcessingAgent = { id: number; name: string };

type Props = {
    project: Project;
    agents: ProjectAgent[];
    conversation: Conversation;
    conversations: CursorPaginated<Conversation>;
    messages: ConversationMessage[];
};

export default function ConversationShow({ project, agents, conversation, messages: initialMessages, conversations }: Props) {
    const [messages, setMessages] = useState<ConversationMessage[]>(initialMessages);
    const [processingAgents, setProcessingAgents] = useState<ProcessingAgent[]>([]);
    const [title, setTitle] = useState(conversation.title);
    const textareaRef = useRef<HTMLTextAreaElement>(null);

    useEffect(() => {
        setMessages(initialMessages);
    }, [initialMessages]);

    useEffect(() => {
        setTitle(conversation.title);
    }, [conversation.title]);

    useEffect(() => {
        const channel = window.Echo.private(`conversation.${conversation.id}`);

        channel.listen('.agents.processing', (e: { agents: ProcessingAgent[] }) => {
            setProcessingAgents(e.agents);
        });

        channel.listen('.message.received', (e: { message: ConversationMessage }) => {
            setMessages((prev) => {
                if (prev.some((m) => m.id === e.message.id)) return prev;
                return [...prev, e.message];
            });

            setProcessingAgents((prev) => prev.filter((a) => a.id !== e.message.project_agent_id));
            textareaRef.current?.focus();
        });

        channel.listen('.title.updated', (e: { id: string; title: string }) => {
            setTitle(e.title);
        });

        return () => {
            window.Echo.leave(`conversation.${conversation.id}`);
        };
    }, [conversation.id]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: title, href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ConversationsNavPanel project={project} conversations={conversations} />}>
            <div className="mx-auto flex min-h-0 w-full max-w-5xl flex-1 flex-col">
                <div className="no-scrollbar flex flex-1 flex-col-reverse overflow-y-auto pb-4">
                    <div className="mx-auto w-full space-y-4">
                        {messages.map((msg) => (
                            <div key={msg.id} className={msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'}>
                                <div className={`max-w-[75%] rounded-xl px-4 py-3 ${msg.role === 'user' ? 'bg-primary text-primary-foreground text-shadow-2xs' : 'bg-muted text-muted-foreground'}`}>
                                    {msg.role === 'assistant' && msg.project_agent_id && <span className="text-sm font-medium tracking-tighter text-primary">{agents.find((a) => a.id === msg.project_agent_id)?.name}</span>}
                                    <div className="prose prose-base max-w-none prose-invert">
                                        <ReactMarkdown>{msg.content}</ReactMarkdown>
                                    </div>
                                </div>
                            </div>
                        ))}

                        {processingAgents.map((agent) => (
                            <div key={agent.id} className="flex justify-start">
                                <div className="flex items-center gap-2 rounded-xl bg-muted px-4 py-3">
                                    <span className="text-sm font-medium tracking-tighter text-primary">{agent.name}</span>
                                    <span className="flex items-center gap-1">
                                        <span className="size-1.5 animate-bounce rounded-full bg-primary/75 [animation-delay:-0.3s]" />
                                        <span className="size-1.5 animate-bounce rounded-full bg-primary/75 [animation-delay:-0.15s]" />
                                        <span className="size-1.5 animate-bounce rounded-full bg-primary/75" />
                                    </span>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="mx-auto w-full shrink-0 px-12 pb-4">
                    <Form {...chat.form(project.ulid)} resetOnSuccess={['message']} options={{ preserveState: true, preserveScroll: true }}>
                        {({ processing }) => <InputChat textareaRef={textareaRef} agents={agents} conversationId={conversation.id} processing={processing || processingAgents.length > 0} />}
                    </Form>
                </div>
            </div>
        </AppLayout>
    );
}

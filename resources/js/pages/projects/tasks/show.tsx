import { router, usePoll } from '@inertiajs/react';
import { FileText, PlayIcon } from 'lucide-react';
import { useState } from 'react';

import { Conversation, ConversationContent, ConversationScrollButton } from '@/components/ai-elements/conversation';
import { Shimmer } from '@/components/ai-elements/shimmer';
import { ChatPromptInput, RoutingPollInput, StreamingTurnRenderer, TurnRenderer } from '@/components/chat';
import { ProjectNavPanel } from '@/components/navigation/project-nav-panel';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import { useMultiAgentChat } from '@/hooks/use-multi-agent-chat';
import AppLayout from '@/layouts/app-layout';
import { groupIntoTurns } from '@/lib/chat-utils';
import { cn } from '@/lib/utils';
import { TaskDetails } from '@/pages/projects/tasks/partials/task-details';
import { dashboard } from '@/routes';
import { show } from '@/routes/projects';
import { index as tasksIndex, start } from '@/routes/projects/tasks';
import type { BreadcrumbItem, CursorPaginated } from '@/types';
import type { ChatMessage } from '@/types/chat';
import type { Conversation as ConversationType, ConversationMessage, ImplementationNote, Project, ProjectAgent, Task } from '@/types/models';

function TaskBreadcrumbs(project: Project, task: Task): BreadcrumbItem[] {
    return [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: 'Tasks', href: tasksIndex(project.ulid).url },
        { title: task.title, href: '#' },
    ];
}

function toInitialMessages(input: CursorPaginated<ConversationMessage> | ConversationMessage[] | undefined): ChatMessage[] {
    const raw = !input ? [] : Array.isArray(input) ? input : input.data ?? [];
    return raw.map((m) => ({
        id: m.id,
        role: m.role,
        content: m.content,
        agentId: m.project_agent_id ?? undefined,
        agentName: m.agent ?? undefined,
        attachments: m.attachments?.length ? m.attachments : undefined,
    }));
}

// --- No Conversation: TaskDetails + Start Button ---

function TaskEmpty({ project, task, subtasks, implementationNotes }: { project: Project; task: Task; subtasks: Task[]; implementationNotes: ImplementationNote[] }) {
    const [starting, setStarting] = useState(false);

    function handleStart() {
        setStarting(true);
        router.post(start.url({ project: project.ulid, task: task.ulid }), {}, { preserveScroll: true, onFinish: () => setStarting(false) });
    }

    return (
        <AppLayout breadcrumbs={TaskBreadcrumbs(project, task)} sidebar={<ProjectNavPanel project={project} />}>
            <div className="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6 p-8">
                <TaskDetails task={task} subtasks={subtasks} implementationNotes={implementationNotes} />

                <Button onClick={handleStart} disabled={starting} size="lg" className="self-start">
                    <PlayIcon /> {starting ? 'Starting...' : 'Start Task'}
                </Button>
            </div>
        </AppLayout>
    );
}

// --- With Conversation: Chat + Floating Details Button ---

function TaskChatView({ project, task, agents, subtasks, implementationNotes, initialMessages, conversation, defaultAgentIds, processingAgents = [] }: { project: Project; task: Task; agents: ProjectAgent[]; subtasks: Task[]; implementationNotes: ImplementationNote[]; initialMessages: ChatMessage[]; conversation: ConversationType; defaultAgentIds: number[]; processingAgents?: { id: number; name: string }[]; }) {
    const isProcessing = processingAgents.length > 0;

    // Poll while the Technical agent's background job is still running
    usePoll(2000, { only: ['messages', 'processingAgents'] }, { autoStart: isProcessing });

    const { messages, agentStreams, status, routingPoll, send, selectAgents, abort, error } = useMultiAgentChat({
        initialMessages,
        conversationId: conversation.id,
        projectUlid: project.ulid,
        taskUlid: task.ulid,
        defaultAgentIds,
    });

    const turns = groupIntoTurns(messages);
    const hasActivity = isProcessing || messages.length > 0 || agentStreams.size > 0 || status !== 'idle';

    return (
        <AppLayout breadcrumbs={TaskBreadcrumbs(project, task)} sidebar={<ProjectNavPanel project={project} />}>
            <div className="relative flex min-h-0 w-full flex-1 flex-col">
                {/* Top spacer */}
                <div className={cn('transition-[flex] duration-500 ease-in-out', hasActivity ? 'flex-none' : 'flex-1')} />

                {/* Title */}
                <div className={cn('flex flex-col items-center justify-center overflow-hidden transition-all duration-500 ease-in-out', hasActivity ? 'max-h-0 opacity-0' : 'max-h-32 pb-4 opacity-100')}>
                    <h1 className="text-2xl font-bold">What can I help with?</h1>
                </div>

                {/* Messages */}
                <div className={cn('flex flex-col overflow-hidden transition-[flex] duration-500 ease-in-out', hasActivity ? 'min-h-0 flex-1' : 'flex-none')}>
                    {hasActivity && (
                        <Conversation>
                            <ConversationContent className="mx-auto w-full max-w-5xl">
                                {turns.map((turn) => (
                                    <TurnRenderer key={turn.id} turn={turn} />
                                ))}

                                {isProcessing &&
                                    processingAgents.map((agent) => (
                                        <div key={agent.id} className="flex flex-col gap-1">
                                            <span className="text-sm font-medium tracking-tight text-primary">{agent.name}</span>
                                            <Shimmer className="text-sm">Thinking...</Shimmer>
                                        </div>
                                    ))}

                                {agentStreams.size > 0 && <StreamingTurnRenderer agentStreams={agentStreams} />}

                                {status === 'routing' && agentStreams.size === 0 && <Shimmer className="text-sm">Routing your message...</Shimmer>}

                                {error && <div className="rounded-xl border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">{error}</div>}
                            </ConversationContent>
                            <ConversationScrollButton />
                        </Conversation>
                    )}
                </div>

                {/* Poll UI */}
                {status === 'polling' && routingPoll && <RoutingPollInput poll={routingPoll} onSelect={selectAgents} />}

                {/* Input */}
                {status !== 'polling' && (
                    <div className={cn('mx-auto w-full shrink-0 pb-4 transition-all duration-500 ease-in-out', hasActivity ? 'max-w-5xl px-12' : 'max-w-3xl px-4')}>
                        <ChatPromptInput onSend={send} isDisabled={isProcessing || status === 'streaming' || status === 'routing'} onAbort={status === 'streaming' ? abort : undefined} agents={agents} defaultSelectedAgentIds={defaultAgentIds} />
                    </div>
                )}

                {/* Bottom spacer */}
                <div className={cn('transition-[flex] duration-500 ease-in-out', hasActivity ? 'flex-none' : 'flex-1')} />

                {/* Floating details button */}
                <Dialog>
                    <DialogTrigger render={<Button size="icon" variant="outline" className="fixed right-6 bottom-6 size-12 rounded-full shadow-lg" />}>
                        <FileText className="size-5" />
                    </DialogTrigger>
                    <DialogContent className="overflow-y-auto sm:max-w-2xl">
                        <div className="p-2">
                            <TaskDetails task={task} subtasks={subtasks} implementationNotes={implementationNotes} />
                        </div>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}

// --- Root ---

type Props = {
    project: Project;
    agents: ProjectAgent[];
    task: Task;
    subtasks: Task[];
    implementationNotes: ImplementationNote[];
    conversation?: ConversationType | null;
    messages?: CursorPaginated<ConversationMessage> | ConversationMessage[];
    defaultAgentIds?: number[];
    processingAgents?: { id: number; name: string }[];
};

export default function TaskShow({ project, agents, task, subtasks = [], implementationNotes = [], conversation = null, messages = [], defaultAgentIds = [], processingAgents = [] }: Props) {
    if (!conversation) {
        return <TaskEmpty project={project} task={task} subtasks={subtasks} implementationNotes={implementationNotes} />;
    }

    return (
        <TaskChatView
            key={processingAgents.length > 0 ? 'processing' : 'ready'}
            project={project}
            task={task}
            agents={agents}
            subtasks={subtasks}
            implementationNotes={implementationNotes}
            initialMessages={toInitialMessages(messages)}
            conversation={conversation}
            defaultAgentIds={defaultAgentIds ?? []}
            processingAgents={processingAgents}
        />
    );
}

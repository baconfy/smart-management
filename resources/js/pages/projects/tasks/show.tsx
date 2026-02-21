import { router } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import { useState } from 'react';

import type { ProcessingAgent } from '@/components/chat';
import { ChatProvider, useChat, ChatMessages, ChatInput } from '@/components/chat';
import { ProjectNavPanel } from '@/components/navigation/project-nav-panel';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { TaskDetails } from '@/pages/projects/tasks/partials/task-details';
import { dashboard } from '@/routes';
import { show } from '@/routes/projects';
import { index as tasksIndex, send, start } from '@/routes/projects/tasks';
import type { BreadcrumbItem } from '@/types';
import type { Conversation, ConversationMessage, ImplementationNote, Project, ProjectAgent, Task } from '@/types/models';

function TaskBreadcrumbs(project: Project, task: Task): BreadcrumbItem[] {
    return [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: 'Tasks', href: tasksIndex(project.ulid).url },
        { title: task.title, href: '#' },
    ];
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
                    {starting ? 'Starting...' : 'Start Task'}
                </Button>
            </div>
        </AppLayout>
    );
}

// --- With Conversation: Chat + Floating Details Button ---

function TaskChatInner({ project, task, subtasks, implementationNotes }: { project: Project; task: Task; subtasks: Task[]; implementationNotes: ImplementationNote[] }) {
    const { messages, processingAgents } = useChat();
    const hasMessages = messages.length > 0 || processingAgents.length > 0;

    return (
        <AppLayout breadcrumbs={TaskBreadcrumbs(project, task)} sidebar={<ProjectNavPanel project={project} />}>
            <div className="relative flex min-h-0 w-full flex-1 flex-col">
                {/* Top spacer */}
                <div className={cn('transition-[flex] duration-500 ease-in-out', hasMessages ? 'flex-none' : 'flex-1')} />

                {/* Title */}
                <div className={cn('flex flex-col items-center justify-center overflow-hidden transition-all duration-500 ease-in-out', hasMessages ? 'max-h-0 opacity-0' : 'max-h-32 pb-4 opacity-100')}>
                    <h1 className="text-2xl font-bold">What can I help with?</h1>
                </div>

                {/* Messages */}
                <div className={cn('flex flex-col overflow-hidden transition-[flex] duration-500 ease-in-out', hasMessages ? 'min-h-0 flex-1' : 'flex-none')}>{hasMessages && <ChatMessages />}</div>

                {/* Input */}
                <div className={cn('mx-auto w-full shrink-0 pb-4 transition-all duration-500 ease-in-out', hasMessages ? 'max-w-5xl px-12' : 'max-w-3xl px-4')}>
                    <ChatInput />
                </div>

                {/* Bottom spacer */}
                <div className={cn('transition-[flex] duration-500 ease-in-out', hasMessages ? 'flex-none' : 'flex-1')} />

                {/* Floating details button */}
                <Dialog>
                    <DialogTrigger render={<Button size="icon" variant="outline" className="fixed right-6 bottom-6 size-12 rounded-full shadow-lg" />}>
                        <FileText className="size-5" />
                    </DialogTrigger>
                    <DialogContent className="overflow-y-auto sm:max-w-2xl">
                        <div className="p-6">
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
    conversation?: Conversation | null;
    messages?: ConversationMessage[];
    defaultAgentIds?: number[];
    processingAgents?: ProcessingAgent[];
};

export default function TaskShow({ project, agents, task, subtasks = [], implementationNotes = [], conversation = null, messages = [], defaultAgentIds = [], processingAgents = [] }: Props) {
    if (!conversation) {
        return <TaskEmpty project={project} task={task} subtasks={subtasks} implementationNotes={implementationNotes} />;
    }

    return (
        <ChatProvider conversation={conversation} agents={agents} messages={messages} projectUlid={project.ulid} defaultSelectedAgentIds={defaultAgentIds} initialProcessingAgents={processingAgents} sendUrl={send.url({ project: project.ulid, task: task.id })}>
            <TaskChatInner project={project} task={task} subtasks={subtasks} implementationNotes={implementationNotes} />
        </ChatProvider>
    );
}

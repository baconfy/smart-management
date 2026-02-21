import { Form } from '@inertiajs/react';
import { ConversationsNavPanel } from '@/components/navigation/conversations-nav-panel';
import { InputChat } from '@/components/ui/input-chat';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { chat, show } from '@/routes/projects';
import { index } from '@/routes/projects/conversations';
import type { BreadcrumbItem, Conversation, CursorPaginated } from '@/types';
import type { Project, ProjectAgent } from '@/types/models';

export default function ConversationIndex({ project, agents, conversations }: { project: Project; agents: ProjectAgent[]; conversations: CursorPaginated<Conversation> }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: 'Conversations', href: index(project.ulid).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ConversationsNavPanel project={project} conversations={conversations} />}>
            <div className="flex flex-1 flex-col items-center justify-center gap-4">
                <h1 className="text-2xl font-bold">What can I help with?</h1>

                <Form {...chat.form(project.ulid)} options={{ preserveState: true, preserveScroll: true }} resetOnSuccess={['message']} className="w-full max-w-3xl">
                    {({ processing }) => <InputChat agents={agents} processing={processing} />}
                </Form>
            </div>
        </AppLayout>
    );
}

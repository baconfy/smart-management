import { Form } from '@inertiajs/react';
import { ChatInput } from '@/components/chat/chat-input';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { chat, show } from '@/routes/projects';
import type { BreadcrumbItem } from '@/types';
import type { Project, ProjectAgent } from '@/types/models';

export default function ConversationIndex({ project, agents }: { project: Project; agents: ProjectAgent[] }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: 'Conversations', href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <div className="flex flex-1 flex-col items-center justify-center">
                <Form {...chat.form(project.ulid)} resetOnSuccess={['message']} className="w-full max-w-3xl">
                    {({ processing, isDirty }) => <ChatInput agents={agents} processing={processing} dirty={isDirty} />}
                </Form>
            </div>
        </AppLayout>
    );
}

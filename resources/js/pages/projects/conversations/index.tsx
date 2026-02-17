import { ChatInput } from '@/components/chat/chat-input';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { show } from '@/routes/projects';
import type { BreadcrumbItem } from '@/types';
import type { Project, ProjectAgent } from '@/types/models';

type Props = {
    project: Project;
    agents: ProjectAgent[];
};

export default function ConversationIndex({ project, agents }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: 'Conversations', href: '#' },
    ];

    function handleSend(message: string, agentIds: number[], files: File[]) {
        // TODO: POST to chat endpoint, redirect to conversation/show on response
        console.log('send', { message, agentIds, files });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <div className="flex flex-1 flex-col items-center justify-center pb-4">
                <div className="w-full max-w-2xl">
                    <ChatInput agents={agents} onSend={handleSend} />
                </div>
            </div>
        </AppLayout>
    );
}

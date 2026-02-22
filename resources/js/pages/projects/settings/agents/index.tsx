import { Link, router } from '@inertiajs/react';
import { BotIcon, MessageCircleMore, PlusIcon } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent } from '@/components/ui/sheet';
import SettingsLayout from '@/layouts/project/settings-layout';
import { dashboard } from '@/routes';
import { show } from '@/routes/projects';
import type { BreadcrumbItem, Project, ProjectAgent } from '@/types';
import { AgentCard } from '@/pages/projects/settings/agents/partials/agent-card';
import { AgentForm } from '@/pages/projects/settings/agents/partials/agent-form';
import { destroy } from '@/routes/projects/agents';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { index as conversations } from '@/routes/projects/conversations';
import { Dialog, DialogContent } from '@/components/ui/dialog';

// --- Types ---

type PageProps = {
    project: Project;
    agents: ProjectAgent[];
    availableTools: string[];
};

export default function AgentsPage({ project, agents, availableTools }: PageProps) {
    const [sheetOpen, setSheetOpen] = useState(false);
    const [editingAgent, setEditingAgent] = useState<ProjectAgent | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: 'Settings', href: `/p/${project.ulid}/s` },
        { title: 'Agents', href: '#' },
    ];

    function openCreate() {
        setEditingAgent(null);
        setSheetOpen(true);
    }

    function openEdit(agent: ProjectAgent) {
        setEditingAgent(agent);
        setSheetOpen(true);
    }

    function handleDelete(agent: ProjectAgent) {
        router.delete(destroy({ project: project.ulid, agent: agent.id }).url);

        setEditingAgent(null);
        setSheetOpen(false);
    }

    function handleClose() {
        setEditingAgent(null);
        setSheetOpen(false);
    }

    return (
        <SettingsLayout breadcrumbs={breadcrumbs} project={project}>
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-lg font-semibold">Agents</h2>
                    <p className="text-sm text-muted-foreground">Manage the AI agents in your project.</p>
                </div>
                <Button onClick={openCreate}>
                    <PlusIcon className="size-4" /> New Agent
                </Button>
            </div>

            <div className="space-y-3">
                {agents.length <= 0 ? (
                    <Empty className="flex h-full items-center justify-center">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <BotIcon />
                            </EmptyMedia>
                            <EmptyTitle>No agents yet.</EmptyTitle>
                            <EmptyDescription>Create your first agent.</EmptyDescription>
                        </EmptyHeader>
                        <EmptyContent className="flex-row justify-center gap-2">
                            <Button onClick={openCreate}>
                                <PlusIcon className="size-4" /> New Agent
                            </Button>
                        </EmptyContent>
                    </Empty>
                ) : (
                    <div className="grid grid-cols-2 gap-6">
                        {agents.map((agent) => (
                            <AgentCard key={agent.id} agent={agent} onEdit={() => openEdit(agent)} />
                        ))}
                    </div>
                )}
            </div>

            <Dialog open={sheetOpen} onOpenChange={setSheetOpen}>
                <DialogContent className="flex w-full flex-col sm:max-w-lg md:max-w-5xl">
                    <AgentForm key={editingAgent?.id ?? 'new'} project={project} agent={editingAgent} availableTools={availableTools} onClose={handleClose} onDelete={() => handleDelete(editingAgent!)} />
                </DialogContent>
            </Dialog>
        </SettingsLayout>
    );
}

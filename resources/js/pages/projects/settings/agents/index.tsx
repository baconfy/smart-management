import { router } from '@inertiajs/react';
import { PlusIcon } from 'lucide-react';
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
                {agents.map((agent) => (
                    <AgentCard key={agent.id} agent={agent} onEdit={() => openEdit(agent)} />
                ))}

                {agents.length === 0 && <div className="rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground">No agents configured.</div>}
            </div>

            <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
                <SheetContent className="flex flex-col w-full sm:max-w-lg md:max-w-2xl">
                    <AgentForm key={editingAgent?.id ?? 'new'} project={project} agent={editingAgent} availableTools={availableTools} onClose={handleClose} onDelete={() => handleDelete(editingAgent!)} />
                </SheetContent>
            </Sheet>
        </SettingsLayout>
    );
}

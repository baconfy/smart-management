import { Form, router } from '@inertiajs/react';
import { PencilIcon, Trash2Icon } from 'lucide-react';
import { useState } from 'react';
import { Conversation, ConversationContent, ConversationScrollButton } from '@/components/ai-elements/conversation';
import { Shimmer } from '@/components/ai-elements/shimmer';
import { ChatPromptInput, RoutingPollInput, StreamingTurnRenderer, TurnRenderer } from '@/components/chat';
import { ConversationsNavPanel } from '@/components/navigation/conversations-nav-panel';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useMultiAgentChat } from '@/hooks/use-multi-agent-chat';
import AppLayout from '@/layouts/app-layout';
import { groupIntoTurns } from '@/lib/chat-utils';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { show } from '@/routes/projects';
import { destroy, index, rename } from '@/routes/projects/conversations';
import type { BreadcrumbItem, BreadcrumbMenuAction, Conversation as ConversationType, ConversationMessage, CursorPaginated, Project, ProjectAgent } from '@/types';
import type { ChatMessage } from '@/types/chat';

type Props = {
    project: Project;
    agents: ProjectAgent[];
    conversations: CursorPaginated<ConversationType>;
    conversation?: ConversationType | null;
    messages?: CursorPaginated<ConversationMessage> | ConversationMessage[];
};

function toInitialMessages(input: CursorPaginated<ConversationMessage> | ConversationMessage[] | undefined): ChatMessage[] {
    const raw = !input ? [] : Array.isArray(input) ? input : (input.data ?? []);
    return raw.map((m) => ({
        id: m.id,
        role: m.role,
        content: m.content,
        agentId: m.project_agent_id ?? undefined,
        agentName: m.agent ?? undefined,
        attachments: m.attachments?.length ? m.attachments : undefined,
    }));
}

export default function ConversationIndex({ project, agents, conversations, conversation = null, messages: initialMessages = [] }: Props) {
    const { messages, agentStreams, status, routingPoll, send, selectAgents, abort, error } = useMultiAgentChat({
        initialMessages: toInitialMessages(initialMessages),
        conversationId: conversation?.id ?? null,
        projectUlid: project.ulid,
        onConversationCreated: (id) => {
            history.replaceState(null, '', index({ project: project.ulid, conversation: id }).url);
        },
    });

    const [renameOpen, setRenameOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);

    const turns = groupIntoTurns(messages);
    const hasActivity = messages.length > 0 || agentStreams.size > 0 || status !== 'idle';

    const menu: BreadcrumbMenuAction[] | undefined = conversation
        ? [
              {
                  title: 'Renomear conversa',
                  icon: PencilIcon,
                  onClick: () => setRenameOpen(true),
              },
              {
                  title: 'Deletar conversa',
                  icon: Trash2Icon,
                  variant: 'destructive',
                  onClick: () => setDeleteOpen(true),
              },
          ]
        : undefined;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: conversation?.title || 'Conversations', href: index({ project: project.ulid }).url, menu },
    ];

    function handleDelete() {
        if (!conversation) return;

        router.delete(destroy.url({ project: project.ulid, conversation: conversation.id }), {
            onSuccess: () => {
                setDeleteOpen(false);
                router.visit(index({ project: project.ulid }).url);
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ConversationsNavPanel project={project} conversations={conversations} />}>
            <div className="flex min-h-0 w-full flex-1 flex-col">
                {/* Top spacer */}
                <div className={cn('transition-[flex] duration-500 ease-in-out', hasActivity ? 'flex-none' : 'flex-1')} />

                {/* Title */}
                <div className={cn('flex justify-center overflow-hidden transition-all duration-500 ease-in-out', hasActivity ? 'max-h-0 opacity-0' : 'max-h-20 pb-4 opacity-100')}>
                    <h1 className="text-2xl font-bold">What can I help with?</h1>
                </div>

                {/* Messages */}
                <div className={cn('flex flex-col overflow-hidden transition-[flex] duration-500 ease-in-out', hasActivity ? 'min-h-0 flex-1' : 'flex-none')}>
                    {hasActivity && (
                        <Conversation>
                            <ConversationContent className="mx-auto w-full max-w-4xl">
                                {turns.map((turn) => (
                                    <TurnRenderer key={turn.id} turn={turn} />
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
                    <div className={cn('mx-auto w-full shrink-0 pb-4 transition-all duration-500 ease-in-out', hasActivity ? 'max-w-4xl' : 'max-w-3xl px-4')}>
                        <ChatPromptInput onSend={send} isDisabled={status === 'streaming' || status === 'routing'} onAbort={status === 'streaming' ? abort : undefined} agents={agents} />
                    </div>
                )}

                {/* Bottom spacer */}
                <div className={cn('transition-[flex] duration-500 ease-in-out', hasActivity ? 'flex-none' : 'flex-1')} />
            </div>

            {/* Rename Dialog */}
            {conversation && (
                <Dialog open={renameOpen} onOpenChange={setRenameOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Renomear conversa</DialogTitle>
                            <DialogDescription>Digite o novo nome para esta conversa.</DialogDescription>
                        </DialogHeader>
                        <Form {...rename.form({ project: project.ulid, conversation: conversation.id })} onSuccess={() => setRenameOpen(false)}>
                            {({ processing, errors }) => (
                                <>
                                    <Input name="title" defaultValue={conversation.title ?? ''} placeholder="Nome da conversa" autoFocus />
                                    {errors.title && <p className="text-sm text-destructive">{errors.title}</p>}
                                    <DialogFooter className="mt-4">
                                        <DialogClose render={<Button variant="outline" />}>Cancelar</DialogClose>
                                        <Button type="submit" disabled={processing}>
                                            {processing && <Spinner />} Salvar
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    </DialogContent>
                </Dialog>
            )}

            {/* Delete AlertDialog */}
            <AlertDialog open={deleteOpen} onOpenChange={setDeleteOpen}>
                <AlertDialogContent size="sm">
                    <AlertDialogHeader>
                        <AlertDialogTitle>Deletar conversa</AlertDialogTitle>
                        <AlertDialogDescription>Tem certeza que deseja deletar esta conversa? Esta ação não pode ser desfeita.</AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction variant="destructive" onClick={handleDelete}>
                            Deletar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}

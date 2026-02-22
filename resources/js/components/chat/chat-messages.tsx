import ReactMarkdown from 'react-markdown';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type { ConversationMessage, ProjectAgent } from '@/types';
import { useChat } from './chat-provider';
import type { ProcessingAgent } from './chat-provider';

// --- Sub-components ---

function ThinkingDots() {
    return (
        <span className="flex items-center gap-1">
            <span className="size-1 animate-bounce rounded-full bg-primary/75 [animation-delay:-0.3s]" />
            <span className="size-1 animate-bounce rounded-full bg-primary/75 [animation-delay:-0.15s]" />
            <span className="size-1 animate-bounce rounded-full bg-primary/75" />
        </span>
    );
}

function AgentTabs({ assistantMessages, processingAgents, agents }: { assistantMessages: ConversationMessage[]; processingAgents: ProcessingAgent[]; agents: ProjectAgent[] }) {
    const allTabs = [
        ...assistantMessages.map((msg) => ({
            id: String(msg.project_agent_id),
            name: agents.find((a) => a.id === msg.project_agent_id)?.name ?? 'Agent',
            message: msg,
            isProcessing: false,
        })),
        ...processingAgents
            .filter((pa) => !assistantMessages.some((m) => m.project_agent_id === pa.id))
            .map((pa) => ({
                id: String(pa.id),
                name: pa.name,
                message: null,
                isProcessing: true,
            })),
    ];

    if (allTabs.length === 0) return null;

    return (
        <div className="flex justify-start">
            <Tabs defaultValue={allTabs[0].id} className="max-w-[80%] flex-col">
                <div className="sticky top-0 z-10 shadow-2xl">
                    <TabsList>
                        {allTabs.map((tab) => (
                            <TabsTrigger key={tab.id} value={tab.id}>
                                {tab.name}
                                {tab.isProcessing && <ThinkingDots />}
                            </TabsTrigger>
                        ))}
                    </TabsList>
                </div>

                {allTabs.map((tab) => (
                    <TabsContent key={tab.id} value={tab.id}>
                        <div className="rounded-xl bg-muted px-4 py-3 text-muted-foreground">
                            {tab.isProcessing ? (
                                <ThinkingDots />
                            ) : (
                                <div className="prose prose-base max-w-none prose-invert">
                                    <ReactMarkdown>{tab.message!.content}</ReactMarkdown>
                                </div>
                            )}
                        </div>
                    </TabsContent>
                ))}
            </Tabs>
        </div>
    );
}

// --- Main ---

export function ChatMessages() {
    const { turns, processingAgents, isRouting, agents, error, clearError, hasMoreMessages, isLoadingMore, loadMoreMessages } = useChat();

    return (
        <div className="no-scrollbar flex flex-1 flex-col-reverse overflow-y-auto pb-4">
            <div className="mx-auto w-full max-w-5xl space-y-4">
                {/* Load earlier messages */}
                {hasMoreMessages && (
                    <div className="flex justify-center">
                        <button
                            onClick={loadMoreMessages}
                            disabled={isLoadingMore}
                            className="text-sm text-muted-foreground hover:text-foreground disabled:opacity-50"
                        >
                            {isLoadingMore ? 'Loading...' : 'Load earlier messages'}
                        </button>
                    </div>
                )}

                {/* Standalone processing — no turns yet (e.g. task auto-start) */}
                {turns.length === 0 && processingAgents.length > 0 && (
                    <div className="space-y-4">
                        {processingAgents.map((pa) => (
                            <div key={pa.id} className="flex justify-start">
                                <div className="flex items-center gap-2 rounded-xl bg-muted px-4 py-3">
                                    <span className="text-sm font-medium tracking-tighter text-primary">{pa.name}</span>
                                    <ThinkingDots />
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {error && (
                    <div className="flex justify-start">
                        <div className="flex items-center gap-2 rounded-xl border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                            <span>{error}</span>
                            <button onClick={clearError} className="ml-2 text-destructive/60 hover:text-destructive">
                                ✕
                            </button>
                        </div>
                    </div>
                )}

                {turns.map((turn, i) => {
                    const turnKey = turn.userMessage?.id ?? `orphan-${turn.assistantMessages[0]?.id ?? i}`;
                    const isLastTurn = i === turns.length - 1;
                    const turnProcessing = isLastTurn ? processingAgents : [];
                    const totalResponders = turn.assistantMessages.length + turnProcessing.length;
                    const isMultiAgent = totalResponders > 1;

                    return (
                        <div key={turnKey} className="space-y-4">
                            {/* User message */}
                            {turn.userMessage && (
                                <div className="flex justify-end">
                                    <div className="max-w-[75%] rounded-xl bg-primary px-4 py-3 text-primary-foreground text-shadow-2xs">
                                        <div className="prose prose-base max-w-none prose-invert">
                                            <ReactMarkdown>{turn.userMessage.content}</ReactMarkdown>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Routing indicator */}
                            {isLastTurn && isRouting && (
                                <div className="flex justify-start">
                                    <div className="flex items-center gap-2 rounded-xl bg-muted px-4 py-3">
                                        <ThinkingDots />
                                    </div>
                                </div>
                            )}

                            {/* Multi-agent: tabs */}
                            {isMultiAgent && <AgentTabs assistantMessages={turn.assistantMessages} processingAgents={turnProcessing} agents={agents} />}

                            {/* Single agent: inline */}
                            {!isMultiAgent && turn.assistantMessages.length === 1 && (
                                <div className="flex justify-start">
                                    <div className="max-w-[75%] rounded-xl bg-muted px-4 py-3 text-muted-foreground">
                                        {turn.assistantMessages[0].project_agent_id && <span className="text-sm font-medium tracking-tighter text-primary">{agents.find((a) => a.id === turn.assistantMessages[0].project_agent_id)?.name}</span>}
                                        <div className="prose prose-base max-w-none prose-invert">
                                            <ReactMarkdown>{turn.assistantMessages[0].content}</ReactMarkdown>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Single agent still processing */}
                            {!isMultiAgent && turn.assistantMessages.length === 0 && turnProcessing.length === 1 && (
                                <div className="flex justify-start">
                                    <div className="flex items-center gap-2 rounded-xl bg-muted px-4 py-3">
                                        <span className="text-sm font-medium tracking-tighter text-primary">{turnProcessing[0].name}</span>
                                        <ThinkingDots />
                                    </div>
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

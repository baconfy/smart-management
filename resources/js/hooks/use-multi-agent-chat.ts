import { useCallback, useRef, useState } from 'react';

import { parseSseStream } from '@/lib/sse-parser';
import type { SseEvent } from '@/lib/sse-parser';
import type { AgentStream, ChatMessage, ChatStatus, RoutingPoll } from '@/types/chat';
import { stream as conversationStream, streamAgents, streamContinue } from '@/routes/projects/conversations';
import { stream as taskStream } from '@/routes/projects/tasks';

export interface UseMultiAgentChatOptions {
    initialMessages?: ChatMessage[];
    conversationId?: string | null;
    projectUlid: string;
    taskUlid?: string;
    defaultAgentIds?: number[];
    onConversationCreated?: (id: string) => void;
}

export interface UseMultiAgentChatReturn {
    messages: ChatMessage[];
    agentStreams: Map<number, AgentStream>;
    status: ChatStatus;
    routingPoll: RoutingPoll | null;
    conversationId: string | null;
    send: (content: string, agentIds?: number[]) => void;
    selectAgents: (agentIds: number[]) => void;
    abort: () => void;
    error: string | null;
}

function getXsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

export function useMultiAgentChat(options: UseMultiAgentChatOptions): UseMultiAgentChatReturn {
    const [messages, setMessages] = useState<ChatMessage[]>(options.initialMessages ?? []);
    const [agentStreams, setAgentStreams] = useState<Map<number, AgentStream>>(new Map());
    const [status, setStatus] = useState<ChatStatus>('idle');
    const [routingPoll, setRoutingPoll] = useState<RoutingPoll | null>(null);
    const [conversationId, setConversationId] = useState<string | null>(options.conversationId ?? null);
    const [error, setError] = useState<string | null>(null);

    const abortRef = useRef<AbortController | null>(null);
    const conversationIdRef = useRef<string | null>(options.conversationId ?? null);
    const agentStreamsRef = useRef<Map<number, AgentStream>>(new Map());

    // rAF-throttled chunk accumulation
    const streamBufferRef = useRef<Map<number, string>>(new Map());
    const rafRef = useRef<number | null>(null);

    const flushStreamBuffer = useCallback(() => {
        setAgentStreams((prev) => {
            const next = new Map(prev);
            for (const [id, accumulated] of streamBufferRef.current.entries()) {
                const stream = next.get(id);
                if (stream) {
                    next.set(id, { ...stream, text: stream.text + accumulated });
                }
            }
            agentStreamsRef.current = next;
            return next;
        });
        streamBufferRef.current.clear();
        rafRef.current = null;
    }, []);

    const handleChunk = useCallback(
        (agentId: number, text: string) => {
            const buffer = streamBufferRef.current;
            buffer.set(agentId, (buffer.get(agentId) ?? '') + text);

            if (!rafRef.current) {
                rafRef.current = requestAnimationFrame(flushStreamBuffer);
            }
        },
        [flushStreamBuffer],
    );

    const consumeStream = useCallback(
        async (response: Response) => {
            for await (const event of parseSseStream(response)) {
                switch (event.type) {
                    case 'conversation':
                        conversationIdRef.current = event.id;
                        setConversationId(event.id);
                        if (event.isNew) {
                            options.onConversationCreated?.(event.id);
                        }
                        break;

                    case 'routing':
                        setStatus('streaming');
                        break;

                    case 'agent_start':
                        setAgentStreams((prev) => {
                            const next = new Map(prev);
                            next.set(event.agentId, {
                                agentId: event.agentId,
                                name: event.name,
                                text: '',
                                isStreaming: true,
                                isDone: false,
                            });
                            agentStreamsRef.current = next;
                            return next;
                        });
                        // Initialize buffer for this agent
                        streamBufferRef.current.set(event.agentId, '');
                        break;

                    case 'chunk':
                        handleChunk(event.agentId, event.text);
                        break;

                    case 'agent_done': {
                        // Flush any pending buffer for this agent
                        if (rafRef.current) {
                            cancelAnimationFrame(rafRef.current);
                            rafRef.current = null;
                        }
                        const pendingBuffer = streamBufferRef.current.get(event.agentId) ?? '';
                        streamBufferRef.current.delete(event.agentId);

                        // Read accumulated text + name from the ref (synchronous, no updater)
                        const stream = agentStreamsRef.current.get(event.agentId);
                        const fullText = (stream?.text ?? '') + pendingBuffer;
                        const agentName = stream?.name;

                        // Promote finished stream into a persisted message
                        if (fullText) {
                            setMessages((msgs) => [
                                ...msgs,
                                {
                                    id: event.messageId,
                                    role: 'assistant',
                                    content: fullText,
                                    agentId: event.agentId,
                                    agentName,
                                },
                            ]);
                        }

                        // Remove from active streams
                        setAgentStreams((prev) => {
                            const next = new Map(prev);
                            next.delete(event.agentId);
                            agentStreamsRef.current = next;
                            return next;
                        });
                        break;
                    }

                    case 'routing_poll':
                        setStatus('polling');
                        setRoutingPoll({ reasoning: event.reasoning, candidates: event.candidates });
                        break;

                    case 'agent_error':
                        setAgentStreams((prev) => {
                            const next = new Map(prev);
                            const stream = next.get(event.agentId);
                            if (stream) {
                                next.set(event.agentId, { ...stream, error: event.message, isStreaming: false });
                            }
                            agentStreamsRef.current = next;
                            return next;
                        });
                        break;

                    case 'error':
                        setStatus('error');
                        setError(event.message);
                        break;

                    case 'done':
                        setStatus((prev) => (prev === 'polling' ? 'polling' : 'idle'));
                        break;
                }
            }
        },
        [handleChunk, options],
    );

    const send = useCallback(
        (content: string, agentIds?: number[]) => {
            const tempId = `temp-${Date.now()}`;

            setError(null);
            setRoutingPoll(null);
            setMessages((prev) => [...prev, { id: tempId, role: 'user', content }]);
            setStatus('routing');

            const controller = new AbortController();
            abortRef.current = controller;

            const resolvedAgentIds = agentIds ?? options.defaultAgentIds;

            // Determine URL
            let url: string;
            if (options.taskUlid) {
                url = taskStream.url({ project: options.projectUlid, task: options.taskUlid });
            } else if (conversationIdRef.current) {
                url = streamContinue.url({ project: options.projectUlid, conversation: conversationIdRef.current });
            } else {
                url = conversationStream.url(options.projectUlid);
            }

            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'text/event-stream',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getXsrfToken(),
                },
                body: JSON.stringify({
                    message: content,
                    ...(resolvedAgentIds && resolvedAgentIds.length > 0 ? { agent_ids: resolvedAgentIds } : {}),
                }),
                signal: controller.signal,
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    return consumeStream(response);
                })
                .catch((err: Error) => {
                    if (err.name === 'AbortError') {
                        return;
                    }
                    setMessages((prev) => prev.filter((m) => m.id !== tempId));
                    setStatus('error');
                    setError('Failed to send message. Please try again.');
                });
        },
        [options, consumeStream],
    );

    const selectAgentsAction = useCallback(
        (agentIds: number[]) => {
            if (!conversationIdRef.current) {
                return;
            }

            setRoutingPoll(null);
            setStatus('routing');

            const controller = new AbortController();
            abortRef.current = controller;

            const url = streamAgents.url({
                project: options.projectUlid,
                conversation: conversationIdRef.current,
            });

            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'text/event-stream',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getXsrfToken(),
                },
                body: JSON.stringify({ agent_ids: agentIds }),
                signal: controller.signal,
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    return consumeStream(response);
                })
                .catch((err: Error) => {
                    if (err.name === 'AbortError') {
                        return;
                    }
                    setStatus('error');
                    setError('Failed to submit agent selection. Please try again.');
                });
        },
        [options.projectUlid, consumeStream],
    );

    const abort = useCallback(() => {
        abortRef.current?.abort();
        if (rafRef.current) {
            cancelAnimationFrame(rafRef.current);
            rafRef.current = null;
        }
        streamBufferRef.current.clear();
        setStatus('idle');
        setAgentStreams(new Map());
    }, []);

    return {
        messages,
        agentStreams,
        status,
        routingPoll,
        conversationId,
        send,
        selectAgents: selectAgentsAction,
        abort,
        error,
    };
}

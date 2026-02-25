import type { FileUIPart } from 'ai';
import { useCallback, useMemo, useRef, useState } from 'react';

import { parseSseStream } from '@/lib/sse-parser';
import type { RoutedAgent } from '@/lib/sse-parser';
import { stream as conversationStream, streamAgents, streamContinue } from '@/routes/projects/conversations';
import { stream as taskStream } from '@/routes/projects/tasks';
import type { AgentStream, ChatAttachment, ChatMessage, ChatStatus, RoutingPoll } from '@/types/chat';

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
    send: (content: string, agentIds?: number[], files?: FileUIPart[]) => void;
    selectAgents: (agentIds: number[]) => void;
    abort: () => void;
    error: string | null;
    /** ID of the agent that most recently received a chunk */
    lastActiveAgentId: number | null;
    /** Agent IDs that responded in the most recent turn */
    lastRespondedAgentIds: number[];
}

function getXsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

export function useMultiAgentChat(options: UseMultiAgentChatOptions): UseMultiAgentChatReturn {
    'use no memo';

    const [messages, setMessages] = useState<ChatMessage[]>(options.initialMessages ?? []);
    const [agentStreams, setAgentStreams] = useState<Map<number, AgentStream>>(new Map());
    const [status, setStatus] = useState<ChatStatus>('idle');
    const [routingPoll, setRoutingPoll] = useState<RoutingPoll | null>(null);
    const [conversationId, setConversationId] = useState<string | null>(options.conversationId ?? null);
    const [error, setError] = useState<string | null>(null);

    const abortRef = useRef<AbortController | null>(null);
    const conversationIdRef = useRef<string | null>(options.conversationId ?? null);

    // Track agent order and types from routing events
    const agentOrderRef = useRef<RoutedAgent[]>([]);
    const [lastActiveAgentId, setLastActiveAgentId] = useState<number | null>(null);

    // Synchronous accumulators — updated immediately, never deferred by React
    const agentFullTextRef = useRef<Map<number, string>>(new Map());
    const agentNameRef = useRef<Map<number, string>>(new Map());

    // rAF-throttled buffer for UI display updates only
    const streamBufferRef = useRef<Map<number, string>>(new Map());
    const rafRef = useRef<number | null>(null);

    const flushStreamBuffer = useCallback(() => {
        setAgentStreams((prev) => {
            const next = new Map(prev);
            for (const [id] of streamBufferRef.current.entries()) {
                const stream = next.get(id);
                if (stream) {
                    // Use the synchronous accumulator as source of truth for display
                    next.set(id, { ...stream, text: agentFullTextRef.current.get(id) ?? '' });
                }
            }
            return next;
        });
        streamBufferRef.current.clear();
        rafRef.current = null;
    }, []);

    const handleChunk = useCallback(
        (agentId: number, text: string) => {
            // Synchronous accumulator — always up to date
            agentFullTextRef.current.set(agentId, (agentFullTextRef.current.get(agentId) ?? '') + text);

            // Mark this agent as dirty for the next rAF flush
            streamBufferRef.current.set(agentId, '');

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
                        agentOrderRef.current = event.agents;
                        setStatus('streaming');
                        break;

                    case 'agent_start': {
                        // Initialize synchronous accumulators
                        agentFullTextRef.current.set(event.agentId, '');
                        agentNameRef.current.set(event.agentId, event.name);
                        streamBufferRef.current.set(event.agentId, '');

                        // Look up agent type from routing data
                        const routedAgent = agentOrderRef.current.find((a) => a.id === event.agentId);

                        setAgentStreams((prev) => {
                            const next = new Map(prev);
                            next.set(event.agentId, {
                                agentId: event.agentId,
                                name: event.name,
                                type: routedAgent?.type ?? 'custom',
                                text: '',
                                isStreaming: true,
                                isDone: false,
                            });
                            return next;
                        });
                        break;
                    }

                    case 'chunk':
                        setLastActiveAgentId(event.agentId);
                        handleChunk(event.agentId, event.text);
                        break;

                    case 'agent_done': {
                        // Cancel any pending rAF flush
                        if (rafRef.current) {
                            cancelAnimationFrame(rafRef.current);
                            rafRef.current = null;
                        }

                        // Read from synchronous refs — always accurate, no React timing issues
                        const fullText = agentFullTextRef.current.get(event.agentId) ?? '';
                        const agentName = agentNameRef.current.get(event.agentId);

                        // Clean up refs
                        agentFullTextRef.current.delete(event.agentId);
                        agentNameRef.current.delete(event.agentId);
                        streamBufferRef.current.delete(event.agentId);

                        // Look up agent type for the promoted message
                        const doneAgentType = agentOrderRef.current.find((a) => a.id === event.agentId)?.type;

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
                                    agentType: doneAgentType,
                                },
                            ]);
                        }

                        // Remove from active streams
                        setAgentStreams((prev) => {
                            const next = new Map(prev);
                            next.delete(event.agentId);
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
        (content: string, agentIds?: number[], files?: FileUIPart[]) => {
            const tempId = `temp-${Date.now()}`;

            // Build optimistic attachments for the user message
            const optimisticAttachments: ChatAttachment[] | undefined =
                files && files.length > 0
                    ? files.map((f) => ({ filename: f.filename ?? 'file', url: f.url, mediaType: f.mediaType ?? 'application/octet-stream' }))
                    : undefined;

            setError(null);
            setRoutingPoll(null);
            setLastActiveAgentId(null);
            setMessages((prev) => [...prev, { id: tempId, role: 'user', content, attachments: optimisticAttachments }]);
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

            // Build request body: use FormData when files are present, JSON otherwise
            let headers: Record<string, string>;
            let body: FormData | string;

            if (files && files.length > 0) {
                const formData = new FormData();
                formData.append('message', content);

                if (resolvedAgentIds && resolvedAgentIds.length > 0) {
                    resolvedAgentIds.forEach((id) => formData.append('agent_ids[]', String(id)));
                }

                files.forEach((file) => {
                    // Convert data URL to Blob
                    const byteString = atob(file.url.split(',')[1]);
                    const mimeType = file.mediaType ?? 'application/octet-stream';
                    const ab = new ArrayBuffer(byteString.length);
                    const ia = new Uint8Array(ab);
                    for (let i = 0; i < byteString.length; i++) {
                        ia[i] = byteString.charCodeAt(i);
                    }
                    const blob = new Blob([ab], { type: mimeType });
                    formData.append('attachments[]', blob, file.filename ?? 'file');
                });

                headers = {
                    Accept: 'text/event-stream',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getXsrfToken(),
                };
                body = formData;
            } else {
                headers = {
                    'Content-Type': 'application/json',
                    Accept: 'text/event-stream',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getXsrfToken(),
                };
                body = JSON.stringify({
                    message: content,
                    ...(resolvedAgentIds && resolvedAgentIds.length > 0 ? { agent_ids: resolvedAgentIds } : {}),
                });
            }

            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers,
                body,
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
        agentFullTextRef.current.clear();
        agentNameRef.current.clear();
        streamBufferRef.current.clear();
        setStatus('idle');
        setAgentStreams(new Map());
    }, []);

    // Compute agent IDs that responded in the most recent turn
    const lastRespondedAgentIds = useMemo(() => {
        // Walk messages backwards to find the last user message, then collect agent IDs after it
        const ids: number[] = [];
        let foundUser = false;
        for (let i = messages.length - 1; i >= 0; i--) {
            const msg = messages[i];
            if (msg.role === 'user') {
                foundUser = true;
                break;
            }
            if (msg.agentId) {
                ids.push(msg.agentId);
            }
        }
        return foundUser ? ids : [];
    }, [messages]);

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
        lastActiveAgentId,
        lastRespondedAgentIds,
    };
}

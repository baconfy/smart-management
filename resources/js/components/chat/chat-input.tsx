import { ArrowUp, Loader2, Paperclip, X } from 'lucide-react';
import React, { createContext, useContext, useEffect, useRef, useState } from 'react';

import { useChat } from '@/components/chat/chat-provider';
import { Badge } from '@/components/ui/badge';
import { InputGroup, InputGroupAddon, InputGroupButton, InputGroupTextarea } from '@/components/ui/input-group';
import { cn } from '@/lib/utils';

// --- Types ---

type ChatInputContextValue = {
    formProcessing: boolean;
    hasContent: boolean;
    files: File[];
    textareaRef: React.RefObject<HTMLTextAreaElement | null>;
    fileInputRef: React.RefObject<HTMLInputElement | null>;
    handleFileChange: (e: React.ChangeEvent<HTMLInputElement>) => void;
    removeFile: (index: number) => void;
    setHasContent: (value: boolean) => void;
};

type ChatInputProps = {
    formProcessing?: boolean;
};

// --- Context ---

const ChatInputContext = createContext<ChatInputContextValue | null>(null);

function useChatInput() {
    const ctx = useContext(ChatInputContext);
    if (!ctx) throw new Error('useChatInput must be used within ChatInput');
    return ctx;
}

// --- Sub-components ---

function ChatInputFiles() {
    const { files, removeFile } = useChatInput();

    if (files.length === 0) return null;

    return (
        <div className="flex flex-wrap gap-1.5">
            {files.map((file, index) => (
                <Badge key={index} variant="secondary" className="gap-1 pr-1">
                    {file.name}
                    <button type="button" onClick={() => removeFile(index)} className="rounded-full text-muted-foreground hover:text-foreground">
                        <X className="size-3" />
                    </button>
                </Badge>
            ))}
        </div>
    );
}

function ChatInputTextarea() {
    const { formProcessing, textareaRef, setHasContent } = useChatInput();
    const { isBusy } = useChat();

    const disabled = formProcessing || isBusy;

    useEffect(() => {
        if (!disabled) {
            setTimeout(() => textareaRef.current?.focus(), 10);
        }
    }, [disabled, textareaRef]);

    function handleKeyDown(e: React.KeyboardEvent<HTMLTextAreaElement>) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            e.currentTarget.form?.requestSubmit();
        }
    }

    return <InputGroupTextarea name="message" ref={textareaRef} placeholder="Send a message..." onKeyDown={handleKeyDown} onChange={(e) => setHasContent(e.target.value.trim().length > 0)} disabled={disabled} autoFocus rows={1} className="max-h-56 min-h-16" />;
}

function ChatInputAttach() {
    const { formProcessing, fileInputRef, handleFileChange } = useChatInput();
    const { isBusy } = useChat();

    const disabled = formProcessing || isBusy;

    return (
        <>
            <InputGroupButton size="icon-sm" type="button" onClick={() => fileInputRef.current?.click()} aria-label="Attach file" disabled={disabled}>
                <Paperclip className="size-4" />
            </InputGroupButton>

            <input ref={fileInputRef} type="file" multiple onChange={handleFileChange} className="hidden" />
        </>
    );
}

function ChatInputAgents() {
    const { isBusy, agents, selectedAgentIds, toggleAgent } = useChat();
    const { formProcessing } = useChatInput();

    const disabled = formProcessing || isBusy;

    return (
        <div className={cn('flex items-center gap-2', { 'cursor-not-allowed opacity-50': disabled })}>
            {agents.map((agent) => (
                <Badge key={agent.id} variant={selectedAgentIds.includes(agent.id) ? 'default' : 'outline'} className={cn('font-bold select-none text-shadow-2xs', { clickable: !disabled })} onClick={() => !disabled && toggleAgent(agent.id)}>
                    {agent.name}
                </Badge>
            ))}
        </div>
    );
}

function ChatInputSubmit() {
    const { formProcessing, hasContent } = useChatInput();
    const { isBusy } = useChat();

    const disabled = formProcessing || isBusy;

    return (
        <InputGroupButton type="submit" size="icon-sm" variant={hasContent ? 'default' : 'ghost'} disabled={disabled || !hasContent} aria-label="Send message">
            {disabled ? <Loader2 className="animate-spin stroke-3" /> : <ArrowUp className="stroke-3" />}
        </InputGroupButton>
    );
}

function ChatInputFooter() {
    return (
        <InputGroupAddon align="block-end" className="flex items-center justify-between">
            <ChatInputAttach />
            <div className="flex items-center gap-2">
                <ChatInputAgents />
                <ChatInputSubmit />
            </div>
        </InputGroupAddon>
    );
}

function ChatInputPoll() {
    const { agents, poll, handleSelectAgents } = useChat();
    const [selectedIds, setSelectedIds] = useState<number[]>([]);

    if (!poll) return null;

    const candidateAgents = poll.candidates.map((c) => agents.find((a) => a.type === c.type)).filter(Boolean) as typeof agents;

    function toggle(id: number) {
        setSelectedIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
    }

    return (
        <div className="flex flex-col gap-3 p-3">
            <p className="font-mono text-base tracking-tight text-muted-foreground/75">{poll.reasoning}</p>

            <div className="flex flex-wrap items-center gap-2">
                {candidateAgents.map((agent) => (
                    <Badge key={agent.id} variant={selectedIds.includes(agent.id) ? 'default' : 'outline'} className="clickable font-bold select-none text-shadow-2xs" onClick={() => toggle(agent.id)}>
                        {agent.name}
                    </Badge>
                ))}
            </div>

            <div className="flex justify-end">
                <InputGroupButton type="button" size="sm" variant={selectedIds.length > 0 ? 'default' : 'ghost'} disabled={selectedIds.length === 0} onClick={() => handleSelectAgents(selectedIds)}>
                    Ask selected
                </InputGroupButton>
            </div>
        </div>
    );
}

// --- Root ---

export function ChatInput({ formProcessing = false }: ChatInputProps) {
    const { conversation, selectedAgentIds, poll } = useChat();
    const [hasContent, setHasContent] = useState(false);
    const [files, setFiles] = useState<File[]>([]);
    const textareaRef = useRef<HTMLTextAreaElement | null>(null);
    const fileInputRef = useRef<HTMLInputElement | null>(null);

    function handleFileChange(e: React.ChangeEvent<HTMLInputElement>) {
        if (e.target.files) {
            setFiles((prev) => [...prev, ...Array.from(e.target.files!)]);
            e.target.value = '';
        }
    }

    function removeFile(index: number) {
        setFiles((prev) => prev.filter((_, i) => i !== index));
    }

    return (
        <ChatInputContext.Provider value={{ formProcessing, hasContent, files, textareaRef, fileInputRef, handleFileChange, removeFile, setHasContent }}>
            {!poll && (
                <>
                    <input type="hidden" name="conversation_id" value={conversation.id} />
                    {selectedAgentIds.map((id) => (
                        <input key={id} type="hidden" name="agent_ids[]" value={id} />
                    ))}
                </>
            )}

            <InputGroup className={cn('h-auto flex-col rounded-xl p-0.5', { 'cursor-not-allowed': formProcessing })}>
                {poll ? (
                    <ChatInputPoll />
                ) : (
                    <>
                        <ChatInputFiles />
                        <ChatInputTextarea />
                        <ChatInputFooter />
                    </>
                )}
            </InputGroup>
        </ChatInputContext.Provider>
    );
}

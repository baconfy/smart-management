import { ArrowUp, Loader2, Paperclip, X } from 'lucide-react';
import React, { createContext, useContext, useEffect, useRef, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { InputGroup, InputGroupAddon, InputGroupButton, InputGroupTextarea } from '@/components/ui/input-group';
import { cn } from '@/lib/utils';
import type { ProjectAgent } from '@/types/models';

// --- Types ---

type InputChatContextValue = {
    agents: ProjectAgent[];
    processing: boolean;
    selectedAgentIds: number[];
    hasContent: boolean;
    files: File[];
    textareaRef: React.RefObject<HTMLTextAreaElement | null>;
    fileInputRef: React.RefObject<HTMLInputElement | null>;
    toggleAgent: (id: number) => void;
    handleFileChange: (e: React.ChangeEvent<HTMLInputElement>) => void;
    removeFile: (index: number) => void;
    setHasContent: (value: boolean) => void;
};

type InputChatProps = {
    agents: ProjectAgent[];
    processing?: boolean;
    conversationId?: string;
    textareaRef?: React.RefObject<HTMLTextAreaElement | null>;
};

// --- Context ---

const InputChatContext = createContext<InputChatContextValue | null>(null);

function useInputChat() {
    const ctx = useContext(InputChatContext);
    if (!ctx) throw new Error('useInputChat must be used within InputChat');
    return ctx;
}

// --- Sub-components ---

function InputChatFiles() {
    const { files, removeFile } = useInputChat();

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

function InputChatTextarea() {
    const { processing, textareaRef, setHasContent } = useInputChat();

    useEffect(() => {
        if (!processing) {
            setTimeout(() => textareaRef.current?.focus(), 10);
        }
    }, [processing, textareaRef]);

    function handleKeyDown(e: React.KeyboardEvent<HTMLTextAreaElement>) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            e.currentTarget.form?.requestSubmit();
        }
    }

    return (
        <InputGroupTextarea
            name="message"
            ref={textareaRef}
            placeholder="Send a message..."
            onKeyDown={handleKeyDown}
            onChange={(e) => setHasContent(e.target.value.trim().length > 0)}
            disabled={processing}
            autoFocus
            rows={1}
            className="max-h-56 min-h-16"
        />
    );
}

function InputChatAttach() {
    const { processing, fileInputRef, handleFileChange } = useInputChat();

    return (
        <>
            <InputGroupButton size="icon-sm" type="button" onClick={() => fileInputRef.current?.click()} aria-label="Attach file" disabled={processing}>
                <Paperclip className="size-4" />
            </InputGroupButton>
            <input ref={fileInputRef} type="file" multiple onChange={handleFileChange} className="hidden" />
        </>
    );
}

function InputChatAgents() {
    const { agents, processing, selectedAgentIds, toggleAgent } = useInputChat();

    return (
        <div className={cn('flex items-center gap-2', { 'cursor-not-allowed opacity-50': processing })}>
            {agents.map((agent) => (
                <Badge
                    key={agent.id}
                    variant={selectedAgentIds.includes(agent.id) ? 'default' : 'outline'}
                    className={cn('select-none pb-[0.05rem] text-shadow-2xs', { clickable: !processing })}
                    onClick={() => !processing && toggleAgent(agent.id)}
                >
                    {agent.name}
                </Badge>
            ))}
        </div>
    );
}

function InputChatSubmit() {
    const { processing, hasContent } = useInputChat();

    return (
        <InputGroupButton type="submit" size="icon-sm" variant={hasContent ? 'default' : 'ghost'} disabled={processing || !hasContent} aria-label="Send message">
            {processing ? <Loader2 className="animate-spin stroke-3" /> : <ArrowUp className="stroke-3" />}
        </InputGroupButton>
    );
}

function InputChatFooter() {
    return (
        <InputGroupAddon align="block-end" className="flex items-center justify-between">
            <InputChatAttach />
            <div className="flex items-center gap-2">
                <InputChatAgents />
                <InputChatSubmit />
            </div>
        </InputGroupAddon>
    );
}

// --- Root ---

export function InputChat({ agents, processing = false, conversationId, textareaRef: externalRef }: InputChatProps) {
    const [selectedAgentIds, setSelectedAgentIds] = useState<number[]>([]);
    const [hasContent, setHasContent] = useState(false);
    const [files, setFiles] = useState<File[]>([]);

    const internalRef = useRef<HTMLTextAreaElement | null>(null);
    const textareaRef = externalRef ?? internalRef;
    const fileInputRef = useRef<HTMLInputElement | null>(null);

    function toggleAgent(id: number) {
        setSelectedAgentIds((prev) => (prev.includes(id) ? prev.filter((a) => a !== id) : [...prev, id]));
    }

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
        <InputChatContext.Provider value={{ agents, processing, selectedAgentIds, hasContent, files, textareaRef, fileInputRef, toggleAgent, handleFileChange, removeFile, setHasContent }}>
            {conversationId && <input type="hidden" name="conversation_id" value={conversationId} />}
            {selectedAgentIds.map((id) => (
                <input key={id} type="hidden" name="agent_ids[]" value={id} />
            ))}

            <InputGroup className={cn('h-auto flex-col rounded-xl p-0.5', { 'cursor-not-allowed': processing })}>
                <InputChatFiles />
                <InputChatTextarea />
                <InputChatFooter />
            </InputGroup>
        </InputChatContext.Provider>
    );
}

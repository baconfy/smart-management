import { ArrowUp, Paperclip, X } from 'lucide-react';
import React, { useRef, useState, type KeyboardEvent } from 'react';

import { Badge } from '@/components/ui/badge';
import { InputGroup, InputGroupAddon, InputGroupButton, InputGroupTextarea } from '@/components/ui/input-group';
import type { ProjectAgent } from '@/types/models';

type ChatInputProps = {
    agents: ProjectAgent[];
    onSend: (message: string, agentIds: number[], files: File[]) => void;
    disabled?: boolean;
};

export function ChatInput({ agents, onSend, disabled = false }: ChatInputProps) {
    const [message, setMessage] = useState('');
    const [selectedAgentIds, setSelectedAgentIds] = useState<number[]>([]);
    const [files, setFiles] = useState<File[]>([]);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const canSend = message.trim().length > 0 && !disabled;

    function handleSend() {
        if (!canSend) return;
        onSend(message.trim(), selectedAgentIds, files);
        setMessage('');
        setFiles([]);
    }

    function handleKeyDown(e: KeyboardEvent<HTMLTextAreaElement>) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSend();
        }
    }

    function toggleAgent(agentId: number) {
        setSelectedAgentIds((prev) => (prev.includes(agentId) ? prev.filter((id) => id !== agentId) : [...prev, agentId]));
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
        <InputGroup className="h-auto flex-col rounded-xl">
            {files.length > 0 && (
                <InputGroupAddon align="block-start" className="flex-wrap gap-1.5">
                    {files.map((file, index) => (
                        <Badge key={index} variant="secondary" className="gap-1 pr-1">
                            {file.name}
                            <button type="button" onClick={() => removeFile(index)} className="rounded-full text-muted-foreground hover:text-foreground">
                                <X className="size-3" />
                            </button>
                        </Badge>
                    ))}
                </InputGroupAddon>
            )}

            <InputGroupTextarea placeholder="Send a message..." value={message} onChange={(e) => setMessage(e.target.value)} onKeyDown={handleKeyDown} disabled={disabled} rows={1} className="max-h-52 min-h-14" />

            <InputGroupAddon align="block-end" className="flex items-center justify-between">
                <InputGroupButton size="icon-sm" onClick={() => fileInputRef.current?.click()} aria-label="Attach file">
                    <Paperclip className="size-4" />
                </InputGroupButton>

                <input ref={fileInputRef} type="file" multiple onChange={handleFileChange} className="hidden" />

                <div className="flex items-center gap-1.5">
                    {agents.map((agent) => (
                        <Badge key={agent.id} variant={selectedAgentIds.includes(agent.id) ? 'default' : 'outline'} className="cursor-pointer select-none px-2.5" onClick={() => toggleAgent(agent.id)}>
                            {agent.name}
                        </Badge>
                    ))}

                    <InputGroupButton size="icon-sm" variant={canSend ? 'default' : 'ghost'} onClick={handleSend} disabled={!canSend} aria-label="Send message">
                        <ArrowUp className="size-4 stroke-3" />
                    </InputGroupButton>
                </div>
            </InputGroupAddon>
        </InputGroup>
    );
}

import { ArrowUp, Loader2, Paperclip, X } from 'lucide-react';
import React, { useRef, useState, type KeyboardEvent } from 'react';

import { Badge } from '@/components/ui/badge';
import { InputGroup, InputGroupAddon, InputGroupButton, InputGroupTextarea } from '@/components/ui/input-group';
import type { ProjectAgent } from '@/types/models';

type InputChatProps = {
    agents: ProjectAgent[];
    processing?: boolean;
    conversationId?: string;
};

export function InputChat({ agents, processing = false, conversationId }: InputChatProps) {
    const [selectedAgentIds, setSelectedAgentIds] = useState<number[]>([]);
    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const [hasContent, setHasContent] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [files, setFiles] = useState<File[]>([]);

    function handleKeyDown(e: KeyboardEvent<HTMLTextAreaElement>) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            e.currentTarget.form?.requestSubmit();
            setTimeout(() => textareaRef.current?.focus(), 10);
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
        <>
            {conversationId && <input type="hidden" name="conversation_id" value={conversationId} />}

            {selectedAgentIds.map((id) => (
                <input key={id} type="hidden" name="agent_ids[]" value={id} />
            ))}

            <InputGroup className="h-auto flex-col rounded-xl p-0.5">
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

                <InputGroupTextarea name="message" ref={textareaRef} placeholder="Send a message..." onKeyDown={handleKeyDown} onChange={(e) => setHasContent(e.target.value.trim().length > 0)} disabled={processing} autoFocus={true} rows={1} className="max-h-56 min-h-16" />

                <InputGroupAddon align="block-end" className="flex items-center justify-between">
                    <InputGroupButton size="icon-sm" onClick={() => fileInputRef.current?.click()} aria-label="Attach file">
                        <Paperclip className="size-4" />
                    </InputGroupButton>

                    <input ref={fileInputRef} type="file" multiple onChange={handleFileChange} className="hidden" />

                    <div className="flex items-center gap-2">
                        {agents.map((agent) => (
                            <Badge key={agent.id} variant={selectedAgentIds.includes(agent.id) ? 'default' : 'outline'} className="clickable select-none" onClick={() => toggleAgent(agent.id)}>
                                {agent.name}
                            </Badge>
                        ))}

                        <InputGroupButton type="submit" size="icon-sm" variant={hasContent ? 'default' : 'ghost'} disabled={processing || !hasContent} aria-label="Send message">
                            {processing ? <Loader2 className="animate-spin stroke-3" /> : <ArrowUp className="stroke-3" />}
                        </InputGroupButton>
                    </div>
                </InputGroupAddon>
            </InputGroup>
        </>
    );
}

import type { FileUIPart } from 'ai';
import { PaperclipIcon, SquareIcon } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { Attachment, AttachmentPreview, AttachmentRemove, Attachments } from '@/components/ai-elements/attachments';
import { PromptInput, PromptInputFooter, PromptInputSubmit, PromptInputTextarea, usePromptInputAttachments } from '@/components/ai-elements/prompt-input';
import { Badge } from '@/components/ui/badge';
import { InputGroupButton } from '@/components/ui/input-group';
import { cn } from '@/lib/utils';
import type { ProjectAgent } from '@/types';

const MAX_FILES = 10;
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

function AttachButton({ disabled }: { disabled: boolean }) {
    const { openFileDialog } = usePromptInputAttachments();

    return (
        <InputGroupButton size="icon-sm" type="button" variant="ghost" disabled={disabled} onClick={openFileDialog} aria-label="Attach files">
            <PaperclipIcon className="size-4" />
        </InputGroupButton>
    );
}

function AttachmentPreviews() {
    const { files, remove } = usePromptInputAttachments();

    if (files.length === 0) {
        return null;
    }

    return (
        <div className="flex w-full justify-start px-4 pt-3">
            <Attachments variant="inline">
                {files.map((file) => (
                    <Attachment key={file.id} data={file} onRemove={() => remove(file.id)}>
                        <AttachmentPreview />
                        <span className="max-w-32 truncate text-xs">{file.filename}</span>
                        <AttachmentRemove />
                    </Attachment>
                ))}
            </Attachments>
        </div>
    );
}

export function ChatPromptInput({ onSend, isDisabled, onAbort, agents, defaultSelectedAgentIds = [], lastRespondedAgentIds = [] }: { onSend: (content: string, agentIds?: number[], files?: FileUIPart[]) => void; isDisabled: boolean; onAbort?: () => void; agents: ProjectAgent[]; defaultSelectedAgentIds?: number[]; lastRespondedAgentIds?: number[] }) {
    const [selectedAgentIds, setSelectedAgentIds] = useState<number[]>(defaultSelectedAgentIds);
    const selectedAgentIdsRef = useRef(selectedAgentIds);
    selectedAgentIdsRef.current = selectedAgentIds;
    const onSendRef = useRef(onSend);
    onSendRef.current = onSend;
    const textareaRef = useRef<HTMLTextAreaElement>(null);

    useEffect(() => {
        if (!isDisabled) {
            textareaRef.current?.focus();
        }
    }, [isDisabled]);

    function toggleAgent(id: number) {
        setSelectedAgentIds((prev) => (prev.includes(id) ? prev.filter((a) => a !== id) : [...prev, id]));
        textareaRef.current?.focus();
    }

    function handleSubmit({ text, files }: { text: string; files: FileUIPart[] }) {
        if (!text.trim()) {
            return;
        }

        const agentIds = selectedAgentIdsRef.current;
        onSendRef.current(text.trim(), agentIds.length > 0 ? agentIds : undefined, files.length > 0 ? files : undefined);
    }

    return (
        <PromptInput onSubmit={handleSubmit} multiple maxFiles={MAX_FILES} maxFileSize={MAX_FILE_SIZE}>
            <AttachmentPreviews />

            <PromptInputTextarea ref={textareaRef} placeholder="Send a message..." disabled={isDisabled} className="text-base px-4 py-3.5" autoFocus={true} />

            <PromptInputFooter>
                <AttachButton disabled={isDisabled} />

                <div className="flex items-center gap-2">
                    <div className={cn('flex items-center gap-2', { 'cursor-not-allowed opacity-50': isDisabled })}>
                        {agents.map((agent) => (
                            <Badge key={agent.id} variant={selectedAgentIds.includes(agent.id) ? 'default' : 'outline'} className={cn('clickable rounded px-3 py-2.5 font-bold select-none text-shadow-2xs', { 'cursor-pointer': !isDisabled }, lastRespondedAgentIds.includes(agent.id) && !selectedAgentIds.includes(agent.id) && 'border-primary/30')} onClick={() => !isDisabled && toggleAgent(agent.id)}>
                                {agent.name}
                            </Badge>
                        ))}
                    </div>

                    {onAbort ? (
                        <InputGroupButton size="icon-sm" type="button" onClick={onAbort} aria-label="Stop">
                            <SquareIcon className="size-4" />
                        </InputGroupButton>
                    ) : (
                        <PromptInputSubmit disabled={isDisabled} />
                    )}
                </div>
            </PromptInputFooter>
        </PromptInput>
    );
}

import { Form, router } from '@inertiajs/react';
import { RotateCcw, SaveIcon, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldDescription, FieldError, FieldGroup, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { reset, store, update } from '@/routes/projects/agents';
import type { Project, ProjectAgent } from '@/types';

export function AgentForm({ project, agent, availableTools, onClose, onDelete }: { project: Project; agent: ProjectAgent | null; availableTools: string[]; onClose: () => void; onDelete: () => void }) {
    const isEditing = agent !== null;
    const [selectedTools, setSelectedTools] = useState<string[]>(agent?.tools ?? []);

    function toggleTool(tool: string) {
        setSelectedTools((prev) => (prev.includes(tool) ? prev.filter((t) => t !== tool) : [...prev, tool]));
    }

    const formProps = isEditing ? update.form({ project: project.ulid, agent: agent.id }) : store.form({ project: project.ulid });

    const formatToolName = (text: string) => {
        return text.replace(/([A-Z])/g, ' $1').trim();
    };

    return (
        <Form {...formProps} onSuccess={onClose} className="flex flex-1 flex-col">
            {({ processing, errors }) => (
                <>
                    <div className="flex-1 space-y-6 overflow-y-auto p-4">
                        <FieldSet>
                            <FieldLegend>{isEditing ? 'Edit Agent' : 'Create Agent'}</FieldLegend>
                            <FieldDescription>{isEditing ? `Editing ${agent.name}` : 'Add a new custom agent to your project.'}</FieldDescription>

                            <FieldGroup>
                                <Field>
                                    <FieldLabel htmlFor="name">Name</FieldLabel>
                                    <Input id="name" name="name" defaultValue={agent?.name ?? ''} placeholder="e.g. DevOps, QA, Designer" autoFocus />
                                    {errors.name && <FieldError>{errors.name}</FieldError>}
                                </Field>

                                <Field>
                                    <FieldLabel htmlFor="model">Model</FieldLabel>
                                    <Input id="model" name="model" defaultValue={agent?.model ?? ''} placeholder="e.g. gpt-4o, claude-sonnet-4-20250514" />
                                    <FieldDescription>Leave empty to use the project default.</FieldDescription>
                                    {errors.model && <FieldError>{errors.model}</FieldError>}
                                </Field>

                                <Field>
                                    <FieldLabel htmlFor="instructions">Instructions</FieldLabel>
                                    <Textarea id="instructions" name="instructions" defaultValue={agent?.instructions ?? ''} placeholder="Describe the agent's role and behavior..." className="max-h-96 min-h-48" />
                                    {errors.instructions && <FieldError>{errors.instructions}</FieldError>}
                                </Field>

                                <Field>
                                    <FieldLabel>Tools</FieldLabel>
                                    <div className="mt-2 grid grid-cols-3 gap-2">
                                        {availableTools.map((tool) => (
                                            <label key={tool} className="flex cursor-pointer items-center gap-2 rounded-md border border-border px-3 py-2 text-sm transition-colors hover:bg-muted">
                                                <Checkbox checked={selectedTools.includes(tool)} onCheckedChange={() => toggleTool(tool)} />
                                                <span>{formatToolName(tool)}</span>
                                            </label>
                                        ))}
                                    </div>
                                    {selectedTools.map((tool) => (
                                        <input key={tool} type="hidden" name="tools[]" value={tool} />
                                    ))}
                                </Field>
                            </FieldGroup>

                            <div className="space-y-2">
                                <Button type="submit" disabled={processing} className="w-full">
                                    <SaveIcon />
                                    {processing && <Spinner />} {isEditing ? 'Save Changes' : 'Create Agent'}
                                </Button>

                                {isEditing && (
                                    <>
                                        {agent.is_default && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                className="w-full"
                                                onClick={() => {
                                                    router.post(reset({ project: project.ulid, agent: agent?.id }).url, {}, { onSuccess: onClose });
                                                }}
                                            >
                                                <RotateCcw />
                                                Reset to Default
                                            </Button>
                                        )}

                                        <AlertDialog>
                                            <AlertDialogTrigger
                                                render={
                                                    <Button variant="destructive" className="w-full">
                                                        <Trash2 />
                                                        Remove agent
                                                    </Button>
                                                }
                                            />
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                                                    <AlertDialogDescription>{`Delete "${agent.name}"? This action cannot be undone.`}</AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                    <AlertDialogAction onClick={onDelete}>Continue</AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </>
                                )}
                            </div>
                        </FieldSet>
                    </div>
                </>
            )}
        </Form>
    );
}

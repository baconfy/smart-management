import { Form } from '@inertiajs/react';
import { PlusCircleIcon, SaveIcon } from 'lucide-react';
import React from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import { Field, FieldDescription, FieldError, FieldGroup, FieldLegend, FieldSeparator, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { store } from '@/routes/projects';

export default function ProjectCreate() {
    return (
        <Dialog>
            <DialogTrigger render={<Button />}>
                <PlusCircleIcon /> Create Project
            </DialogTrigger>

            <DialogContent showCloseButton={false}>
                <Form {...store.form()} options={{ preserveScroll: true, preserveState: true }} resetOnSuccess={['title', 'description']}>
                    {({ processing, errors }) => (
                        <FieldSet>
                            <FieldLegend>Create new project</FieldLegend>
                            <FieldDescription>Fill in the details below to get started. You can update these settings later in your project dashboard.</FieldDescription>
                            <FieldGroup>
                                <Field>
                                    <Label htmlFor="name">Name</Label>
                                    <Input id="name" type="text" name="name" placeholder="Type the project name here" autoComplete="current-name" autoFocus />
                                    {errors.name && <FieldError>{errors.name}</FieldError>}
                                </Field>
                                <Field>
                                    <Label htmlFor="color">Color</Label>
                                    <Input id="color" type="color" name="color" placeholder="Type the project color here" autoComplete="current-color" />
                                    {errors.color && <FieldError>{errors.color}</FieldError>}
                                </Field>
                                <Field>
                                    <Label htmlFor="description">Description</Label>
                                    <Textarea id="description" name="description" placeholder="Type the project description here" autoComplete="current-description" />
                                    {errors.description && <FieldError>{errors.description}</FieldError>}
                                </Field>
                            </FieldGroup>
                            <FieldSeparator />
                            <FieldGroup>
                                <Field>
                                    <Button className="w-full" disabled={processing} type="submit">
                                        {processing ? <Spinner /> : <SaveIcon />}
                                        {processing ? 'Creating...' : 'Create'}
                                    </Button>
                                </Field>
                            </FieldGroup>
                        </FieldSet>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

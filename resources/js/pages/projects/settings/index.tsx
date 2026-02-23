import { Form, router } from '@inertiajs/react';
import { SaveIcon, Trash2 } from 'lucide-react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import SettingsLayout from '@/layouts/project/settings-layout';
import { dashboard } from '@/routes';
import { show } from '@/routes/projects';
import { destroy, update } from '@/routes/projects/settings';
import type { BreadcrumbItem, Project } from '@/types';

export default function ProjectsPage({ project }: { project: Project }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: 'Settings', href: '#' },
    ];

    return (
        <SettingsLayout breadcrumbs={breadcrumbs} project={project}>
            <div className="space-y-8">
                <div className="space-y-4">
                    <h2 className="text-lg font-semibold">General</h2>

                    <Form {...update.form({ project: project.ulid })}>
                        {({ processing, errors }) => (
                            <FieldGroup>
                                <Field>
                                    <FieldLabel htmlFor="name">Project Name</FieldLabel>
                                    <Input id="name" name="name" defaultValue={project.name} />
                                    {errors.name && <FieldError>{errors.name}</FieldError>}
                                </Field>
                                <Field>
                                    <Button type="submit" disabled={processing}>
                                        {processing ? <Spinner /> : <SaveIcon />}
                                        {processing ? 'Saving...' : 'Save'}
                                    </Button>
                                </Field>
                            </FieldGroup>
                        )}
                    </Form>
                </div>

                <div className="space-y-4 rounded-lg border border-red-200 p-4 dark:border-red-900">
                    <div>
                        <h2 className="text-lg font-semibold text-red-600 dark:text-red-400">Danger Zone</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Deleting a project is permanent and cannot be undone. All conversations, tasks, decisions, and other data will be lost.
                        </p>
                    </div>

                    <AlertDialog>
                        <AlertDialogTrigger
                            render={
                                <Button variant="destructive">
                                    <Trash2 />
                                    Delete Project
                                </Button>
                            }
                        />
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                                <AlertDialogDescription>
                                    {`This will permanently delete "${project.name}" and all of its data. This action cannot be undone.`}
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                <AlertDialogAction onClick={() => router.delete(destroy(project.ulid).url)}>Delete Project</AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </div>
            </div>
        </SettingsLayout>
    );
}

import { Form, Link, usePage } from '@inertiajs/react';
import { SaveIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldError, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import ProfileLayout from '@/layouts/settings/profile-layout';
import { dashboard } from '@/routes';
import { update } from '@/routes/user-profile-information';
import { send } from '@/routes/verification';
import type { BreadcrumbItem, SharedData } from '@/types';

export default function ProfilePage({ mustVerifyEmail, status }: { mustVerifyEmail: boolean; status?: string }) {
    const { auth } = usePage<SharedData>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Profile', href: '#' },
    ];

    return (
        <ProfileLayout breadcrumbs={breadcrumbs}>
            <Form {...update.form()} options={{ preserveScroll: true }} className="space-y-6">
                {({ processing, recentlySuccessful, errors }) => (
                    <Card>
                        <CardHeader className="border-b">
                            <CardTitle>Profile Information</CardTitle>
                            <CardDescription>Update your name and email address.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <>
                                <FieldGroup>
                                    <Field>
                                        <FieldLabel htmlFor="name">Name</FieldLabel>
                                        <Input id="name" name="name" defaultValue={auth.user.name} autoComplete="name" />
                                        {errors.name && <FieldError>{errors.name}</FieldError>}
                                    </Field>

                                    <Field>
                                        <FieldLabel htmlFor="email">Email</FieldLabel>
                                        <Input id="email" type="email" name="email" defaultValue={auth.user.email} autoComplete="username" />
                                        {errors.email && <FieldError>{errors.email}</FieldError>}
                                    </Field>
                                </FieldGroup>

                                {mustVerifyEmail && auth.user.email_verified_at === null && (
                                    <div>
                                        <p className="text-sm text-muted-foreground">
                                            Your email address is unverified.{' '}
                                            <Link href={send().url} method="post" as="button" className="text-foreground underline underline-offset-4">
                                                Click here to resend the verification email.
                                            </Link>
                                        </p>

                                        {status === 'verification-link-sent' && <p className="mt-2 text-sm font-medium text-green-600">A new verification link has been sent to your email address.</p>}
                                    </div>
                                )}
                            </>
                        </CardContent>
                        <CardFooter className="flex border-t items-center gap-4">
                            <Button type="submit" className="w-full" disabled={processing}>
                                {processing ? <Spinner /> : <SaveIcon />}
                                {processing ? 'Saving...' : 'Save'}
                            </Button>

                            {recentlySuccessful && <p className="text-sm text-muted-foreground">Saved.</p>}
                        </CardFooter>
                    </Card>
                )}
            </Form>
        </ProfileLayout>
    );
}

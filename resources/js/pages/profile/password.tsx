import { Form } from '@inertiajs/react';
import { SaveIcon } from 'lucide-react';
import { useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldError, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import ProfileLayout from '@/layouts/settings/profile-layout';
import { dashboard } from '@/routes';
import { password } from '@/routes/profile';
import { update } from '@/routes/user-password';
import type { BreadcrumbItem } from '@/types';

export default function PasswordPage() {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Password', href: password().url },
    ];

    return (
        <ProfileLayout breadcrumbs={breadcrumbs}>
            <Form
                action={update.url()}
                method="put"
                options={{ preserveScroll: true }}
                resetOnError={['password', 'password_confirmation', 'current_password']}
                resetOnSuccess
                onError={(errors) => {
                    if (errors.password) {
                        passwordInput.current?.focus();
                    }

                    if (errors.current_password) {
                        currentPasswordInput.current?.focus();
                    }
                }}
                className="space-y-6"
            >
                {({ processing, recentlySuccessful, errors }) => (
                    <Card>
                        <CardHeader className="border-b">
                            <CardTitle>Update Password</CardTitle>
                            <CardDescription>Ensure your account is using a long, random password to stay secure.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <FieldGroup>
                                <Field>
                                    <FieldLabel htmlFor="current_password">Current Password</FieldLabel>
                                    <Input ref={currentPasswordInput} id="current_password" type="password" name="current_password" autoComplete="current-password" placeholder="Current password" />
                                    {errors.current_password && <FieldError>{errors.current_password}</FieldError>}
                                </Field>

                                <Field>
                                    <FieldLabel htmlFor="password">New Password</FieldLabel>
                                    <Input ref={passwordInput} id="password" type="password" name="password" autoComplete="new-password" placeholder="New password" />
                                    {errors.password && <FieldError>{errors.password}</FieldError>}
                                </Field>

                                <Field>
                                    <FieldLabel htmlFor="password_confirmation">Confirm Password</FieldLabel>
                                    <Input id="password_confirmation" type="password" name="password_confirmation" autoComplete="new-password" placeholder="Confirm password" />
                                    {errors.password_confirmation && <FieldError>{errors.password_confirmation}</FieldError>}
                                </Field>
                            </FieldGroup>
                        </CardContent>
                        <CardFooter className="flex items-center gap-4 border-t">
                            <Button type="submit" className="w-full" disabled={processing}>
                                {processing ? <Spinner /> : <SaveIcon />}
                                {processing ? 'Saving...' : 'Change password'}
                            </Button>

                            {recentlySuccessful && <p className="text-sm text-muted-foreground">Saved.</p>}
                        </CardFooter>
                    </Card>
                )}
            </Form>
        </ProfileLayout>
    );
}

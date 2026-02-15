import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import React from 'react';
import { Button } from '@/components/ui/button';
import { Field, FieldDescription, FieldError, FieldGroup, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/auth-layout';
import { login } from '@/routes';
import { email } from '@/routes/password';

export default function ForgotPassword({ status }: { status?: string }) {
    return (
        <AuthLayout>
            <Head title="Forgot password" />

            <Form {...email.form()} resetOnSuccess={['email']} className="p-6 md:p-8">
                {({ processing, errors }) => (
                    <>
                        <FieldSet>
                            <FieldLegend>Forgot password</FieldLegend>
                            <FieldDescription>Enter your email to receive a password reset link</FieldDescription>

                            {status && <FieldGroup className="mb-4 text-center text-sm font-medium text-green-600">{status}</FieldGroup>}

                            <FieldGroup>
                                <Field>
                                    <FieldLabel htmlFor="email">Email</FieldLabel>
                                    <Input id="email" type="email" name="email" autoFocus tabIndex={1} autoComplete="email" placeholder="email@example.com" />
                                    {errors.email && <FieldError>{errors.email}</FieldError>}
                                </Field>
                            </FieldGroup>

                            <FieldGroup>
                                <Field>
                                    <Button className="w-full" disabled={processing} data-test="email-password-reset-link-button">
                                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                        Email password reset link
                                    </Button>
                                </Field>
                            </FieldGroup>
                        </FieldSet>

                        <p className="mt-8 text-center">
                            Or, return to <Link href={login()}>log in</Link>
                        </p>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}

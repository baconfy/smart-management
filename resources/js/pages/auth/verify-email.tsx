import { Form, Head, Link } from '@inertiajs/react';
import React from 'react';
import { Button } from '@/components/ui/button';
import { Field, FieldDescription, FieldGroup, FieldLegend, FieldSet } from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    return (
        <AuthLayout>
            <Head title="Email verification" />

            <Form {...send.form()} className="p-6 md:p-8">
                {({ processing }) => (
                    <>
                        <FieldSet>
                            <FieldLegend>Verify email</FieldLegend>
                            <FieldDescription>Please verify your email address by clicking on the link we just emailed to you.</FieldDescription>

                            {status && <FieldGroup className="mb-4 text-center text-sm font-medium text-green-600">A new verification link has been sent to the email address you provided during registration.</FieldGroup>}

                            <FieldGroup>
                                <Field>
                                    <Button disabled={processing} variant="secondary">
                                        {processing && <Spinner />} Resend verification email
                                    </Button>
                                </Field>
                            </FieldGroup>
                        </FieldSet>

                        <p className="mt-8 text-center">
                            <Link href={logout()}>Log out</Link>
                        </p>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}

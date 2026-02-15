import { Form, Head } from '@inertiajs/react';
import React from 'react';
import { Button } from '@/components/ui/button';
import { Field, FieldDescription, FieldError, FieldGroup, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { update } from '@/routes/password';

export default function ResetPassword({ token, email }: { token: string; email: string }) {
    return (
        <AuthLayout>
            <Head title="Reset password" />

            <Form {...update.form()} transform={(data) => ({ ...data, token, email })} resetOnSuccess={['password', 'password_confirmation']} className="p-6 md:p-8">
                {({ processing, errors }) => (
                    <>
                        <FieldSet>
                            <FieldLegend>Reset password</FieldLegend>
                            <FieldDescription>Please enter your new password below</FieldDescription>

                            <FieldGroup>
                                <Field>
                                    <Label htmlFor="email">Email</Label>
                                    <Input id="email" type="email" name="email" autoComplete="email" value={email} className="mt-1 block w-full" readOnly />
                                    {errors.name && <FieldError>{errors.name}</FieldError>}
                                </Field>
                                <Field>
                                    <Label htmlFor="password">Password</Label>
                                    <Input id="password" type="password" name="password" autoComplete="new-password" className="mt-1 block w-full" autoFocus placeholder="Password" />
                                    {errors.password && <FieldError>{errors.password}</FieldError>}
                                </Field>
                                <Field>
                                    <Label htmlFor="password_confirmation">Confirm password</Label>
                                    <Input id="password_confirmation" type="password" name="password_confirmation" autoComplete="new-password" className="mt-1 block w-full" placeholder="Confirm password" />
                                    {errors.password_confirmation && <FieldError>{errors.password_confirmation}</FieldError>}
                                </Field>
                            </FieldGroup>
                            <FieldGroup>
                                <Field>
                                    <Button type="submit" className="mt-4 w-full" disabled={processing} data-test="reset-password-button">
                                        {processing && <Spinner />}
                                        Reset password
                                    </Button>
                                </Field>
                            </FieldGroup>
                        </FieldSet>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}

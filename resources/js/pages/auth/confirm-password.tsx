import { Form, Head } from '@inertiajs/react';
import React from 'react';
import { Button } from '@/components/ui/button';
import { Field, FieldDescription, FieldError, FieldGroup, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { store } from '@/routes/password/confirm';

export default function ConfirmPassword() {
    return (
        <AuthLayout>
            <Head title="Confirm password" />

            <Form {...store.form()} resetOnSuccess={['password']} className="p-6 md:p-8">
                {({ processing, errors }) => (
                    <>
                        <FieldSet>
                            <FieldLegend>Confirm your password</FieldLegend>
                            <FieldDescription>This is a secure area of the application. Please confirm your password before continuing.</FieldDescription>

                            <FieldGroup>
                                <Field>
                                    <Label htmlFor="password">Password</Label>
                                    <Input id="password" type="password" name="password" placeholder="Password" autoComplete="current-password" autoFocus />
                                    {errors.password && <FieldError>{errors.password}</FieldError>}
                                </Field>
                            </FieldGroup>

                            <FieldGroup>
                                <Field>
                                    <Button className="w-full" disabled={processing} data-test="confirm-password-button">
                                        {processing && <Spinner />}
                                        Confirm password
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

import { Form, Head, Link } from '@inertiajs/react';
import React from 'react';
import { Button } from '@/components/ui/button';
import { Field, FieldDescription, FieldError, FieldGroup, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { login } from '@/routes';

export default function Register() {
    return (
        <AuthLayout>
            <Head title="Register" />

            <Form action="/register" method="post" resetOnSuccess={['password', 'password_confirmation']} disableWhileProcessing className="p-6 md:p-8">
                {({ processing, errors }) => (
                    <>
                        <FieldSet>
                            <FieldLegend>Create an account</FieldLegend>
                            <FieldDescription>Enter your details below to create your account</FieldDescription>

                            <FieldGroup>
                                <Field>
                                    <Label htmlFor="name">Name</Label>
                                    <Input id="name" type="text" autoFocus tabIndex={1} autoComplete="name" name="name" placeholder="Full name" />
                                    {errors.name && <FieldError>{errors.name}</FieldError>}
                                </Field>
                                <Field>
                                    <Label htmlFor="email">Email address</Label>
                                    <Input id="email" type="email" tabIndex={2} autoComplete="email" name="email" placeholder="email@example.com" />
                                    {errors.email && <FieldError>{errors.email}</FieldError>}
                                </Field>
                                <Field>
                                    <Label htmlFor="password">Password</Label>
                                    <Input id="password" type="password" tabIndex={3} autoComplete="new-password" name="password" placeholder="Password" />
                                    {errors.password && <FieldError>{errors.password}</FieldError>}
                                </Field>
                                <Field>
                                    <Label htmlFor="password_confirmation">Confirm password</Label>
                                    <Input id="password_confirmation" type="password" tabIndex={4} autoComplete="new-password" name="password_confirmation" placeholder="Confirm password" />
                                    {errors.password_confirmation && <FieldError>{errors.password_confirmation}</FieldError>}
                                </Field>
                            </FieldGroup>
                            <FieldGroup>
                                <Field>
                                    <Button type="submit" className="mt-2 w-full" tabIndex={5} data-test="register-user-button">
                                        {processing && <Spinner />}
                                        Create account
                                    </Button>
                                </Field>
                            </FieldGroup>
                        </FieldSet>

                        <p className="mt-8 text-center">
                            Already have an account?{' '}
                            <Link href={login()} tabIndex={6}>
                                Log in
                            </Link>
                        </p>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}

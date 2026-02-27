import { Form, Head, Link } from '@inertiajs/react';
import React from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldDescription, FieldError, FieldGroup, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

export default function Login({ status, canResetPassword, canRegister }: { status?: string; canResetPassword: boolean; canRegister: boolean }) {
    return (
        <AuthLayout>
            <Head title="Log in" />

            <Form {...store.form()} resetOnSuccess={['password']} className="p-6 md:p-8">
                {({ processing, errors }) => (
                    <>
                        <FieldSet>
                            <FieldLegend>Welcome back</FieldLegend>
                            <FieldDescription>Login to your account</FieldDescription>

                            {status && <FieldGroup className="mb-4 text-center text-sm font-medium text-green-600">{status}</FieldGroup>}

                            <FieldGroup>
                                <Field>
                                    <FieldLabel htmlFor="email">Email</FieldLabel>
                                    <Input id="email" type="email" name="email" autoFocus tabIndex={1} autoComplete="email" placeholder="email@example.com" />
                                    {errors.email && <FieldError>{errors.email}</FieldError>}
                                </Field>

                                <Field>
                                    <div className="flex items-center justify-between">
                                        <FieldLabel htmlFor="password">Password</FieldLabel>
                                        {canResetPassword && <Link href={request()}>Forgot your password?</Link>}
                                    </div>
                                    <Input id="password" type="password" name="password" tabIndex={2} autoComplete="current-password" placeholder="Password" />
                                    {errors.password && <FieldError>{errors.password}</FieldError>}
                                </Field>

                                <Field orientation="horizontal">
                                    <Checkbox id="remember" name="remember" tabIndex={3} />
                                    <Label htmlFor="remember">Remember me</Label>
                                </Field>
                            </FieldGroup>
                            <FieldGroup>
                                <Field>
                                    <Button type="submit" disabled={processing}>
                                        {processing && <Spinner />} Login
                                    </Button>
                                </Field>
                            </FieldGroup>
                        </FieldSet>

                        {canRegister && (
                            <p className="mt-8 text-center">
                                Don&apos;t have an account? <Link href="/register">Sign up</Link>
                            </p>
                        )}
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}

import { Form, Head } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import React, { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Field, FieldDescription, FieldError, FieldGroup, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';
import AuthLayout from '@/layouts/auth-layout';
import { store } from '@/routes/two-factor/login';

export default function TwoFactorChallenge() {
    const [showRecoveryInput, setShowRecoveryInput] = useState<boolean>(false);
    const [code, setCode] = useState<string>('');

    const authConfigContent = useMemo<{ title: string; description: string; toggleText: string }>(() => {
        if (showRecoveryInput) {
            return {
                title: 'Recovery Code',
                description: 'Please confirm access to your account by entering one of your emergency recovery codes.',
                toggleText: 'login using an authentication code',
            };
        }

        return {
            title: 'Authentication Code',
            description: 'Enter the authentication code provided by your authenticator application.',
            toggleText: 'login using a recovery code',
        };
    }, [showRecoveryInput]);

    const toggleRecoveryMode = (clearErrors: () => void): void => {
        setShowRecoveryInput(!showRecoveryInput);
        clearErrors();
        setCode('');
    };

    return (
        <AuthLayout>
            <Head title="Two-Factor Authentication" />

            <Form {...store.form()} resetOnError resetOnSuccess={!showRecoveryInput} className="p-6 md:p-8">
                {({ errors, processing, clearErrors }) => (
                    <>
                        <FieldSet>
                            <FieldLegend>{authConfigContent.title}</FieldLegend>
                            <FieldDescription>{authConfigContent.description}</FieldDescription>

                            {showRecoveryInput ? (
                                <FieldGroup>
                                    <Field>
                                        <FieldLabel htmlFor="email">Recovery code</FieldLabel>
                                        <Input name="recovery_code" type="text" placeholder="Enter recovery code" autoFocus={showRecoveryInput} />
                                        {errors.recovery_code && <FieldError>{errors.recovery_code}</FieldError>}
                                    </Field>
                                </FieldGroup>
                            ) : (
                                <FieldGroup>
                                    <Field>
                                        <FieldLabel htmlFor="email">Type the code</FieldLabel>
                                        <InputOTP name="code" maxLength={OTP_MAX_LENGTH} value={code} onChange={(value) => setCode(value)} disabled={processing} pattern={REGEXP_ONLY_DIGITS}>
                                            <InputOTPGroup>
                                                {Array.from({ length: OTP_MAX_LENGTH }, (_, index) => (
                                                    <InputOTPSlot key={index} index={index} />
                                                ))}
                                            </InputOTPGroup>
                                        </InputOTP>
                                        {errors.code && <FieldError>{errors.code}</FieldError>}
                                    </Field>
                                </FieldGroup>
                            )}

                            <FieldGroup>
                                <Field>
                                    <Button type="submit" className="w-full" disabled={processing}>
                                        Continue
                                    </Button>
                                </Field>
                            </FieldGroup>
                        </FieldSet>

                        <p className="mt-8 text-center">
                            or you can{' '}
                            <button type="button" onClick={() => toggleRecoveryMode(clearErrors)}>
                                {authConfigContent.toggleText}
                            </button>
                        </p>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}

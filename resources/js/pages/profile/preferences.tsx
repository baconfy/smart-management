import { Form, router } from '@inertiajs/react';
import { Eye, ShieldBan, ShieldCheck, Trash2 } from 'lucide-react';
import { useRef, useState } from 'react';
import AppearanceTabs from '@/components/appearance-tabs';
import TwoFactorRecoveryCodes from '@/components/two-factor-recovery-codes';
import TwoFactorSetupModal from '@/components/two-factor-setup-modal';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { useTwoFactorAuth } from '@/hooks/use-two-factor-auth';
import ProfileLayout from '@/layouts/settings/profile-layout';
import { dashboard } from '@/routes';
import { destroy } from '@/routes/profile';
import { disable, enable } from '@/routes/two-factor';
import type { BreadcrumbItem } from '@/types';

type Props = {
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
    twoFactorPendingConfirmation?: boolean;
};

export default function PreferencesPage({ requiresConfirmation = false, twoFactorEnabled = false, twoFactorPendingConfirmation = false }: Props) {
    const passwordRef = useRef<HTMLInputElement>(null);
    const { qrCodeSvg, hasSetupData, manualSetupKey, clearSetupData, fetchSetupData, recoveryCodesList, fetchRecoveryCodes, errors } = useTwoFactorAuth();
    const [showSetupModal, setShowSetupModal] = useState<boolean>(false);
    const [showRecoveryCodes, setShowRecoveryCodes] = useState<boolean>(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Preferences', href: '#' },
    ];

    return (
        <ProfileLayout breadcrumbs={breadcrumbs}>
            <Card>
                <CardHeader className="border-b">
                    <CardTitle>Preferences</CardTitle>
                    <CardDescription>Personalize your account settings and preferences.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-6 md:px-8 md:py-4">
                    <div className="flex items-center justify-between">
                        <div className="max-w-3/4">
                            <h2 className="text-lg font-bold">Appearance</h2>
                            <p className="text-sm text-muted-foreground/65">Manage your account's visual settings.</p>
                        </div>

                        <AppearanceTabs />
                    </div>

                    <hr className="opacity-50" />

                    <div className="flex items-center justify-between">
                        <div className="max-w-2/3">
                            <div className="flex items-center gap-2">
                                <h2 className="text-lg font-bold">Two-Factor Authentication</h2>
                                <Badge variant={twoFactorEnabled ? 'default' : twoFactorPendingConfirmation ? 'secondary' : 'destructive'}>
                                    {twoFactorEnabled ? 'Enabled' : twoFactorPendingConfirmation ? 'Pending' : 'Disabled'}
                                </Badge>
                            </div>

                            <p className="text-sm text-muted-foreground/65">{twoFactorEnabled ? 'With 2FA enabled, you will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.' : 'When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.'}</p>
                        </div>

                        {twoFactorEnabled ? (
                            <div className="flex gap-2">
                                <Button variant="outline" onClick={() => setShowRecoveryCodes(true)}>
                                    <Eye /> View Recovery Codes
                                </Button>

                                <Form action={disable.url()} method="delete">
                                    {({ processing }) => (
                                        <Button variant="destructive" type="submit" disabled={processing}>
                                            <ShieldBan /> Disable 2FA
                                        </Button>
                                    )}
                                </Form>
                            </div>
                        ) : twoFactorPendingConfirmation || hasSetupData ? (
                            <Button onClick={() => setShowSetupModal(true)}>
                                <ShieldCheck /> Continue Setup
                            </Button>
                        ) : (
                            <Form action={enable.url()} method="post" onSuccess={() => setShowSetupModal(true)}>
                                {({ processing }) => (
                                    <Button type="submit" disabled={processing}>
                                        <ShieldCheck /> Enable 2FA
                                    </Button>
                                )}
                            </Form>
                        )}
                    </div>

                    <hr className="opacity-50" />

                    <div className="flex items-center justify-between">
                        <div className="max-w-2/3">
                            <h2 className="text-lg font-bold text-destructive">Danger Zone</h2>
                            <p className="text-sm text-muted-foreground/65">Once your account is deleted, all of its resources and data will be permanently removed.</p>
                        </div>

                        <AlertDialog>
                            <AlertDialogTrigger render={<Button variant="destructive" />}>
                                <Trash2 /> Delete Account
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                                    <AlertDialogDescription>This action cannot be undone. This will permanently delete your account and all associated data.</AlertDialogDescription>
                                </AlertDialogHeader>

                                <Field>
                                    <FieldLabel htmlFor="current_password">Password</FieldLabel>
                                    <Input ref={passwordRef} id="current_password" type="password" name="current_password" placeholder="Enter your password" />
                                </Field>

                                <AlertDialogFooter>
                                    <AlertDialogCancel variant="ghost">Cancel</AlertDialogCancel>
                                    <AlertDialogAction variant="destructive" onClick={() => router.delete(destroy().url, { data: { current_password: passwordRef.current?.value ?? '' } })}>
                                        <Trash2 />
                                        Delete Account
                                    </AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                    </div>
                </CardContent>
            </Card>

            <TwoFactorSetupModal isOpen={showSetupModal} onClose={() => setShowSetupModal(false)} requiresConfirmation={requiresConfirmation} twoFactorEnabled={twoFactorEnabled} qrCodeSvg={qrCodeSvg} manualSetupKey={manualSetupKey} clearSetupData={clearSetupData} fetchSetupData={fetchSetupData} errors={errors} />
            <TwoFactorRecoveryCodes isOpen={showRecoveryCodes} onClose={() => setShowRecoveryCodes(false)} recoveryCodesList={recoveryCodesList} fetchRecoveryCodes={fetchRecoveryCodes} errors={errors} />
        </ProfileLayout>
    );
}

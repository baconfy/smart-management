import { Form } from '@inertiajs/react';
import { LockKeyhole, RefreshCw } from 'lucide-react';
import { useEffect } from 'react';
import AlertError from '@/components/alert-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { regenerateRecoveryCodes } from '@/routes/two-factor';

type Props = {
    isOpen: boolean;
    onClose: () => void;
    recoveryCodesList: string[];
    fetchRecoveryCodes: () => Promise<void>;
    errors: string[];
};

export default function TwoFactorRecoveryCodes({ isOpen, onClose, recoveryCodesList, fetchRecoveryCodes, errors }: Props) {
    useEffect(() => {
        if (isOpen && !recoveryCodesList.length) {
            void fetchRecoveryCodes();
        }
    }, [isOpen, recoveryCodesList.length, fetchRecoveryCodes]);

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <div className="flex items-center gap-3">
                        <LockKeyhole className="size-4" aria-hidden="true" />
                        <DialogTitle>2FA Recovery Codes</DialogTitle>
                    </div>
                    <DialogDescription>Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.</DialogDescription>
                </DialogHeader>

                <div className="space-y-3">
                    {errors?.length ? (
                        <AlertError errors={errors} />
                    ) : (
                        <>
                            <div className="grid gap-1 rounded-lg bg-muted p-4 font-mono text-sm" role="list" aria-label="Recovery codes">
                                {recoveryCodesList.length ? (
                                    recoveryCodesList.map((code, index) => (
                                        <div key={index} role="listitem" className="select-text text-center">
                                            {code}
                                        </div>
                                    ))
                                ) : (
                                    <div className="space-y-2" aria-label="Loading recovery codes">
                                        {Array.from({ length: 8 }, (_, index) => (
                                            <div key={index} className="h-4 animate-pulse rounded bg-muted-foreground/20" aria-hidden="true" />
                                        ))}
                                    </div>
                                )}
                            </div>

                            <div className="text-xs text-muted-foreground select-none">
                                <p id="regenerate-warning">
                                    Each recovery code can be used once to access your account and will be removed after use. If you need more, click{' '}
                                    <span className="font-bold">Regenerate Codes</span> below.
                                </p>
                            </div>

                            <Form action={regenerateRecoveryCodes.url()} method="post" options={{ preserveScroll: true }} onSuccess={fetchRecoveryCodes}>
                                {({ processing }) => (
                                    <Button variant="secondary" type="submit" disabled={processing} className="w-full" aria-describedby="regenerate-warning">
                                        <RefreshCw /> Regenerate Codes
                                    </Button>
                                )}
                            </Form>
                        </>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}

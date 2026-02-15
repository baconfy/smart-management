import AppLayout from '@/layouts/app-layout';

export default function Dashboard() {
    return (
        <AppLayout>
            <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                <div className="aspect-video rounded-xl bg-muted" />
                <div className="aspect-video rounded-xl bg-muted" />
                <div className="aspect-video rounded-xl bg-muted" />
            </div>

            <div className="min-h-screen flex-1 rounded-xl bg-muted md:min-h-min" />
        </AppLayout>
    );
}

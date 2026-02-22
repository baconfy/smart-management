import '../css/app.css';
import './echo';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import { Toaster } from './components/ui/sonner';
import { TooltipProvider } from './components/ui/tooltip';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <StrictMode>
                <TooltipProvider>
                    <App {...props} />
                    <Toaster />
                </TooltipProvider>
            </StrictMode>,
        );
    },
    progress: {
        color: '#f59e0b',
    },
});

initializeTheme();

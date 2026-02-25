import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { type Plugin, defineConfig } from 'vite';

/**
 * Stub out the mermaid chunk that streamdown bundles internally.
 * Streamdown has a React.lazy(() => import('./mermaid-3ZIDBTTL.js')) that Vite
 * follows even though the component is never rendered (mermaid plugin removed).
 * This intercepts the resolved module and replaces it with a no-op export to
 * eliminate the ~1MB chunk.
 */
function stubStreamdownMermaid(): Plugin {
    return {
        name: 'stub-streamdown-mermaid',
        enforce: 'pre',
        load(id) {
            if (id.includes('streamdown') && id.includes('mermaid-3ZIDBTTL')) {
                return 'export const Mermaid = () => null;';
            }
        },
    };
}

export default defineConfig({
    plugins: [
        stubStreamdownMermaid(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
            detectTls: false,
            refresh: true,
        }),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
    esbuild: {
        jsx: 'automatic',
    },
});







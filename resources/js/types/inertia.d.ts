import type { SharedData } from './index';

declare module '@inertiajs/core' {
    type PageProps = SharedData;
}

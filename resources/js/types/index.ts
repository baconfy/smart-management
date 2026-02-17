export type * from './auth';
export type * from './models';
export type * from './navigation';
export type * from './ui';

import type { Auth } from './auth';
import type { Project } from './models';

export type SharedData = {
    name: string;
    auth: Auth;
    projects: Project[];
    [key: string]: unknown;
};

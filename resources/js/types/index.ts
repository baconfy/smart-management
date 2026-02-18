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

export type CursorPaginated<T> = {
    data: T[];
    path: string;
    per_page: number;
    next_cursor: string | null;
    next_page_url: string | null;
    prev_cursor: string | null;
    prev_page_url: string | null;
};

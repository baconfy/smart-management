import { usePage } from '@inertiajs/react';
import * as React from 'react';

import { ProjectsPanel } from '@/components/navigation/projects-panel';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Sidebar, SidebarContent, SidebarHeader, SidebarMenu, SidebarMenuItem } from '@/components/ui/sidebar';
import { useInitials } from '@/hooks/use-initials';

export function AppSidebar({ panel, ...props }: React.ComponentProps<typeof Sidebar> & { panel?: React.ReactNode }) {
    const { user } = usePage().props.auth;
    const getInitials = useInitials();

    return (
        <Sidebar variant="inset" {...props}>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem className="flex items-center gap-2">
                        <Avatar className="size-10">
                            <AvatarImage src={user.avatar} alt={user.name} />
                            <AvatarFallback className="rounded-full font-black">{getInitials(user.name)}</AvatarFallback>
                        </Avatar>

                        <div className="grid flex-1 text-left leading-relaxed">
                            <span className="truncate text-xs font-bold">{user.name}</span>
                            <span className="truncate text-xs text-primary tracking-tighter font-medium">{user.email}</span>
                        </div>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>{panel ?? <ProjectsPanel />}</SidebarContent>
        </Sidebar>
    );
}

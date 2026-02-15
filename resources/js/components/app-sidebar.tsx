import { Link, usePage } from '@inertiajs/react';
import { Command } from 'lucide-react';
import * as React from 'react';

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { edit } from '@/routes/profile';

export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
    const { user } = usePage().props.auth;

    return (
        <Sidebar variant="inset" {...props}>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton>
                            <Link href={dashboard()} className="flex items-center gap-1">
                                <Command className="size-4" />

                                <div className="grid flex-1 text-left">
                                    <span className="truncate text-base leading-none font-black tracking-tight">SmartManagement</span>
                                </div>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>{/*  CONTENT HERE  */}</SidebarContent>

            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg">
                            <Link href={edit()} className="flex items-center gap-2">
                                <Avatar className="size-10">
                                    <AvatarImage src={user.avatar} alt={user.name} />
                                    <AvatarFallback className="rounded-lg font-black">CN</AvatarFallback>
                                </Avatar>

                                <div className="grid flex-1 text-left text-sm leading-none">
                                    <span className="truncate font-bold">{user.name}</span>
                                    <span className="truncate text-xs text-muted-foreground">{user.email}</span>
                                </div>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarFooter>
        </Sidebar>
    );
}

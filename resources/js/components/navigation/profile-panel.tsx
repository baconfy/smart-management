import { Link, usePage } from '@inertiajs/react';
import React from 'react';
import { SidebarGroup, SidebarGroupContent, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { profile } from '@/routes';
import { password, preferences } from '@/routes/profile';

export function ProfilePanel() {
    const { url } = usePage();

    const items = [
        { label: 'General', href: profile().url },
        { label: 'Password', href: password().url },
        { label: 'Preferences', href: preferences().url },
    ];

    return (
        <SidebarGroup className="h-full items-center justify-center">
            <SidebarGroupContent>
                <SidebarMenu>
                    {items.map((item) => (
                        <SidebarMenuItem>
                            <SidebarMenuButton key={item.href} render={<Link className="flex items-center gap-2" href={item.href} />} isActive={url === item.href}>
                                <p className="line-clamp-1 truncate font-bold">{item.label}</p>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    ))}
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}

import { Link } from '@inertiajs/react';
import { Fragment } from 'react';
import { AppSidebar } from '@/components/app-sidebar';
import { Breadcrumb, BreadcrumbEllipsis, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import type { AppLayoutProps } from '@/types';

export default ({ children, breadcrumbs, sidebar }: AppLayoutProps) => {
    const lastItem = breadcrumbs?.[breadcrumbs.length - 1];

    return (
        <SidebarProvider>
            <AppSidebar panel={sidebar} />
            <SidebarInset>
                <header className="flex h-16 shrink-0 items-center gap-2">
                    <div className="flex items-center px-4">
                        <SidebarTrigger />
                        <Separator orientation="vertical" className="data-[orientation=vertical]:h-4" />

                        {breadcrumbs && breadcrumbs.length > 0 && (
                            <Breadcrumb>
                                <BreadcrumbList>
                                    {breadcrumbs.map((item, index) => {
                                        const isLast = index === breadcrumbs.length - 1;
                                        return (
                                            <Fragment key={index}>
                                                <BreadcrumbItem>{isLast ? <BreadcrumbPage>{item.title}</BreadcrumbPage> : <BreadcrumbLink render={<Link href={item.href} />}>{item.title}</BreadcrumbLink>}</BreadcrumbItem>
                                                {!isLast && <BreadcrumbSeparator />}
                                            </Fragment>
                                        );
                                    })}

                                    {lastItem?.menu && lastItem.menu.length > 0 && (
                                        <>
                                            <BreadcrumbSeparator />
                                            <BreadcrumbItem>
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger className="clickable flex items-center gap-1" aria-label="Actions">
                                                        <BreadcrumbEllipsis />
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="start" className="w-full">
                                                        {lastItem.menu.map((action) => (
                                                            <DropdownMenuItem key={action.title} onClick={action.onClick} variant={action.variant}>
                                                                {action.icon && <action.icon className="size-4" />}
                                                                <span className="text-nowrap">{action.title}</span>
                                                            </DropdownMenuItem>
                                                        ))}
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </BreadcrumbItem>
                                        </>
                                    )}
                                </BreadcrumbList>
                            </Breadcrumb>
                        )}
                    </div>
                </header>

                <main className="no-scrollbar flex min-h-0 flex-1 flex-col gap-4 overflow-auto p-4 pt-0">{children}</main>
            </SidebarInset>
        </SidebarProvider>
    );
};

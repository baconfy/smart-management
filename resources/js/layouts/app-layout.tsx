import { Link } from '@inertiajs/react';
import { Fragment } from 'react';
import { AppSidebar } from '@/components/app-sidebar';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import type { AppLayoutProps } from '@/types';

export default ({ children, breadcrumbs, sidebar }: AppLayoutProps) => (
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
                            </BreadcrumbList>
                        </Breadcrumb>
                    )}
                </div>
            </header>

            <div className="flex flex-1 flex-col gap-4 p-4 pt-0">{children}</div>
        </SidebarInset>
    </SidebarProvider>
);

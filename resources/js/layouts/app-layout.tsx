import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppLayoutProps } from '@/types';

export default ({ children }: AppLayoutProps) => <SidebarProvider>{children}</SidebarProvider>;

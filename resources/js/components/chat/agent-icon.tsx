import { BotIcon, Building2Icon, ClipboardListIcon, DatabaseIcon, LineChartIcon, WrenchIcon } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

const AGENT_ICONS: Record<string, LucideIcon> = {
    architect: Building2Icon,
    analyst: LineChartIcon,
    pm: ClipboardListIcon,
    dba: DatabaseIcon,
    technical: WrenchIcon,
    custom: BotIcon,
};

interface AgentIconProps {
    type: string;
    className?: string;
}

export function AgentIcon({ type, className }: AgentIconProps) {
    const Icon = AGENT_ICONS[type] ?? BotIcon;
    return <Icon className={className} />;
}

import { Link } from '@inertiajs/react';
import { BookOpenIcon, MessageCircleMore } from 'lucide-react';
import { ProjectNavPanel } from '@/components/navigation/project-nav-panel';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { show } from '@/routes/projects';
import { index as conversations } from '@/routes/projects/conversations';
import type { BreadcrumbItem } from '@/types';
import type { BusinessRule, Project } from '@/types/models';

export default function BusinessRulesIndex({ project, businessRules }: { project: Project; businessRules: BusinessRule[] }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: 'Business Rules', href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ProjectNavPanel project={project} />}>
            {businessRules.length === 0 ? (
                <Empty className="flex h-full items-center justify-center">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <BookOpenIcon />
                        </EmptyMedia>
                        <EmptyTitle>No business rules yet.</EmptyTitle>
                        <EmptyDescription>Chat with the Analyst agent to create new rules.</EmptyDescription>
                    </EmptyHeader>
                    <EmptyContent className="flex-row justify-center gap-2">
                        <Button size="lg" render={<Link href={conversations({ project: project.ulid })} />}>
                            <MessageCircleMore /> Start a conversation
                        </Button>
                    </EmptyContent>
                </Empty>
            ) : (
                <Accordion className="mx-auto max-w-4xl">
                    {businessRules.map((rule) => (
                        <AccordionItem key={rule.id} value={String(rule.id)}>
                            <AccordionTrigger className="flex items-center gap-2">
                                <div className="text-lg font-bold tracking-tighter">{rule.title}</div>
                                <Badge variant="secondary">{rule.category}</Badge>
                            </AccordionTrigger>
                            <AccordionContent>
                                <div className="prose prose-base prose-invert opacity-75">{rule.description}</div>
                            </AccordionContent>
                        </AccordionItem>
                    ))}
                </Accordion>
            )}
        </AppLayout>
    );
}

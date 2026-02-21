import { pointerWithin, DndContext, DragOverlay, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import type { DragEndEvent, DragOverEvent, DragStartEvent } from '@dnd-kit/core';
import { arrayMove } from '@dnd-kit/sortable';
import { router } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { GavelIcon, MessageCircleMore } from 'lucide-react';
import { useCallback, useMemo, useRef, useState } from 'react';
import { ProjectNavPanel } from '@/components/navigation/project-nav-panel';
import { Button } from '@/components/ui/button';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import AppLayout from '@/layouts/app-layout';
import { KanbanCard, KanbanColumn } from '@/pages/projects/tasks/partials';
import { dashboard } from '@/routes';
import { show } from '@/routes/projects';
import { index as conversations } from '@/routes/projects/conversations';
import { update as updateTask } from '@/routes/projects/tasks';
import type { BreadcrumbItem } from '@/types';
import type { Project, Task, TaskStatus } from '@/types/models';

type Props = {
    project: Project;
    statuses: TaskStatus[];
    tasks: Task[];
};

export default function TasksIndex({ project, statuses, tasks: initialTasks }: Props) {
    const [tasks, setTasks] = useState<Task[]>(initialTasks);
    const [activeTask, setActiveTask] = useState<Task | null>(null);
    const pendingStatusRef = useRef<Map<number, number>>(new Map());

    const sensors = useSensors(
        useSensor(PointerSensor, {
            activationConstraint: { distance: 8 },
        }),
    );

    const tasksByStatus = useMemo(() => {
        const map = new Map<number, Task[]>();

        for (const status of statuses) {
            map.set(status.id, []);
        }

        for (const task of tasks) {
            if (task.task_status_id && map.has(task.task_status_id)) {
                map.get(task.task_status_id)!.push(task);
            }
        }

        // Sort by sort_order within each column
        for (const [, columnTasks] of map) {
            columnTasks.sort((a, b) => a.sort_order - b.sort_order);
        }

        return map;
    }, [tasks, statuses]);

    const findStatusIdByTaskId = useCallback(
        (taskId: number): number | null => {
            for (const [statusId, columnTasks] of tasksByStatus) {
                if (columnTasks.some((t) => t.id === taskId)) {
                    return statusId;
                }
            }
            return null;
        },
        [tasksByStatus],
    );

    function handleDragStart(event: DragStartEvent) {
        const task = tasks.find((t) => t.id === event.active.id);
        setActiveTask(task ?? null);
        pendingStatusRef.current.clear();
    }

    function handleDragOver(event: DragOverEvent) {
        const { active, over } = event;
        if (!over) return;

        const activeId = active.id as number;
        const overId = over.id;

        const activeStatusId = findStatusIdByTaskId(activeId);

        let overStatusId: number | null = null;

        if (typeof overId === 'string' && String(overId).startsWith('column-')) {
            overStatusId = Number(String(overId).replace('column-', ''));
        } else {
            overStatusId = findStatusIdByTaskId(overId as number);
        }

        if (!activeStatusId || !overStatusId || activeStatusId === overStatusId) return;

        pendingStatusRef.current.set(activeId, overStatusId);

        setTasks((prev) => prev.map((t) => (t.id === activeId ? { ...t, task_status_id: overStatusId } : t)));
    }

    function handleDragEnd(event: DragEndEvent) {
        const { active, over } = event;
        setActiveTask(null);

        if (!over) {
            pendingStatusRef.current.clear();
            return;
        }

        const activeId = active.id as number;
        const task = tasks.find((t) => t.id === activeId);
        if (!task) return;

        const targetStatusId = pendingStatusRef.current.get(activeId) ?? task.task_status_id;
        pendingStatusRef.current.clear();

        // Reorder within a column
        if (typeof over.id === 'number' && activeId !== over.id) {
            setTasks((prev) => {
                const columnTasks = prev.filter((t) => t.task_status_id === targetStatusId).sort((a, b) => a.sort_order - b.sort_order);

                const oldIndex = columnTasks.findIndex((t) => t.id === activeId);
                const newIndex = columnTasks.findIndex((t) => t.id === over.id);

                if (oldIndex === -1 || newIndex === -1) return prev;

                const reordered = arrayMove(columnTasks, oldIndex, newIndex);
                const updatedIds = new Map(reordered.map((t, i) => [t.id, i]));

                return prev.map((t) => (updatedIds.has(t.id) ? { ...t, sort_order: updatedIds.get(t.id)! } : t));
            });
        }

        // Persist
        const sortOrder = tasks
            .filter((t) => t.task_status_id === targetStatusId)
            .sort((a, b) => a.sort_order - b.sort_order)
            .findIndex((t) => t.id === activeId);

        router.patch(
            updateTask({ project: project.ulid, task: task.ulid }).url,
            {
                task_status_id: targetStatusId,
                sort_order: Math.max(sortOrder, 0),
            },
            { preserveScroll: true, preserveState: true },
        );
    }

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: project.name, href: show(project.ulid).url },
        { title: 'Tasks', href: '#' },
    ];

    const hasTasks = tasks.length > 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs} sidebar={<ProjectNavPanel project={project} />}>
            {!hasTasks ? (
                <Empty className="flex h-full items-center justify-center">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <GavelIcon />
                        </EmptyMedia>
                        <EmptyTitle>No tasks yet.</EmptyTitle>
                        <EmptyDescription>Chat with the PM agent to create tasks.</EmptyDescription>
                    </EmptyHeader>
                    <EmptyContent className="flex-row justify-center gap-2">
                        <Button size="lg" render={<Link href={conversations(project.ulid)} />}>
                            <MessageCircleMore /> Start a conversation
                        </Button>
                    </EmptyContent>
                </Empty>
            ) : (
                <div className="flex h-full flex-col overflow-hidden">
                    <div className="flex flex-1 gap-6 overflow-scroll no-scrollbar">
                        <DndContext sensors={sensors} collisionDetection={pointerWithin} onDragStart={handleDragStart} onDragOver={handleDragOver} onDragEnd={handleDragEnd}>
                            {statuses.map((status) => (
                                <KanbanColumn key={status.id} status={status} tasks={tasksByStatus.get(status.id) ?? []} projectUlid={project.ulid} />
                            ))}
                            <DragOverlay>{activeTask ? <KanbanCard task={activeTask} projectUlid={project.ulid} isDragOverlay /> : null}</DragOverlay>
                        </DndContext>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}

import { useDroppable } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import type { Task, TaskStatus } from '@/types';
import { KanbanCard } from './kanban-card';

type Props = {
    status: TaskStatus;
    tasks: Task[];
    projectUlid: string;
};

export function KanbanColumn({ status, tasks, projectUlid }: Props) {
    const { setNodeRef, isOver } = useDroppable({
        id: `column-${status.id}`,
        data: { type: 'column', statusId: status.id },
    });

    return (
        <div className="flex h-full w-full max-w-72 shrink-0 flex-col gap-4 overflow-hidden">
            <div className="flex items-center justify-between gap-2 rounded-lg border-b border-border bg-card p-3 text-xs font-medium text-foreground uppercase">
                <div className="font-bold">{status.name}</div>
                <span style={{ backgroundColor: status.color }} className="flex size-6 items-center justify-center rounded text-xs">
                    {tasks.length}
                </span>
            </div>

            <div ref={setNodeRef} className={`no-scrollbar flex min-h-32 flex-1 flex-col gap-3 overflow-y-auto border border-dashed transition-colors ${isOver ? 'border-primary/50 bg-primary/5 p-4' : 'border-transparent'}`}>
                <SortableContext items={tasks.map((t) => t.id)} strategy={verticalListSortingStrategy}>
                    {tasks.map((task) => (
                        <KanbanCard key={task.id} task={task} projectUlid={projectUlid} />
                    ))}
                </SortableContext>
            </div>
        </div>
    );
}

import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { router } from '@inertiajs/react';
import { show as showTask } from '@/routes/projects/tasks';
import type { Task } from '@/types/models';

const PRIORITY_COLORS: Record<string, string> = {
    high: 'bg-red-500/20 text-red-300',
    medium: 'bg-amber-500/20 text-amber-300',
    low: 'bg-zinc-500/20 text-zinc-300',
};

type Props = {
    task: Task;
    projectUlid: string;
    isDragOverlay?: boolean;
};

export function KanbanCard({ task, projectUlid, isDragOverlay = false }: Props) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: task.id,
        data: { type: 'task', task },
    });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    function handleClick() {
        if (!isDragOverlay) {
            router.visit(showTask({ project: projectUlid, task: task.ulid }).url);
        }
    }

    return (
        <div ref={setNodeRef} style={style} onClick={handleClick} className={`cursor-grab rounded-lg border border-border bg-card p-4 shadow-sm active:cursor-grabbing ${isDragging ? 'opacity-40' : ''} ${isDragOverlay ? 'rotate-2 shadow-lg' : ''}`} {...attributes} {...listeners}>
            <div className="space-y-2.5">
                <div className="flex items-center gap-2">
                    <span className={`flex h-4 items-center justify-center rounded px-2 text-[0.5rem] leading-none font-bold uppercase ${PRIORITY_COLORS[task.priority] ?? ''}`}>{task.priority}</span>
                    {task.phase && <span className="flex h-4 items-center justify-center rounded px-2 text-[0.5rem] leading-none font-bold bg-muted text-muted-foreground uppercase">{task.phase}</span>}
                </div>
                <h1 className="text-base leading-relaxed font-bold tracking-tighter text-foreground">{task.title}</h1>
            </div>
        </div>
    );
}

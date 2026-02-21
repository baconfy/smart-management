import type { ImplementationNote, Task, TaskStatus } from '@/types';

type Props = {
    task: Task & { status: TaskStatus | null };
    subtasks: Task[];
    implementationNotes: ImplementationNote[];
};

const PRIORITY_COLORS: Record<string, string> = {
    high: 'bg-red-500/20 text-red-300',
    medium: 'bg-amber-500/20 text-amber-300',
    low: 'bg-zinc-500/20 text-zinc-300',
};

export function TaskDetails({ task, subtasks, implementationNotes }: Props) {
    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="space-y-6">
                <h1 className="text-2xl leading-relaxed font-bold tracking-tighter">{task.title}</h1>
                <div className="flex items-center gap-2">
                    {task.status && (
                        <span className="flex h-5 items-center justify-center rounded px-2.5 text-[0.5rem] leading-none font-bold uppercase" style={{ backgroundColor: task.status.color }}>
                            {task.status.name}
                        </span>
                    )}
                    <span className={`flex h-5 items-center justify-center rounded px-2 text-[0.5rem] leading-none font-bold uppercase ${PRIORITY_COLORS[task.priority] ?? ''}`}>{task.priority}</span>
                    {task.phase && <span className="bg-accent justify-center flex h-5 items-center rounded px-2 text-[0.5rem] leading-none font-bold uppercase">{task.phase}</span>}
                    {task.milestone && <span className="flex h-5 items-center justify-center rounded bg-accent px-2 text-[0.5rem] leading-none font-bold uppercase">{task.milestone}</span>}
                    {task.estimate && <span className="flex h-5 items-center justify-center rounded bg-accent px-2 text-[0.5rem] leading-none font-bold uppercase">{task.estimate}</span>}
                </div>
                <p className="font-mono text-base text-muted-foreground">{task.description}</p>
            </div>

            {/* Subtasks */}
            {subtasks.length > 0 && (
                <div className="space-y-3">
                    <h2 className="text-sm font-medium tracking-wider text-muted-foreground uppercase">Subtasks</h2>
                    <div className="space-y-2">
                        {subtasks.map((sub) => (
                            <div key={sub.id} className="flex items-center gap-3 rounded-lg border border-border bg-muted/50 px-4 py-3">
                                <span className="flex-1 text-sm font-medium">{sub.title}</span>
                                {sub.status && (
                                    <span className="rounded-full px-2 py-0.5 text-[10px] font-medium text-white" style={{ backgroundColor: sub.status.color }}>
                                        {sub.status.name}
                                    </span>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Implementation Notes */}
            {implementationNotes.length > 0 && (
                <div className="space-y-3">
                    <h2 className="text-sm font-medium tracking-wider text-muted-foreground uppercase">Implementation Notes</h2>
                    <div className="space-y-2">
                        {implementationNotes.map((note) => (
                            <div key={note.id} className="rounded-lg border border-border bg-muted/50 px-4 py-3">
                                <p className="text-sm font-medium">{note.title}</p>
                                <p className="mt-1 text-sm text-muted-foreground">{note.content}</p>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}

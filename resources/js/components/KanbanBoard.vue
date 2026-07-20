<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref, computed } from 'vue';
import TaskCard from '@/components/TaskCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { update as updateTask } from '@/routes/tasks';

type ChecklistItem = {
    id: number;
    task_id: number;
    item_text: string;
    is_completed: boolean;
    sort_order: number;
};

type TaskType = {
    id: number;
    title: string;
    description: string | null;
    status: 'todo' | 'in_progress' | 'blocked' | 'done';
    priority: 'low' | 'medium' | 'high' | 'urgent';
    due_date: string | null;
    tags: string[] | null;
    is_recurring: boolean;
    recurrence_rule: string | null;
    checklists: ChecklistItem[];
};

const props = defineProps<{
    tasks: TaskType[];
}>();

const emit = defineEmits<{
    (e: 'selectTask', task: TaskType): void;
    (
        e: 'createTask',
        status: 'todo' | 'in_progress' | 'blocked' | 'done',
    ): void;
}>();

// Define lanes with distinct, vibrant color schemes
const lanes = [
    {
        id: 'todo' as const,
        title: 'To Do',
        accentColor: 'border-t-slate-400 dark:border-t-slate-500',
        headerBg: 'bg-slate-50/80 dark:bg-slate-900/30',
        columnBg: 'bg-slate-50/30 dark:bg-slate-900/10',
        emptyBg:
            'bg-slate-100/50 dark:bg-slate-900/20 border-slate-200/70 dark:border-slate-700/50',
        addBtnHover:
            'hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-700 dark:hover:text-slate-300',
        badgeBg:
            'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
        dot: 'bg-slate-400',
    },
    {
        id: 'in_progress' as const,
        title: 'In Progress',
        accentColor: 'border-t-blue-500',
        headerBg: 'bg-blue-50/80 dark:bg-blue-950/20',
        columnBg: 'bg-blue-50/20 dark:bg-blue-950/5',
        emptyBg:
            'bg-blue-50/50 dark:bg-blue-950/20 border-blue-200/60 dark:border-blue-800/50',
        addBtnHover:
            'hover:bg-blue-50 dark:hover:bg-blue-950/30 hover:text-blue-700 dark:hover:text-blue-300',
        badgeBg:
            'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
        dot: 'bg-blue-500',
    },
    {
        id: 'blocked' as const,
        title: 'Blocked',
        accentColor: 'border-t-red-500',
        headerBg: 'bg-red-50/80 dark:bg-red-950/20',
        columnBg: 'bg-red-50/20 dark:bg-red-950/5',
        emptyBg:
            'bg-red-50/50 dark:bg-red-950/20 border-red-200/60 dark:border-red-800/50',
        addBtnHover:
            'hover:bg-red-50 dark:hover:bg-red-950/30 hover:text-red-700 dark:hover:text-red-300',
        badgeBg: 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
        dot: 'bg-red-500',
    },
    {
        id: 'done' as const,
        title: 'Done',
        accentColor: 'border-t-emerald-500',
        headerBg: 'bg-emerald-50/80 dark:bg-emerald-950/20',
        columnBg: 'bg-emerald-50/20 dark:bg-emerald-950/5',
        emptyBg:
            'bg-emerald-50/50 dark:bg-emerald-950/20 border-emerald-200/60 dark:border-emerald-800/50',
        addBtnHover:
            'hover:bg-emerald-50 dark:hover:bg-emerald-950/30 hover:text-emerald-700 dark:hover:text-emerald-300',
        badgeBg:
            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
        dot: 'bg-emerald-500',
    },
];

// Active dragging lane state for hover styles
const activeDragLane = ref<string | null>(null);

// Group tasks by status
const tasksByLane = computed(() => {
    const groups: Record<string, TaskType[]> = {
        todo: [],
        in_progress: [],
        blocked: [],
        done: [],
    };
    props.tasks.forEach((task) => {
        if (groups[task.status]) {
            groups[task.status].push(task);
        } else {
            groups.todo.push(task);
        }
    });

    return groups;
});

// Drag and drop event handlers
const handleDragEnter = (event: DragEvent, laneId: string) => {
    activeDragLane.value = laneId;
};

const handleDragLeave = (event: DragEvent, laneId: string) => {
    if (activeDragLane.value === laneId) {
        activeDragLane.value = null;
    }
};

const handleDrop = (
    event: DragEvent,
    targetStatus: 'todo' | 'in_progress' | 'blocked' | 'done',
) => {
    activeDragLane.value = null;

    if (!event.dataTransfer) {
return;
}

    const taskIdStr = event.dataTransfer.getData('text/plain');
    const taskId = parseInt(taskIdStr, 10);

    if (isNaN(taskId)) {
return;
}

    const task = props.tasks.find((t) => t.id === taskId);

    if (task && task.status !== targetStatus) {
        task.status = targetStatus;

        router.patch(
            updateTask(taskId).url,
            { status: targetStatus },
            { preserveScroll: true },
        );
    }
};
</script>

<template>
    <div class="grid h-full grid-cols-1 items-start gap-4 md:grid-cols-4">
        <div
            v-for="lane in lanes"
            :key="lane.id"
            @dragover.prevent
            @dragenter="handleDragEnter($event, lane.id)"
            @dragleave="handleDragLeave($event, lane.id)"
            @drop="handleDrop($event, lane.id)"
            class="flex max-h-[80vh] min-h-[500px] flex-col overflow-hidden rounded-xl border shadow-sm transition-all duration-200"
            :class="[
                lane.columnBg,
                activeDragLane === lane.id
                    ? 'scale-[1.01] shadow-md ring-2 ring-primary/30'
                    : 'border-border/60',
                lane.accentColor,
                'border-t-[3px]',
            ]"
        >
            <!-- Lane Header -->
            <div
                :class="['border-b border-border/40 px-4 py-3', lane.headerBg]"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span
                            :class="['size-2 shrink-0 rounded-full', lane.dot]"
                        />
                        <span class="text-sm font-semibold">{{
                            lane.title
                        }}</span>
                        <span
                            :class="[
                                'rounded-full px-2 py-0.5 text-[10px] font-bold tabular-nums',
                                lane.badgeBg,
                            ]"
                        >
                            {{ tasksByLane[lane.id].length }}
                        </span>
                    </div>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-7 rounded-md text-muted-foreground"
                        :class="lane.addBtnHover"
                        @click="emit('createTask', lane.id)"
                    >
                        <Plus class="size-4" />
                    </Button>
                </div>
            </div>

            <!-- Task Cards Area -->
            <div
                class="min-h-[400px] flex-1 space-y-2.5 overflow-y-auto px-3 py-3"
            >
                <TaskCard
                    v-for="task in tasksByLane[lane.id]"
                    :key="task.id"
                    :task="task"
                    @click="emit('selectTask', task)"
                />

                <!-- Empty State -->
                <div
                    v-if="tasksByLane[lane.id].length === 0"
                    :class="[
                        'flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed py-12 text-xs text-muted-foreground',
                        lane.emptyBg,
                    ]"
                >
                    <Plus class="size-5 opacity-40" />
                    <span class="font-medium">No tasks here</span>
                    <span class="text-muted-foreground/60"
                        >Drag a card here or click +</span
                    >
                </div>
            </div>

            <!-- Quick Add at bottom -->
            <div class="border-t border-border/30 px-3 pt-1.5 pb-3">
                <Button
                    variant="ghost"
                    class="h-8 w-full justify-start gap-1.5 rounded-md text-xs text-muted-foreground"
                    :class="lane.addBtnHover"
                    @click="emit('createTask', lane.id)"
                >
                    <Plus class="size-3.5" />
                    Add task
                </Button>
            </div>
        </div>
    </div>
</template>

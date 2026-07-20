<script setup lang="ts">
import {
    Clock,
    CheckSquare,
    AlertCircle,
    CheckCircle2,
    ArrowDown,
    ArrowRight,
    ArrowUp,
    Flame,
} from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';

type ChecklistItem = {
    id: number;
    task_id: number;
    item_text: string;
    is_completed: boolean;
    sort_order: number;
};

type ProjectType = {
    id: number;
    name: string;
    color: string | null;
};

type TaskType = {
    id: number;
    project_id: number | null;
    project_milestone_id: number | null;
    project?: ProjectType | null;
    project_milestone?: {
        id: number;
        title: string;
    } | null;
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
    task: TaskType;
}>();

const emit = defineEmits<{
    (e: 'click', task: TaskType): void;
}>();

// Priority configuration with icons and distinct colors
const priorityConfig = computed(() => {
    switch (props.task.priority) {
        case 'urgent':
            return {
                label: 'Urgent',
                class: 'bg-red-100 text-red-700 border-red-200 dark:bg-red-950/60 dark:text-red-300 dark:border-red-800',
                leftAccent: 'bg-red-500',
            };
        case 'high':
            return {
                label: 'High',
                class: 'bg-orange-100 text-orange-700 border-orange-200 dark:bg-orange-950/60 dark:text-orange-300 dark:border-orange-800',
                leftAccent: 'bg-orange-500',
            };
        case 'medium':
            return {
                label: 'Medium',
                class: 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800',
                leftAccent: 'bg-amber-400',
            };
        case 'low':
        default:
            return {
                label: 'Low',
                class: 'bg-sky-100 text-sky-700 border-sky-200 dark:bg-sky-950/60 dark:text-sky-300 dark:border-sky-800',
                leftAccent: 'bg-sky-400',
            };
    }
});

// Checklist stats
const checklistStats = computed(() => {
    const total = props.task.checklists.length;

    if (total === 0) {
return null;
}

    const completed = props.task.checklists.filter(
        (item) => item.is_completed,
    ).length;

    return {
        completed,
        total,
        ratio: `${completed}/${total}`,
        isFinished: completed === total,
        pct: Math.round((completed / total) * 100),
    };
});

// Due date computations
const dueDateInfo = computed(() => {
    if (!props.task.due_date || props.task.status === 'done') {
return null;
}

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const due = new Date(props.task.due_date);
    due.setHours(0, 0, 0, 0);

    const diffTime = due.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    const formatted = due.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });

    if (diffDays < 0) {
        return {
            text: `${Math.abs(diffDays)}d overdue`,
            class: 'text-red-600 bg-red-100 dark:bg-red-950/60 dark:text-red-300 border-red-200 dark:border-red-800',
            isCritical: true,
        };
    } else if (diffDays === 0) {
        return {
            text: 'Due today',
            class: 'text-amber-600 bg-amber-100 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            isWarning: true,
        };
    } else if (diffDays === 1) {
        return {
            text: 'Tomorrow',
            class: 'text-foreground/70 bg-muted border-border',
        };
    } else {
        return {
            text: formatted,
            class: 'text-muted-foreground bg-muted/50 border-border',
        };
    }
});

// Drag start handler
const handleDragStart = (event: DragEvent) => {
    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', props.task.id.toString());
    }
};
</script>

<template>
    <Card
        draggable="true"
        @dragstart="handleDragStart"
        @click="emit('click', task)"
        class="group relative cursor-grab overflow-hidden border-border/60 bg-card transition-all duration-200 select-none hover:shadow-md active:cursor-grabbing"
        :class="{ 'opacity-70': task.status === 'done' }"
    >
        <!-- Left priority accent strip -->
        <div
            class="absolute top-0 bottom-0 left-0 w-[3px] rounded-l-xl"
            :class="priorityConfig.leftAccent"
        />

        <CardContent class="space-y-2.5 p-3.5 pl-4">
            <!-- Title row -->
            <div class="flex items-start justify-between gap-2">
                <h4
                    class="line-clamp-2 flex-1 text-sm leading-snug font-semibold transition-colors duration-150 group-hover:text-primary"
                    :class="{
                        'font-normal text-muted-foreground line-through group-hover:text-muted-foreground':
                            task.status === 'done',
                    }"
                >
                    {{ task.title }}
                </h4>
                <CheckCircle2
                    v-if="task.status === 'done'"
                    class="mt-0.5 size-4 shrink-0 text-emerald-500"
                />
            </div>

            <!-- Description -->
            <p
                v-if="task.description"
                class="line-clamp-2 text-xs leading-relaxed text-muted-foreground"
            >
                {{ task.description }}
            </p>

            <!-- Project & Tags -->
            <div
                v-if="task.project || (task.tags && task.tags.length)"
                class="flex flex-wrap gap-1"
            >
                <Badge
                    v-if="task.project"
                    variant="outline"
                    class="h-4 border px-1.5 py-0 text-[9px] font-semibold"
                    :style="{
                        color: task.project.color ?? '#5C59D9',
                        borderColor: (task.project.color ?? '#5C59D9') + '30',
                        backgroundColor:
                            (task.project.color ?? '#5C59D9') + '10',
                    }"
                >
                    {{ task.project.name }}
                    <span
                        v-if="task.project_milestone"
                        class="ml-1 font-normal opacity-75"
                    >
                        · {{ task.project_milestone.title }}
                    </span>
                </Badge>
                <Badge
                    v-for="tag in task.tags"
                    :key="tag"
                    variant="secondary"
                    class="h-4 border-0 bg-muted/60 px-1.5 py-0 text-[10px] font-medium text-muted-foreground"
                >
                    #{{ tag }}
                </Badge>
            </div>

            <!-- Checklist progress bar -->
            <div v-if="checklistStats" class="space-y-1">
                <div class="flex items-center justify-between">
                    <span
                        class="flex items-center gap-1 text-[10px] text-muted-foreground"
                    >
                        <CheckSquare
                            class="size-3"
                            :class="
                                checklistStats.isFinished
                                    ? 'text-emerald-500'
                                    : 'text-muted-foreground'
                            "
                        />
                        {{ checklistStats.ratio }}
                    </span>
                    <span
                        class="text-[10px] font-medium"
                        :class="
                            checklistStats.isFinished
                                ? 'text-emerald-500'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ checklistStats.pct }}%
                    </span>
                </div>
                <div class="h-1 w-full overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full transition-all duration-300"
                        :class="
                            checklistStats.isFinished
                                ? 'bg-emerald-500'
                                : 'bg-blue-500'
                        "
                        :style="{ width: checklistStats.pct + '%' }"
                    />
                </div>
            </div>

            <!-- Footer: Priority + Due date -->
            <div
                class="flex items-center justify-between border-t border-border/40 pt-1"
            >
                <Badge
                    variant="outline"
                    :class="[
                        'h-5 border px-1.5 py-0 text-[10px] font-semibold',
                        priorityConfig.class,
                    ]"
                >
                    <Flame
                        v-if="task.priority === 'urgent'"
                        class="mr-0.5 size-2.5"
                    />
                    <ArrowUp
                        v-else-if="task.priority === 'high'"
                        class="mr-0.5 size-2.5"
                    />
                    <ArrowRight
                        v-else-if="task.priority === 'medium'"
                        class="mr-0.5 size-2.5"
                    />
                    <ArrowDown v-else class="mr-0.5 size-2.5" />
                    {{ priorityConfig.label }}
                </Badge>

                <Badge
                    v-if="dueDateInfo"
                    variant="outline"
                    :class="[
                        'flex h-5 items-center gap-1 border px-1.5 py-0 text-[10px] font-medium',
                        dueDateInfo.class,
                    ]"
                >
                    <AlertCircle
                        v-if="dueDateInfo.isCritical"
                        class="size-2.5"
                    />
                    <Clock v-else class="size-2.5" />
                    {{ dueDateInfo.text }}
                </Badge>
            </div>
        </CardContent>
    </Card>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Clock,
    CheckSquare,
    ArrowUpRight,
    AlertCircle,
    CalendarDays,
} from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { update as updateTask } from '@/routes/tasks';

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
    tasks: TaskType[];
}>();

const emit = defineEmits<{
    (e: 'selectTask', task: TaskType): void;
}>();

// Helper to determine status styling
const getStatusConfig = (status: TaskType['status']) => {
    switch (status) {
        case 'done':
            return {
                label: 'Done',
                class: 'bg-green-500/10 text-green-500 border-green-500/20 dark:bg-green-500/20',
            };
        case 'blocked':
            return {
                label: 'Blocked',
                class: 'bg-red-500/10 text-red-500 border-red-500/20 dark:bg-red-500/20',
            };
        case 'in_progress':
            return {
                label: 'In Progress',
                class: 'bg-blue-500/10 text-blue-500 border-blue-500/20 dark:bg-blue-500/20',
            };
        case 'todo':
        default:
            return {
                label: 'To Do',
                class: 'bg-slate-500/10 text-slate-500 border-slate-500/20 dark:bg-slate-500/20',
            };
    }
};

// Helper to determine priority styling
const getPriorityConfig = (priority: TaskType['priority']) => {
    switch (priority) {
        case 'urgent':
            return {
                label: 'Urgent',
                class: 'bg-red-500/10 text-red-500 border-red-500/20',
            };
        case 'high':
            return {
                label: 'High',
                class: 'bg-orange-500/10 text-orange-500 border-orange-500/20',
            };
        case 'medium':
            return {
                label: 'Medium',
                class: 'bg-amber-500/10 text-amber-500 border-amber-500/20',
            };
        case 'low':
        default:
            return {
                label: 'Low',
                class: 'bg-blue-500/10 text-blue-500 border-blue-500/20',
            };
    }
};

// Check date overdue status
const isOverdue = (dateStr: string | null, status: TaskType['status']) => {
    if (!dateStr || status === 'done') {
return false;
}

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const due = new Date(dateStr);
    due.setHours(0, 0, 0, 0);

    return due.getTime() < today.getTime();
};

const formatDate = (dateStr: string | null) => {
    if (!dateStr) {
return '-';
}

    const due = new Date(dateStr);

    return due.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

// Inline check completion toggle
const toggleCompletion = (task: TaskType) => {
    const newStatus: TaskType['status'] =
        task.status === 'done' ? 'todo' : 'done';

    // Update local state optimistically
    task.status = newStatus;

    router.patch(
        updateTask(task.id).url,
        { status: newStatus },
        { preserveScroll: true },
    );
};
</script>

<template>
    <div class="overflow-hidden rounded-xl border bg-card">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead>
                    <tr
                        class="border-b bg-muted/40 font-semibold text-muted-foreground"
                    >
                        <th class="w-12 p-3.5 text-center">Status</th>
                        <th class="min-w-[250px] p-3.5">Task Title</th>
                        <th class="w-28 p-3.5">Status</th>
                        <th class="w-24 p-3.5">Priority</th>
                        <th class="w-32 p-3.5">Due Date</th>
                        <th class="max-w-[200px] p-3.5">Tags</th>
                        <th class="w-16 p-3.5 text-center">Sub-tasks</th>
                        <th class="w-12 p-3.5 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="task in tasks"
                        :key="task.id"
                        class="group transition-all duration-150 hover:bg-muted/30"
                    >
                        <!-- Inline checkbox -->
                        <td class="p-3.5 text-center">
                            <Checkbox
                                :checked="task.status === 'done'"
                                @update:checked="toggleCompletion(task)"
                                class="size-4 cursor-pointer"
                            />
                        </td>

                        <!-- Title and Description -->
                        <td class="p-3.5">
                            <div class="flex flex-col gap-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="cursor-pointer text-sm leading-tight font-semibold hover:underline"
                                        :class="{
                                            'font-normal text-muted-foreground line-through':
                                                task.status === 'done',
                                        }"
                                        @click="emit('selectTask', task)"
                                    >
                                        {{ task.title }}
                                    </span>
                                    <Badge
                                        v-if="task.project"
                                        variant="outline"
                                        class="shrink-0 px-1.5 py-0.5 text-[9px] leading-none"
                                        :style="{
                                            color:
                                                task.project.color ?? '#5C59D9',
                                            borderColor:
                                                (task.project.color ??
                                                    '#5C59D9') + '30',
                                            backgroundColor:
                                                (task.project.color ??
                                                    '#5C59D9') + '10',
                                        }"
                                    >
                                        {{ task.project.name }}
                                        <span
                                            v-if="task.project_milestone"
                                            class="font-normal opacity-75"
                                        >
                                            · {{ task.project_milestone.title }}
                                        </span>
                                    </Badge>
                                </div>
                                <span
                                    v-if="task.description"
                                    class="line-clamp-1 text-[11px] leading-relaxed text-muted-foreground"
                                >
                                    {{ task.description }}
                                </span>
                            </div>
                        </td>

                        <!-- Status badge -->
                        <td class="p-3.5">
                            <Badge
                                variant="outline"
                                :class="[
                                    'border px-2 py-0.5 text-[10px] font-semibold',
                                    getStatusConfig(task.status).class,
                                ]"
                            >
                                {{ getStatusConfig(task.status).label }}
                            </Badge>
                        </td>

                        <!-- Priority badge -->
                        <td class="p-3.5">
                            <Badge
                                variant="outline"
                                :class="[
                                    'border px-2 py-0.5 text-[10px] font-semibold',
                                    getPriorityConfig(task.priority).class,
                                ]"
                            >
                                {{ getPriorityConfig(task.priority).label }}
                            </Badge>
                        </td>

                        <!-- Due date -->
                        <td class="p-3.5">
                            <div
                                class="flex items-center gap-1.5 font-medium"
                                :class="[
                                    isOverdue(task.due_date, task.status)
                                        ? 'text-red-500'
                                        : task.status === 'done'
                                          ? 'text-green-500'
                                          : 'text-muted-foreground',
                                ]"
                            >
                                <AlertCircle
                                    v-if="isOverdue(task.due_date, task.status)"
                                    class="size-3.5"
                                />
                                <CalendarDays v-else class="size-3.5" />
                                <span>{{ formatDate(task.due_date) }}</span>
                            </div>
                        </td>

                        <!-- Tags -->
                        <td class="p-3.5">
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="tag in task.tags"
                                    :key="tag"
                                    variant="secondary"
                                    class="border-border px-1.5 py-0 text-[9px] text-muted-foreground"
                                >
                                    {{ tag }}
                                </Badge>
                                <span
                                    v-if="!task.tags || !task.tags.length"
                                    class="text-muted-foreground/60"
                                    >-</span
                                >
                            </div>
                        </td>

                        <!-- Checklist counts -->
                        <td class="p-3.5 text-center">
                            <div
                                v-if="task.checklists.length"
                                class="inline-flex items-center gap-1 font-medium"
                                :class="[
                                    task.checklists.filter(
                                        (i) => i.is_completed,
                                    ).length === task.checklists.length
                                        ? 'text-green-500'
                                        : 'text-muted-foreground',
                                ]"
                            >
                                <CheckSquare class="size-3.5" />
                                <span>
                                    {{
                                        task.checklists.filter(
                                            (i) => i.is_completed,
                                        ).length
                                    }}/{{ task.checklists.length }}
                                </span>
                            </div>
                            <span v-else class="text-muted-foreground/40"
                                >-</span
                            >
                        </td>

                        <!-- Action trigger -->
                        <td class="p-3.5 text-center">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-7 opacity-0 transition-all duration-150 group-hover:opacity-100"
                                @click="emit('selectTask', task)"
                            >
                                <ArrowUpRight
                                    class="size-4 text-muted-foreground hover:text-foreground"
                                />
                            </Button>
                        </td>
                    </tr>

                    <tr v-if="tasks.length === 0">
                        <td
                            colspan="8"
                            class="p-8 text-center text-muted-foreground"
                        >
                            No tasks found matching current filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

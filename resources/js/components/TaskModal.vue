<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { useConfirm } from '@/composables/useConfirm';

const { confirm } = useConfirm();
import {
    Plus,
    Trash2,
    X,
    Clock,
    Tag,
    CheckSquare,
    Loader2,
    ListChecks,
    CircleDot,
    CircleAlert,
    CircleCheck,
    Circle,
    Flame,
    ArrowUp,
    ArrowRight,
    ArrowDown,
    CalendarDays,
    FolderKanban,
    CheckCircle2,
} from '@lucide/vue';
import {
    store as storeTask,
    update as updateTask,
    destroy as destroyTask,
} from '@/routes/tasks';
import {
    store as storeChecklist,
    update as updateChecklist,
    destroy as destroyChecklist,
} from '@/routes/tasks/checklists';

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
    milestones?: {
        id: number;
        title: string;
        description: string | null;
        due_date: string;
        status: 'pending' | 'completed';
    }[];
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

const props = withDefaults(
    defineProps<{
        task: TaskType | null;
        isOpen: boolean;
        defaultStatus?: 'todo' | 'in_progress' | 'blocked' | 'done';
        projects?: ProjectType[];
    }>(),
    {
        projects: () => [],
    },
);

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const newTag = ref('');
const newChecklistItemText = ref('');
const isSubmittingChecklist = ref(false);

const form = useForm({
    title: '',
    description: '',
    status: 'todo' as 'todo' | 'in_progress' | 'blocked' | 'done',
    priority: 'medium' as 'low' | 'medium' | 'high' | 'urgent',
    due_date: '',
    tags: [] as string[],
    project_id: null as number | null | string,
    project_milestone_id: null as number | null | string,
});

const availableMilestones = computed(() => {
    if (!form.project_id || form.project_id === 'none') {
        return [];
    }

    const selectedProj = props.projects.find(
        (p) => String(p.id) === String(form.project_id),
    );

    return selectedProj?.milestones || [];
});

watch(
    () => form.project_id,
    (newProjId, oldProjId) => {
        if (oldProjId !== undefined) {
            form.project_milestone_id = 'none';
        }
    },
);

watch(
    () => props.task,
    (newTask) => {
        if (newTask) {
            form.title = newTask.title;
            form.description = newTask.description || '';
            form.status = newTask.status;
            form.priority = newTask.priority;

            if (newTask.due_date) {
                const date = new Date(newTask.due_date);
                form.due_date = date.toISOString().split('T')[0];
            } else {
                form.due_date = '';
            }

            form.tags = Array.isArray(newTask.tags) ? [...newTask.tags] : [];
            form.project_id = newTask.project_id
                ? String(newTask.project_id)
                : 'none';
            form.project_milestone_id = newTask.project_milestone_id
                ? String(newTask.project_milestone_id)
                : 'none';
        } else {
            form.title = '';
            form.description = '';
            form.status = props.defaultStatus || 'todo';
            form.priority = 'medium';
            form.due_date = '';
            form.tags = [];
            form.project_id = 'none';
            form.project_milestone_id = 'none';
        }
    },
    { immediate: true },
);

const handleOpenChange = (open: boolean) => {
    if (!open) {
        emit('close');
    }
};

const addTag = () => {
    const trimmed = newTag.value.trim().toLowerCase();

    if (trimmed && !form.tags.includes(trimmed)) {
        form.tags.push(trimmed);
    }

    newTag.value = '';
};

const removeTag = (index: number) => {
    form.tags.splice(index, 1);
};

const saveTask = () => {
    const originalProjectId = form.project_id;
    const originalMilestoneId = form.project_milestone_id;

    if (form.project_id === 'none') {
        form.project_id = null;
    }

    if (form.project_milestone_id === 'none') {
        form.project_milestone_id = null;
    }

    if (props.task) {
        form.patch(updateTask(props.task.id).url, {
            preserveScroll: true,
            onSuccess: () => {
                emit('close');
            },
            onError: () => {
                form.project_id = originalProjectId;
                form.project_milestone_id = originalMilestoneId;
            },
        });
    } else {
        form.post(storeTask().url, {
            preserveScroll: true,
            onSuccess: () => {
                emit('close');
            },
            onError: () => {
                form.project_id = originalProjectId;
                form.project_milestone_id = originalMilestoneId;
            },
        });
    }
};

const deleteTask = async () => {
    if (!props.task) {
return;
}

    const isConfirmed = await confirm({
        title: 'Delete Task',
        message: 'Are you sure you want to delete this task?',
        confirmText: 'Delete',
        cancelText: 'Cancel',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(destroyTask(props.task.id).url, {
            preserveScroll: true,
            onSuccess: () => {
                emit('close');
            },
        });
    }
};

// Checklist Management
const addChecklistItem = () => {
    if (!props.task || !newChecklistItemText.value.trim()) {
return;
}

    isSubmittingChecklist.value = true;
    router.post(
        storeChecklist(props.task.id).url,
        { item_text: newChecklistItemText.value.trim() },
        {
            preserveScroll: true,
            onSuccess: () => {
                newChecklistItemText.value = '';
                isSubmittingChecklist.value = false;
            },
            onError: () => {
                isSubmittingChecklist.value = false;
            },
        },
    );
};

const toggleChecklistItem = (item: ChecklistItem) => {
    router.patch(
        updateChecklist(item.id).url,
        { is_completed: !item.is_completed },
        { preserveScroll: true },
    );
};

const removeChecklistItem = (item: ChecklistItem) => {
    router.delete(destroyChecklist(item.id).url, { preserveScroll: true });
};

// Status config
const statusConfig = computed(() => {
    const configs = {
        todo: {
            label: 'To Do',
            class: 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
            dot: 'bg-slate-400',
        },
        in_progress: {
            label: 'In Progress',
            class: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800',
            dot: 'bg-blue-500',
        },
        blocked: {
            label: 'Blocked',
            class: 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/60 dark:text-red-300 dark:border-red-800',
            dot: 'bg-red-500',
        },
        done: {
            label: 'Done',
            class: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800',
            dot: 'bg-emerald-500',
        },
    };

    return configs[form.status] || configs.todo;
});

const priorityConfig = computed(() => {
    const configs = {
        low: {
            label: 'Low',
            class: 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/60 dark:text-sky-300 dark:border-sky-800',
        },
        medium: {
            label: 'Medium',
            class: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800',
        },
        high: {
            label: 'High',
            class: 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-950/60 dark:text-orange-300 dark:border-orange-800',
        },
        urgent: {
            label: 'Urgent',
            class: 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/60 dark:text-red-300 dark:border-red-800',
        },
    };

    return configs[form.priority] || configs.medium;
});

// Checklist progress
const checklistProgress = computed(() => {
    if (!props.task?.checklists.length) {
return null;
}

    const done = props.task.checklists.filter((i) => i.is_completed).length;
    const total = props.task.checklists.length;

    return { done, total, pct: Math.round((done / total) * 100) };
});
</script>

<template>
    <Dialog :open="isOpen" @update:open="handleOpenChange">
        <DialogContent
            class="max-h-[92vh] gap-0 overflow-hidden rounded-2xl border-0 p-0 shadow-2xl sm:max-w-[780px]"
        >
            <!-- Modal Header with accent bar -->
            <div
                class="relative border-b px-7 pt-6 pb-5"
                :class="{
                    'bg-gradient-to-r from-slate-50 to-white dark:from-slate-900 dark:to-slate-900/80':
                        form.status === 'todo',
                    'bg-gradient-to-r from-blue-50 to-white dark:from-blue-950/30 dark:to-slate-900/80':
                        form.status === 'in_progress',
                    'bg-gradient-to-r from-red-50 to-white dark:from-red-950/30 dark:to-slate-900/80':
                        form.status === 'blocked',
                    'bg-gradient-to-r from-emerald-50 to-white dark:from-emerald-950/30 dark:to-slate-900/80':
                        form.status === 'done',
                }"
            >
                <!-- Thin top accent line by status -->
                <div
                    class="absolute top-0 right-0 left-0 h-1 rounded-t-2xl"
                    :class="{
                        'bg-slate-400': form.status === 'todo',
                        'bg-blue-500': form.status === 'in_progress',
                        'bg-red-500': form.status === 'blocked',
                        'bg-emerald-500': form.status === 'done',
                    }"
                />
                <DialogHeader>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-xl"
                                :class="{
                                    'bg-slate-100 dark:bg-slate-800':
                                        form.status === 'todo',
                                    'bg-blue-100 dark:bg-blue-900/50':
                                        form.status === 'in_progress',
                                    'bg-red-100 dark:bg-red-900/50':
                                        form.status === 'blocked',
                                    'bg-emerald-100 dark:bg-emerald-900/50':
                                        form.status === 'done',
                                }"
                            >
                                <Circle
                                    v-if="form.status === 'todo'"
                                    class="size-4 text-slate-500 dark:text-slate-400"
                                />
                                <CircleDot
                                    v-else-if="form.status === 'in_progress'"
                                    class="size-4 text-blue-500"
                                />
                                <CircleAlert
                                    v-else-if="form.status === 'blocked'"
                                    class="size-4 text-red-500"
                                />
                                <CircleCheck
                                    v-else
                                    class="size-4 text-emerald-500"
                                />
                            </div>
                            <div>
                                <DialogTitle
                                    class="text-base leading-tight font-semibold text-foreground"
                                >
                                    {{ task ? 'Edit Task' : 'Create New Task' }}
                                </DialogTitle>
                                <p
                                    v-if="task"
                                    class="mt-0.5 text-xs text-muted-foreground"
                                >
                                    Task #{{ task.id }}
                                </p>
                            </div>
                        </div>
                        <!-- Current status badge -->
                        <div class="mr-8 flex items-center gap-2">
                            <Badge
                                variant="outline"
                                :class="[
                                    'flex items-center gap-1.5 border px-2.5 py-1 text-xs font-medium',
                                    statusConfig.class,
                                ]"
                            >
                                <span
                                    :class="[
                                        'size-1.5 shrink-0 rounded-full',
                                        statusConfig.dot,
                                    ]"
                                />
                                {{ statusConfig.label }}
                            </Badge>
                            <Badge
                                variant="outline"
                                :class="[
                                    'border px-2.5 py-1 text-xs font-medium',
                                    priorityConfig.class,
                                ]"
                            >
                                {{ priorityConfig.label }}
                            </Badge>
                        </div>
                    </div>
                </DialogHeader>
            </div>

            <!-- Modal Body: scrollable -->
            <div class="max-h-[calc(92vh-160px)] overflow-y-auto">
                <form @submit.prevent="saveTask">
                    <div class="grid grid-cols-1 gap-0 md:grid-cols-5">
                        <!-- Left Pane: Main Details (3/5) -->
                        <div class="space-y-5 px-7 py-5 md:col-span-3">
                            <!-- Title -->
                            <div class="space-y-2">
                                <Label
                                    for="title"
                                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                    >Title</Label
                                >
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    placeholder="What needs to be done?"
                                    required
                                    class="h-11 border-muted-foreground/20 text-base font-medium focus:border-primary/50"
                                />
                                <span
                                    v-if="form.errors.title"
                                    class="flex items-center gap-1 text-xs text-destructive"
                                >
                                    <CircleAlert class="size-3" />{{
                                        form.errors.title
                                    }}
                                </span>
                            </div>

                            <!-- Description -->
                            <div class="space-y-2">
                                <Label
                                    for="description"
                                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                    >Description</Label
                                >
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Add more details, context, or acceptance criteria..."
                                    rows="4"
                                    class="flex w-full resize-none rounded-lg border border-muted-foreground/20 bg-transparent px-3 py-2.5 text-sm shadow-sm transition-colors placeholder:text-muted-foreground/60 focus-visible:border-primary/50 focus-visible:ring-1 focus-visible:ring-primary/50 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                ></textarea>
                                <span
                                    v-if="form.errors.description"
                                    class="text-xs text-destructive"
                                    >{{ form.errors.description }}</span
                                >
                            </div>

                            <!-- Checklist (Edit mode only) -->
                            <div v-if="task" class="space-y-3">
                                <Separator />
                                <div class="flex items-center justify-between">
                                    <Label
                                        class="flex items-center gap-1.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                    >
                                        <ListChecks class="size-3.5" />
                                        Sub-tasks
                                    </Label>
                                    <div
                                        v-if="checklistProgress"
                                        class="flex items-center gap-2"
                                    >
                                        <div
                                            class="h-1.5 w-24 overflow-hidden rounded-full bg-muted"
                                        >
                                            <div
                                                class="h-full rounded-full transition-all duration-300"
                                                :class="
                                                    checklistProgress.pct ===
                                                    100
                                                        ? 'bg-emerald-500'
                                                        : 'bg-blue-500'
                                                "
                                                :style="{
                                                    width:
                                                        checklistProgress.pct +
                                                        '%',
                                                }"
                                            />
                                        </div>
                                        <span
                                            class="text-xs font-medium text-muted-foreground"
                                            >{{ checklistProgress.done }}/{{
                                                checklistProgress.total
                                            }}</span
                                        >
                                    </div>
                                </div>

                                <!-- Add sub-task -->
                                <div class="flex gap-2">
                                    <Input
                                        v-model="newChecklistItemText"
                                        placeholder="Add a sub-task..."
                                        @keydown.enter.prevent="
                                            addChecklistItem
                                        "
                                        class="h-9 text-sm"
                                        :disabled="isSubmittingChecklist"
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="h-9 shrink-0 px-3"
                                        @click="addChecklistItem"
                                        :disabled="
                                            isSubmittingChecklist ||
                                            !newChecklistItemText.trim()
                                        "
                                    >
                                        <Plus
                                            v-if="!isSubmittingChecklist"
                                            class="size-4"
                                        />
                                        <Loader2
                                            v-else
                                            class="size-4 animate-spin"
                                        />
                                    </Button>
                                </div>

                                <!-- Checklist items -->
                                <div
                                    class="max-h-[200px] space-y-1 overflow-y-auto rounded-lg border bg-muted/20 p-2"
                                >
                                    <div
                                        v-for="item in task.checklists"
                                        :key="item.id"
                                        class="group flex items-center justify-between rounded-md p-2 transition-colors hover:bg-background"
                                    >
                                        <div
                                            class="flex min-w-0 items-center gap-2.5"
                                        >
                                            <Checkbox
                                                :id="`check-${item.id}`"
                                                :checked="item.is_completed"
                                                @update:checked="
                                                    toggleChecklistItem(item)
                                                "
                                                class="shrink-0"
                                            />
                                            <label
                                                :for="`check-${item.id}`"
                                                class="cursor-pointer truncate text-sm leading-none transition-all duration-200"
                                                :class="
                                                    item.is_completed
                                                        ? 'text-muted-foreground line-through'
                                                        : 'text-foreground'
                                                "
                                            >
                                                {{ item.item_text }}
                                            </label>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="size-7 shrink-0 opacity-0 transition-all duration-150 group-hover:opacity-100 hover:bg-destructive/10 hover:text-destructive"
                                            @click="removeChecklistItem(item)"
                                        >
                                            <Trash2 class="size-3.5" />
                                        </Button>
                                    </div>
                                    <div
                                        v-if="!task.checklists.length"
                                        class="py-5 text-center text-xs text-muted-foreground"
                                    >
                                        No sub-tasks yet. Add one above.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Pane: Attributes (2/5) -->
                        <div
                            class="space-y-5 border-l bg-muted/20 px-6 py-5 md:col-span-2 dark:bg-muted/5"
                        >
                            <!-- Project -->
                            <div class="space-y-2">
                                <Label
                                    class="flex items-center gap-1.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >
                                    <FolderKanban class="size-3.5" />
                                    Project
                                </Label>
                                <Select v-model="form.project_id">
                                    <SelectTrigger
                                        class="w-full border-muted-foreground/20 bg-background text-xs"
                                    >
                                        <SelectValue placeholder="No project" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            <span
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="size-2 shrink-0 rounded-full bg-slate-300 dark:bg-slate-700"
                                                />
                                                No Project
                                            </span>
                                        </SelectItem>
                                        <SelectItem
                                            v-for="project in projects"
                                            :key="project.id"
                                            :value="String(project.id)"
                                        >
                                            <span
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="size-2 shrink-0 rounded-full"
                                                    :style="{
                                                        backgroundColor:
                                                            project.color ??
                                                            '#5C59D9',
                                                    }"
                                                />
                                                {{ project.name }}
                                            </span>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <span
                                    v-if="form.errors.project_id"
                                    class="text-xs text-destructive"
                                    >{{ form.errors.project_id }}</span
                                >
                            </div>

                            <!-- Project Milestone -->
                            <div
                                v-if="
                                    form.project_id &&
                                    form.project_id !== 'none'
                                "
                                class="space-y-2"
                            >
                                <Label
                                    class="flex items-center gap-1.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >
                                    <CheckCircle2 class="size-3.5" />
                                    Project Milestone
                                </Label>
                                <Select
                                    v-model="form.project_milestone_id"
                                    :disabled="availableMilestones.length === 0"
                                >
                                    <SelectTrigger
                                        class="w-full border-muted-foreground/20 bg-background text-xs"
                                    >
                                        <SelectValue
                                            :placeholder="
                                                availableMilestones.length === 0
                                                    ? 'No milestones found'
                                                    : 'Select milestone'
                                            "
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            <span
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="size-2 shrink-0 rounded-full bg-slate-300 dark:bg-slate-700"
                                                />
                                                No Milestone
                                            </span>
                                        </SelectItem>
                                        <SelectItem
                                            v-for="milestone in availableMilestones"
                                            :key="milestone.id"
                                            :value="String(milestone.id)"
                                        >
                                            <span
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="size-2 shrink-0 rounded-full bg-slate-400"
                                                />
                                                {{ milestone.title }}
                                            </span>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <span
                                    v-if="form.errors.project_milestone_id"
                                    class="text-xs text-destructive"
                                    >{{
                                        form.errors.project_milestone_id
                                    }}</span
                                >
                            </div>

                            <!-- Status -->
                            <div class="space-y-2">
                                <Label
                                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                    >Status</Label
                                >
                                <Select v-model="form.status">
                                    <SelectTrigger
                                        class="w-full border-muted-foreground/20 bg-background"
                                    >
                                        <SelectValue
                                            placeholder="Select status"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="todo">
                                            <span
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="size-2 shrink-0 rounded-full bg-slate-400"
                                                />
                                                To Do
                                            </span>
                                        </SelectItem>
                                        <SelectItem value="in_progress">
                                            <span
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="size-2 shrink-0 rounded-full bg-blue-500"
                                                />
                                                In Progress
                                            </span>
                                        </SelectItem>
                                        <SelectItem value="blocked">
                                            <span
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="size-2 shrink-0 rounded-full bg-red-500"
                                                />
                                                Blocked
                                            </span>
                                        </SelectItem>
                                        <SelectItem value="done">
                                            <span
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="size-2 shrink-0 rounded-full bg-emerald-500"
                                                />
                                                Done
                                            </span>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <span
                                    v-if="form.errors.status"
                                    class="text-xs text-destructive"
                                    >{{ form.errors.status }}</span
                                >
                            </div>

                            <!-- Priority -->
                            <div class="space-y-2">
                                <Label
                                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                    >Priority</Label
                                >
                                <Select v-model="form.priority">
                                    <SelectTrigger
                                        class="w-full border-muted-foreground/20 bg-background"
                                    >
                                        <SelectValue
                                            placeholder="Select priority"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="low">
                                            <span
                                                class="flex items-center gap-2"
                                            >
                                                <ArrowDown
                                                    class="size-3.5 text-sky-500"
                                                />
                                                Low
                                            </span>
                                        </SelectItem>
                                        <SelectItem value="medium">
                                            <span
                                                class="flex items-center gap-2"
                                            >
                                                <ArrowRight
                                                    class="size-3.5 text-amber-500"
                                                />
                                                Medium
                                            </span>
                                        </SelectItem>
                                        <SelectItem value="high">
                                            <span
                                                class="flex items-center gap-2"
                                            >
                                                <ArrowUp
                                                    class="size-3.5 text-orange-500"
                                                />
                                                High
                                            </span>
                                        </SelectItem>
                                        <SelectItem value="urgent">
                                            <span
                                                class="flex items-center gap-2"
                                            >
                                                <Flame
                                                    class="size-3.5 text-red-500"
                                                />
                                                Urgent
                                            </span>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <span
                                    v-if="form.errors.priority"
                                    class="text-xs text-destructive"
                                    >{{ form.errors.priority }}</span
                                >
                            </div>

                            <!-- Due Date -->
                            <div class="space-y-2">
                                <Label
                                    for="due_date"
                                    class="flex items-center gap-1.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >
                                    <CalendarDays class="size-3.5" />
                                    Due Date
                                </Label>
                                <Input
                                    id="due_date"
                                    type="date"
                                    v-model="form.due_date"
                                    class="w-full border-muted-foreground/20 bg-background"
                                />
                                <span
                                    v-if="form.errors.due_date"
                                    class="text-xs text-destructive"
                                    >{{ form.errors.due_date }}</span
                                >
                            </div>

                            <!-- Tags -->
                            <div class="space-y-2">
                                <Label
                                    class="flex items-center gap-1.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >
                                    <Tag class="size-3.5" />
                                    Tags
                                </Label>
                                <div class="flex gap-2">
                                    <Input
                                        v-model="newTag"
                                        placeholder="Add tag..."
                                        @keydown.enter.prevent="addTag"
                                        class="h-9 flex-1 border-muted-foreground/20 bg-background"
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        class="h-9 w-9 shrink-0"
                                        @click="addTag"
                                        :disabled="!newTag.trim()"
                                    >
                                        <Plus class="size-4" />
                                    </Button>
                                </div>
                                <div
                                    class="flex min-h-[28px] flex-wrap gap-1.5"
                                >
                                    <Badge
                                        v-for="(tag, idx) in form.tags"
                                        :key="tag"
                                        variant="secondary"
                                        class="flex items-center gap-1 px-2 py-0.5 text-xs font-medium"
                                    >
                                        {{ tag }}
                                        <button
                                            type="button"
                                            @click="removeTag(idx)"
                                            class="ml-0.5 shrink-0 text-muted-foreground transition-colors hover:text-foreground"
                                        >
                                            <X class="size-3" />
                                        </button>
                                    </Badge>
                                    <span
                                        v-if="!form.tags.length"
                                        class="text-xs text-muted-foreground/60 italic"
                                        >No tags added yet.</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div
                        class="flex items-center justify-between border-t bg-muted/10 px-7 py-4"
                    >
                        <div>
                            <Button
                                v-if="task"
                                type="button"
                                variant="ghost"
                                class="h-9 gap-1.5 text-sm text-destructive hover:bg-destructive/10 hover:text-destructive"
                                @click="deleteTask"
                            >
                                <Trash2 class="size-4" />
                                Delete Task
                            </Button>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-9"
                                @click="emit('close')"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                class="h-9 min-w-[120px] gap-1.5"
                                :disabled="
                                    form.processing || !form.title.trim()
                                "
                            >
                                <Loader2
                                    v-if="form.processing"
                                    class="size-4 animate-spin"
                                />
                                <CheckSquare v-else class="size-4" />
                                {{ task ? 'Save Changes' : 'Create Task' }}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </DialogContent>
    </Dialog>
</template>

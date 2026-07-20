<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    FolderKanban,
    GitBranch,
    Globe,
    Server,
    Copy,
    ExternalLink,
    Plus,
    CheckCircle2,
    Circle,
    Trash2,
    Pencil,
    BarChart3,
    CheckSquare,
    Clock,
    AlertTriangle,
    Calendar,
} from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useConfirm } from '@/composables/useConfirm';
import { index as projectsIndex, edit as projectEdit } from '@/routes/projects';

const { confirm } = useConfirm();


type Milestone = {
    id: number;
    title: string;
    description: string | null;
    due_date: string;
    completed_at: string | null;
    status: 'pending' | 'completed';
    sort_order: number;
};

type Task = {
    id: number;
    title: string;
    description: string | null;
    status: 'todo' | 'in_progress' | 'blocked' | 'done';
    priority: 'low' | 'medium' | 'high' | 'urgent';
    due_date: string | null;
    tags: string[] | null;
    project_milestone_id?: number | null;
    project_milestone?: {
        id: number;
        title: string;
    } | null;
};

type Project = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    color: string | null;
    repository_url: string | null;
    production_url: string | null;
    staging_url: string | null;
    server_ip: string | null;
    status: 'active' | 'pipeline' | 'archived';
    milestones: Milestone[];
    tasks: Task[];
    created_at: string;
};

type TaskStats = {
    total: number;
    done: number;
    in_progress: number;
    overdue: number;
};

const props = defineProps<{
    project: Project;
    taskStats: TaskStats;
    progressPercent: number;
}>();

import { setLayoutProps } from '@inertiajs/vue3';

setLayoutProps({
    breadcrumbs: [
        { title: 'Projects', href: projectsIndex().url },
        { title: props.project.name, href: '#' },
    ],
});

const statusConfig = {
    active: {
        label: 'Active',
        classes:
            'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-800',
        dot: 'bg-blue-500',
    },
    pipeline: {
        label: 'Pipeline',
        classes:
            'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800/50 dark:text-slate-300 dark:border-slate-700',
        dot: 'bg-slate-400',
    },
    archived: {
        label: 'Archived',
        classes:
            'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-950/50 dark:text-orange-300 dark:border-orange-800',
        dot: 'bg-orange-400',
    },
};

// Milestone add form
const showAddMilestone = ref(false);
const milestoneForm = useForm({
    title: '',
    description: '',
    due_date: '',
});

function submitMilestone() {
    milestoneForm.post(`/app/projects/${props.project.id}/milestones`, {
        onSuccess: () => {
            milestoneForm.reset();
            showAddMilestone.value = false;
            toast.success('Milestone added successfully');
        },
    });
}

function toggleMilestone(milestone: Milestone) {
    const newStatus =
        milestone.status === 'completed' ? 'pending' : 'completed';
    router.patch(
        `/app/projects/milestones/${milestone.id}`,
        { status: newStatus },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`Milestone updated to ${newStatus}`);
            },
        },
    );
}

async function deleteMilestone(milestone: Milestone) {
    const isConfirmed = await confirm({
        title: 'Delete Milestone',
        message: `Delete milestone "${milestone.title}"?`,
        confirmText: 'Delete',
        cancelText: 'Cancel',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(`/app/projects/milestones/${milestone.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Milestone deleted');
            },
        });
    }
}

// Inline Task Form
const showAddTask = ref(false);
const taskForm = useForm({
    title: '',
    project_id: props.project.id,
    status: 'todo' as const,
    priority: 'medium' as const,
});

function submitTask() {
    if (!taskForm.title.trim()) {
        return;
    }

    taskForm.post('/app/tasks', {
        preserveScroll: true,
        onSuccess: () => {
            taskForm.reset('title');
            showAddTask.value = false;
            toast.success('Task created successfully');
        },
    });
}

function toggleTask(task: Task) {
    const newStatus = task.status === 'done' ? 'todo' : 'done';
    // Optimistically update
    task.status = newStatus;
    router.patch(
        `/app/tasks/${task.id}`,
        { status: newStatus },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Task status updated');
            },
        },
    );
}

async function deleteTask(task: Task) {
    const isConfirmed = await confirm({
        title: 'Delete Task',
        message: `Delete task "${task.title}"?`,
        confirmText: 'Delete',
        cancelText: 'Cancel',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(`/app/tasks/${task.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Task deleted');
            },
        });
    }
}

const getTaskStatusConfig = (status: Task['status']) => {
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

const getTaskPriorityConfig = (priority: Task['priority']) => {
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

async function deleteProject() {
    const isConfirmed = await confirm({
        title: 'Delete Project',
        message: `Delete project "${props.project.name}"? This will permanently delete all related milestones.`,
        confirmText: 'Delete',
        cancelText: 'Cancel',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(`/app/projects/${props.project.id}`);
    }
}

async function copyToClipboard(text: string, label: string) {
    await navigator.clipboard.writeText(text);
    toast.success(`${label} copied!`);
}

function getInitials(name: string): string {
    return name
        .split(/[\s-_]+/)
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase();
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function isOverdue(dateStr: string): boolean {
    return new Date(dateStr) < new Date();
}
</script>

<template>
    <Head :title="project.name" />

    <div class="mx-auto max-w-7xl space-y-6 px-6 py-6">
        <!-- Header -->
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <Link :href="projectsIndex().url">
                    <Button variant="ghost" size="icon" class="size-9">
                        <ArrowLeft class="size-4" />
                    </Button>
                </Link>
                <!-- Avatar -->
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-sm font-bold text-white shadow-sm"
                    :style="{ backgroundColor: project.color ?? '#5C59D9' }"
                >
                    {{ getInitials(project.name) }}
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl font-bold tracking-tight">
                            {{ project.name }}
                        </h2>
                        <span
                            class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                            :class="statusConfig[project.status].classes"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full"
                                :class="statusConfig[project.status].dot"
                            />
                            {{ statusConfig[project.status].label }}
                        </span>
                    </div>
                    <p
                        v-if="project.description"
                        class="mt-0.5 line-clamp-1 text-sm text-muted-foreground"
                    >
                        {{ project.description }}
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <Link :href="projectEdit(project.id).url">
                    <Button
                        variant="outline"
                        size="sm"
                        class="h-8 gap-1.5 text-xs"
                    >
                        <Pencil class="size-3.5" />
                        Edit
                    </Button>
                </Link>
                <Button
                    variant="destructive"
                    size="sm"
                    class="h-8 gap-1.5 text-xs"
                    @click="deleteProject"
                >
                    <Trash2 class="size-3.5" />
                    Delete
                </Button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left Column: Milestones -->
            <div class="space-y-4 lg:col-span-2">
                <!-- Milestones Section -->
                <div class="overflow-hidden rounded-xl border bg-card">
                    <div
                        class="flex items-center justify-between border-b px-5 py-4"
                    >
                        <h3
                            class="flex items-center gap-2 text-sm font-semibold"
                        >
                            <CheckCircle2
                                class="size-4 text-muted-foreground"
                            />
                            Milestones
                            <span
                                class="text-xs font-normal text-muted-foreground"
                                >({{ project.milestones.length }})</span
                            >
                        </h3>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="h-7 gap-1.5 text-xs"
                            @click="showAddMilestone = !showAddMilestone"
                        >
                            <Plus class="size-3.5" />
                            Add
                        </Button>
                    </div>

                    <!-- Add Milestone Form -->
                    <div
                        v-if="showAddMilestone"
                        class="border-b bg-muted/30 px-5 py-4"
                    >
                        <form
                            class="space-y-3"
                            @submit.prevent="submitMilestone"
                        >
                            <div class="space-y-1.5">
                                <Label class="text-xs font-medium"
                                    >Milestone Title *</Label
                                >
                                <Input
                                    v-model="milestoneForm.title"
                                    placeholder="e.g., Phase 1 — Core Backend"
                                    class="h-8 text-xs"
                                    autofocus
                                />
                                <InputError
                                    :message="milestoneForm.errors.title"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs font-medium"
                                    >Due Date *</Label
                                >
                                <Input
                                    v-model="milestoneForm.due_date"
                                    type="date"
                                    class="h-8 text-xs"
                                />
                                <InputError
                                    :message="milestoneForm.errors.due_date"
                                />
                            </div>
                            <div class="flex items-center justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 text-xs"
                                    @click="showAddMilestone = false"
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    size="sm"
                                    class="h-7 text-xs"
                                    :disabled="milestoneForm.processing"
                                >
                                    Add Milestone
                                </Button>
                            </div>
                        </form>
                    </div>

                    <!-- Timeline -->
                    <div v-if="project.milestones.length > 0" class="px-5 py-4">
                        <div class="relative">
                            <!-- Vertical line -->
                            <div
                                class="absolute top-4 bottom-4 left-3.5 w-px bg-border"
                            />

                            <div class="space-y-4">
                                <div
                                    v-for="(
                                        milestone, idx
                                    ) in project.milestones"
                                    :key="milestone.id"
                                    class="group relative flex gap-4"
                                >
                                    <!-- Status indicator -->
                                    <div class="relative z-10 shrink-0">
                                        <button
                                            class="flex h-7 w-7 items-center justify-center rounded-full border-2 transition-all duration-200"
                                            :class="
                                                milestone.status === 'completed'
                                                    ? 'border-emerald-500 bg-emerald-500 text-white hover:bg-emerald-600'
                                                    : isOverdue(
                                                            milestone.due_date,
                                                        )
                                                      ? 'border-orange-400 bg-orange-50 text-orange-500 hover:bg-orange-100 dark:bg-orange-950/30'
                                                      : 'border-muted-foreground/30 bg-background text-muted-foreground hover:border-primary hover:text-primary'
                                            "
                                            @click="toggleMilestone(milestone)"
                                            :title="
                                                milestone.status === 'completed'
                                                    ? 'Mark as pending'
                                                    : 'Mark as completed'
                                            "
                                        >
                                            <CheckCircle2
                                                v-if="
                                                    milestone.status ===
                                                    'completed'
                                                "
                                                class="size-4"
                                            />
                                            <AlertTriangle
                                                v-else-if="
                                                    isOverdue(
                                                        milestone.due_date,
                                                    )
                                                "
                                                class="size-3.5"
                                            />
                                            <Circle v-else class="size-3.5" />
                                        </button>
                                    </div>

                                    <!-- Content -->
                                    <div class="min-w-0 flex-1 pb-2">
                                        <div
                                            class="flex items-start justify-between gap-2"
                                        >
                                            <div class="min-w-0 flex-1">
                                                <p
                                                    class="text-sm font-medium"
                                                    :class="
                                                        milestone.status ===
                                                        'completed'
                                                            ? 'text-muted-foreground line-through'
                                                            : ''
                                                    "
                                                >
                                                    {{ milestone.title }}
                                                </p>
                                                <div
                                                    class="mt-0.5 flex items-center gap-2"
                                                >
                                                    <Calendar
                                                        class="size-3 text-muted-foreground"
                                                    />
                                                    <span
                                                        class="text-xs"
                                                        :class="
                                                            milestone.status ===
                                                                'pending' &&
                                                            isOverdue(
                                                                milestone.due_date,
                                                            )
                                                                ? 'font-medium text-orange-600 dark:text-orange-400'
                                                                : 'text-muted-foreground'
                                                        "
                                                    >
                                                        {{
                                                            milestone.status ===
                                                            'completed'
                                                                ? 'Completed'
                                                                : isOverdue(
                                                                        milestone.due_date,
                                                                    )
                                                                  ? 'Overdue · '
                                                                  : ''
                                                        }}
                                                        {{
                                                            formatDate(
                                                                milestone.due_date,
                                                            )
                                                        }}
                                                    </span>
                                                </div>
                                            </div>
                                            <button
                                                class="rounded p-1 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100 hover:text-destructive"
                                                @click="
                                                    deleteMilestone(milestone)
                                                "
                                            >
                                                <Trash2 class="size-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty milestones -->
                    <div
                        v-else-if="!showAddMilestone"
                        class="px-5 py-8 text-center"
                    >
                        <CheckCircle2
                            class="mx-auto mb-2 size-8 text-muted-foreground/30"
                        />
                        <p class="text-sm text-muted-foreground">
                            No milestones yet
                        </p>
                        <p class="text-xs text-muted-foreground/70">
                            Click "Add" to track delivery markers
                        </p>
                    </div>
                </div>

                <!-- Related Tasks Section -->
                <div
                    class="overflow-hidden rounded-xl border bg-card shadow-sm"
                >
                    <div
                        class="flex items-center justify-between border-b px-5 py-4"
                    >
                        <h3
                            class="flex items-center gap-2 text-sm font-semibold"
                        >
                            <CheckSquare class="size-4 text-muted-foreground" />
                            Related Tasks
                            <span
                                class="text-xs font-normal text-muted-foreground"
                                >({{ project.tasks.length }})</span
                            >
                        </h3>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="h-7 gap-1.5 text-xs"
                            @click="showAddTask = !showAddTask"
                        >
                            <Plus class="size-3.5" />
                            Quick Add
                        </Button>
                    </div>

                    <!-- Quick Add Task Form -->
                    <div
                        v-if="showAddTask"
                        class="border-b bg-muted/30 px-5 py-4"
                    >
                        <form
                            class="flex items-center gap-3"
                            @submit.prevent="submitTask"
                        >
                            <div class="min-w-0 flex-1">
                                <Input
                                    v-model="taskForm.title"
                                    placeholder="Enter task title and press Enter..."
                                    class="h-8 bg-background text-xs"
                                    autofocus
                                />
                                <InputError :message="taskForm.errors.title" />
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="h-8 text-xs"
                                    @click="showAddTask = false"
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    size="sm"
                                    class="h-8 text-xs"
                                    :disabled="taskForm.processing"
                                >
                                    Add Task
                                </Button>
                            </div>
                        </form>
                    </div>

                    <!-- Tasks List -->
                    <div v-if="project.tasks.length > 0" class="divide-y">
                        <div
                            v-for="task in project.tasks"
                            :key="task.id"
                            class="group/task flex items-center justify-between gap-4 px-5 py-3.5 transition-colors hover:bg-muted/20"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <Checkbox
                                    :checked="task.status === 'done'"
                                    @update:checked="toggleTask(task)"
                                    class="size-4 shrink-0 cursor-pointer"
                                />
                                <div class="min-w-0">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <p
                                            class="text-xs leading-none font-medium text-foreground"
                                            :class="
                                                task.status === 'done'
                                                    ? 'text-muted-foreground line-through'
                                                    : ''
                                            "
                                        >
                                            {{ task.title }}
                                        </p>
                                        <Badge
                                            v-if="task.project_milestone"
                                            variant="secondary"
                                            class="h-4 px-1.5 py-0.5 text-[9px] font-medium"
                                        >
                                            {{ task.project_milestone.title }}
                                        </Badge>
                                    </div>
                                    <p
                                        v-if="task.description"
                                        class="mt-1 truncate text-[11px] text-muted-foreground"
                                    >
                                        {{ task.description }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <!-- Due Date -->
                                <span
                                    v-if="task.due_date"
                                    class="flex items-center gap-1 rounded px-2 py-0.5 text-[10px] font-medium"
                                    :class="
                                        task.status !== 'done' &&
                                        isOverdue(task.due_date)
                                            ? 'bg-red-500/10 text-red-600 dark:text-red-400'
                                            : 'bg-muted/40 text-muted-foreground'
                                    "
                                >
                                    <Clock class="size-2.5" />
                                    {{ formatDate(task.due_date) }}
                                </span>

                                <!-- Priority -->
                                <span
                                    class="rounded border px-2 py-0.5 text-[10px] font-medium capitalize"
                                    :class="
                                        getTaskPriorityConfig(task.priority)
                                            .class
                                    "
                                >
                                    {{
                                        getTaskPriorityConfig(task.priority)
                                            .label
                                    }}
                                </span>

                                <!-- Status -->
                                <span
                                    class="rounded border px-2 py-0.5 text-[10px] font-medium capitalize"
                                    :class="
                                        getTaskStatusConfig(task.status).class
                                    "
                                >
                                    {{ getTaskStatusConfig(task.status).label }}
                                </span>

                                <!-- Delete -->
                                <button
                                    class="rounded p-1 text-muted-foreground opacity-0 transition-opacity group-hover/task:opacity-100 hover:text-destructive"
                                    @click="deleteTask(task)"
                                    title="Delete task"
                                >
                                    <Trash2 class="size-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty Tasks state -->
                    <div
                        v-else-if="!showAddTask"
                        class="px-5 py-10 text-center"
                    >
                        <CheckSquare
                            class="mx-auto mb-2 size-8 text-muted-foreground/30"
                        />
                        <p class="text-xs text-muted-foreground">
                            No tasks linked to this project yet
                        </p>
                        <p class="text-[11px] text-muted-foreground/70">
                            Click "Quick Add" to write a quick checklist task
                        </p>
                    </div>
                </div>
            </div>

            <!-- Right Column: Info + Task Stats -->
            <div class="space-y-4">
                <!-- Task Progress -->
                <div class="space-y-4 rounded-xl border bg-card p-5">
                    <h3 class="flex items-center gap-2 text-sm font-semibold">
                        <BarChart3 class="size-4 text-muted-foreground" />
                        Task Progress
                    </h3>

                    <div v-if="taskStats.total > 0" class="space-y-3">
                        <div class="flex items-end justify-between">
                            <span
                                class="text-3xl font-bold"
                                :style="{ color: project.color ?? '#5C59D9' }"
                            >
                                {{ progressPercent }}%
                            </span>
                            <span class="text-xs text-muted-foreground">
                                {{ taskStats.done }}/{{ taskStats.total }} done
                            </span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full transition-all duration-700"
                                :style="{
                                    width: `${progressPercent}%`,
                                    backgroundColor: project.color ?? '#5C59D9',
                                }"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div
                                class="rounded-lg bg-muted/40 p-2.5 text-center"
                            >
                                <p class="text-lg font-bold text-foreground">
                                    {{ taskStats.in_progress }}
                                </p>
                                <p
                                    class="flex items-center justify-center gap-1 text-[10px] text-muted-foreground"
                                >
                                    <Clock class="size-2.5" /> In Progress
                                </p>
                            </div>
                            <div
                                class="rounded-lg bg-muted/40 p-2.5 text-center"
                            >
                                <p
                                    class="text-lg font-bold"
                                    :class="
                                        taskStats.overdue > 0
                                            ? 'text-orange-600 dark:text-orange-400'
                                            : 'text-foreground'
                                    "
                                >
                                    {{ taskStats.overdue }}
                                </p>
                                <p
                                    class="flex items-center justify-center gap-1 text-[10px] text-muted-foreground"
                                >
                                    <AlertTriangle class="size-2.5" /> Overdue
                                </p>
                            </div>
                        </div>

                        <Link href="/tasks">
                            <Button
                                variant="outline"
                                size="sm"
                                class="h-8 w-full gap-1.5 text-xs"
                            >
                                <CheckSquare class="size-3.5" />
                                View Tasks
                            </Button>
                        </Link>
                    </div>

                    <div v-else class="py-2 text-center">
                        <CheckSquare
                            class="mx-auto mb-1 size-6 text-muted-foreground/30"
                        />
                        <p class="text-xs text-muted-foreground">
                            No tasks linked yet
                        </p>
                    </div>
                </div>

                <!-- Environments -->
                <div class="space-y-3 rounded-xl border bg-card p-5">
                    <h3 class="flex items-center gap-2 text-sm font-semibold">
                        <Globe class="size-4 text-muted-foreground" />
                        Environments
                    </h3>

                    <div class="space-y-2">
                        <template v-if="project.production_url">
                            <div
                                class="group/url flex items-center gap-2 rounded-lg bg-muted/30 p-2.5 transition-colors hover:bg-muted/50"
                            >
                                <Globe
                                    class="size-3.5 shrink-0 text-emerald-500"
                                />
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="text-[10px] tracking-wide text-muted-foreground uppercase"
                                    >
                                        Production
                                    </p>
                                    <a
                                        :href="project.production_url"
                                        target="_blank"
                                        class="block truncate font-mono text-xs transition-colors hover:text-primary"
                                    >
                                        {{ project.production_url }}
                                    </a>
                                </div>
                                <div
                                    class="flex shrink-0 items-center gap-1 opacity-0 transition-opacity group-hover/url:opacity-100"
                                >
                                    <button
                                        @click="
                                            copyToClipboard(
                                                project.production_url!,
                                                'Production URL',
                                            )
                                        "
                                    >
                                        <Copy
                                            class="size-3.5 text-muted-foreground hover:text-foreground"
                                        />
                                    </button>
                                    <a
                                        :href="project.production_url"
                                        target="_blank"
                                    >
                                        <ExternalLink
                                            class="size-3.5 text-muted-foreground hover:text-foreground"
                                        />
                                    </a>
                                </div>
                            </div>
                        </template>

                        <template v-if="project.staging_url">
                            <div
                                class="group/url flex items-center gap-2 rounded-lg bg-muted/30 p-2.5 transition-colors hover:bg-muted/50"
                            >
                                <Globe
                                    class="size-3.5 shrink-0 text-amber-500"
                                />
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="text-[10px] tracking-wide text-muted-foreground uppercase"
                                    >
                                        Staging
                                    </p>
                                    <a
                                        :href="project.staging_url"
                                        target="_blank"
                                        class="block truncate font-mono text-xs transition-colors hover:text-primary"
                                    >
                                        {{ project.staging_url }}
                                    </a>
                                </div>
                                <div
                                    class="flex shrink-0 items-center gap-1 opacity-0 transition-opacity group-hover/url:opacity-100"
                                >
                                    <button
                                        @click="
                                            copyToClipboard(
                                                project.staging_url!,
                                                'Staging URL',
                                            )
                                        "
                                    >
                                        <Copy
                                            class="size-3.5 text-muted-foreground hover:text-foreground"
                                        />
                                    </button>
                                </div>
                            </div>
                        </template>

                        <template v-if="project.repository_url">
                            <div
                                class="group/url flex items-center gap-2 rounded-lg bg-muted/30 p-2.5 transition-colors hover:bg-muted/50"
                            >
                                <GitBranch
                                    class="size-3.5 shrink-0 text-muted-foreground"
                                />
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="text-[10px] tracking-wide text-muted-foreground uppercase"
                                    >
                                        Repository
                                    </p>
                                    <a
                                        :href="project.repository_url"
                                        target="_blank"
                                        class="block truncate font-mono text-xs transition-colors hover:text-primary"
                                    >
                                        {{ project.repository_url }}
                                    </a>
                                </div>
                                <div
                                    class="flex shrink-0 items-center gap-1 opacity-0 transition-opacity group-hover/url:opacity-100"
                                >
                                    <button
                                        @click="
                                            copyToClipboard(
                                                project.repository_url!,
                                                'Repository URL',
                                            )
                                        "
                                    >
                                        <Copy
                                            class="size-3.5 text-muted-foreground hover:text-foreground"
                                        />
                                    </button>
                                </div>
                            </div>
                        </template>

                        <template v-if="project.server_ip">
                            <div
                                class="group/url flex items-center gap-2 rounded-lg bg-muted/30 p-2.5 transition-colors hover:bg-muted/50"
                            >
                                <Server
                                    class="size-3.5 shrink-0 text-muted-foreground"
                                />
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="text-[10px] tracking-wide text-muted-foreground uppercase"
                                    >
                                        Server IP
                                    </p>
                                    <p class="font-mono text-xs">
                                        {{ project.server_ip }}
                                    </p>
                                </div>
                                <button
                                    class="shrink-0 opacity-0 transition-opacity group-hover/url:opacity-100"
                                    @click="
                                        copyToClipboard(
                                            project.server_ip!,
                                            'Server IP',
                                        )
                                    "
                                >
                                    <Copy
                                        class="size-3.5 text-muted-foreground hover:text-foreground"
                                    />
                                </button>
                            </div>
                        </template>

                        <p
                            v-if="
                                !project.production_url &&
                                !project.staging_url &&
                                !project.repository_url &&
                                !project.server_ip
                            "
                            class="py-2 text-center text-xs text-muted-foreground"
                        >
                            No environment links set
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

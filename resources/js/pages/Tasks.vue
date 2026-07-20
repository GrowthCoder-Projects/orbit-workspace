<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Plus,
    Search,
    SlidersHorizontal,
    Columns,
    ListTodo,
    CheckSquare,
} from '@lucide/vue';
import { ref, computed } from 'vue';

import KanbanBoard from '@/components/KanbanBoard.vue';
import TaskList from '@/components/TaskList.vue';
import TaskModal from '@/components/TaskModal.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index as tasksIndex } from '@/routes/tasks';

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

const props = defineProps<{
    tasks: TaskType[];
    projects: ProjectType[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Tasks',
                href: tasksIndex().url,
            },
        ],
    },
});

// Tab state (board or list)
const activeTab = ref<'board' | 'list'>('board');

// Search & Filter state
const searchQuery = ref('');
const filterPriority = ref<string>('all');
const filterStatus = ref<string>('all');

// Modal state
const isModalOpen = ref(false);
const selectedTask = ref<TaskType | null>(null);
const defaultCreateStatus = ref<'todo' | 'in_progress' | 'blocked' | 'done'>(
    'todo',
);

// Filtering logic
const filteredTasks = computed(() => {
    return props.tasks.filter((task) => {
        // Search query filter
        const matchesSearch =
            task.title
                .toLowerCase()
                .includes(searchQuery.value.toLowerCase()) ||
            (task.description &&
                task.description
                    .toLowerCase()
                    .includes(searchQuery.value.toLowerCase())) ||
            (task.tags &&
                task.tags.some((t) =>
                    t.toLowerCase().includes(searchQuery.value.toLowerCase()),
                ));

        // Priority filter
        const matchesPriority =
            filterPriority.value === 'all' ||
            task.priority === filterPriority.value;

        // Status filter (only applied in List view, as Board view naturally displays columns)
        const matchesStatus =
            activeTab.value === 'board' ||
            filterStatus.value === 'all' ||
            task.status === filterStatus.value;

        return matchesSearch && matchesPriority && matchesStatus;
    });
});

// Modal control handlers
const openCreateModal = (
    status: 'todo' | 'in_progress' | 'blocked' | 'done' = 'todo',
) => {
    selectedTask.value = null;
    defaultCreateStatus.value = status;
    isModalOpen.value = true;
};

const openEditModal = (task: TaskType) => {
    selectedTask.value = task;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    selectedTask.value = null;
};
</script>

<template>
    <Head title="Tasks & Kanban" />

    <div class="space-y-6 px-6 py-6">
        <!-- Header section -->
        <div
            class="flex flex-col justify-between gap-4 border-b pb-5 md:flex-row md:items-center"
        >
            <div class="space-y-1">
                <h2
                    class="flex items-center gap-2 text-2xl font-bold tracking-tight"
                >
                    <CheckSquare class="size-6 text-primary" />
                    Tasks & Kanban
                </h2>
                <p class="text-sm text-muted-foreground">
                    Manage code milestones, priorities, sub-tasks, and kanban
                    cards.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <!-- Tab Toggle (Board / List) -->
                <div
                    class="flex items-center gap-0.5 rounded-lg border border-border bg-background p-0.5 shadow-sm"
                >
                    <Button
                        variant="ghost"
                        size="sm"
                        class="h-8 gap-1.5 rounded-md px-3 text-xs transition-all duration-150"
                        :class="
                            activeTab === 'board'
                                ? 'bg-primary font-semibold text-primary-foreground shadow-sm hover:bg-primary/90'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                        "
                        @click="activeTab = 'board'"
                    >
                        <Columns class="size-3.5" />
                        Board
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="h-8 gap-1.5 rounded-md px-3 text-xs transition-all duration-150"
                        :class="
                            activeTab === 'list'
                                ? 'bg-primary font-semibold text-primary-foreground shadow-sm hover:bg-primary/90'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                        "
                        @click="activeTab = 'list'"
                    >
                        <ListTodo class="size-3.5" />
                        List
                    </Button>
                </div>

                <!-- Create Task Trigger -->
                <Button
                    @click="() => openCreateModal('todo')"
                    class="h-9 gap-1.5 text-xs font-medium"
                >
                    <Plus class="size-4" />
                    Create Task
                </Button>
            </div>
        </div>

        <!-- Filters Section -->
        <div
            class="flex flex-col justify-between gap-4 rounded-xl border bg-card p-4 shadow-sm md:flex-row md:items-center"
        >
            <!-- Search bar -->
            <div class="relative max-w-sm flex-1">
                <Search
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="searchQuery"
                    placeholder="Search tasks, descriptions, or tags..."
                    class="h-9 pl-9 text-xs"
                />
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5">
                    <SlidersHorizontal class="size-3.5 text-muted-foreground" />
                    <span class="text-xs font-medium text-muted-foreground"
                        >Filters:</span
                    >
                </div>

                <!-- Priority Filter -->
                <Select v-model="filterPriority">
                    <SelectTrigger class="h-9 w-32 text-xs">
                        <SelectValue placeholder="Priority: All" />
                    </SelectTrigger>
                    <SelectContent class="text-xs">
                        <SelectItem value="all">Priority: All</SelectItem>
                        <SelectItem value="low">Low</SelectItem>
                        <SelectItem value="medium">Medium</SelectItem>
                        <SelectItem value="high">High</SelectItem>
                        <SelectItem value="urgent">Urgent</SelectItem>
                    </SelectContent>
                </Select>

                <!-- Status Filter (Only visible in List view) -->
                <Select v-if="activeTab === 'list'" v-model="filterStatus">
                    <SelectTrigger class="h-9 w-32 text-xs">
                        <SelectValue placeholder="Status: All" />
                    </SelectTrigger>
                    <SelectContent class="text-xs">
                        <SelectItem value="all">Status: All</SelectItem>
                        <SelectItem value="todo">To Do</SelectItem>
                        <SelectItem value="in_progress">In Progress</SelectItem>
                        <SelectItem value="blocked">Blocked</SelectItem>
                        <SelectItem value="done">Done</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <!-- Main Workspace content rendering -->
        <div class="h-full">
            <!-- Kanban Board Render -->
            <KanbanBoard
                v-if="activeTab === 'board'"
                :tasks="filteredTasks"
                @selectTask="openEditModal"
                @createTask="openCreateModal"
            />

            <!-- List View Render -->
            <TaskList
                v-else
                :tasks="filteredTasks"
                @selectTask="openEditModal"
            />
        </div>

        <!-- Task Overlay Detail/Create Modal -->
        <TaskModal
            :task="selectedTask"
            :is-open="isModalOpen"
            :default-status="defaultCreateStatus"
            :projects="projects"
            @close="closeModal"
        />
    </div>
</template>

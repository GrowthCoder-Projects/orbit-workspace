<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Plus,
    Search,
    FolderKanban,
    GitBranch,
    Globe,
    Server,
    Copy,
    CheckCircle2,
    Clock,
    Archive,
    CircleDot,
    ChevronRight,
    ExternalLink,
    BarChart3,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useConfirm } from '@/composables/useConfirm';
import {
    index as projectsIndex,
    create as projectCreate,
    show as projectShow,
    edit as projectEdit,
} from '@/routes/projects';

const { confirm } = useConfirm();

type Milestone = {
    id: number;
    title: string;
    due_date: string;
    status: 'pending' | 'completed';
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
    progress_percent: number;
    tasks_count: number;
    done_tasks_count: number;
    milestones: Milestone[];
    created_at: string;
};

const props = defineProps<{
    projects: Project[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Projects', href: projectsIndex().url }],
    },
});

const search = ref('');
const filterStatus = ref<'all' | 'active' | 'pipeline' | 'archived'>('all');

const filteredProjects = computed(() => {
    return props.projects.filter((p) => {
        const matchesSearch =
            p.name.toLowerCase().includes(search.value.toLowerCase()) ||
            (p.description &&
                p.description
                    .toLowerCase()
                    .includes(search.value.toLowerCase()));
        const matchesStatus =
            filterStatus.value === 'all' || p.status === filterStatus.value;

        return matchesSearch && matchesStatus;
    });
});

const statusConfig = {
    active: {
        label: 'Active',
        icon: CircleDot,
        classes:
            'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-800',
        dot: 'bg-blue-500',
    },
    pipeline: {
        label: 'Pipeline',
        icon: Clock,
        classes:
            'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800/50 dark:text-slate-300 dark:border-slate-700',
        dot: 'bg-slate-400',
    },
    archived: {
        label: 'Archived',
        icon: Archive,
        classes:
            'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-950/50 dark:text-orange-300 dark:border-orange-800',
        dot: 'bg-orange-400',
    },
};

const statusCounts = computed(() => ({
    all: props.projects.length,
    active: props.projects.filter((p) => p.status === 'active').length,
    pipeline: props.projects.filter((p) => p.status === 'pipeline').length,
    archived: props.projects.filter((p) => p.status === 'archived').length,
}));

const loadedFavicons = ref<Record<number, boolean>>({});

function getInitials(name: string): string {
    return name
        .split(/[\s-_]+/)
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase();
}

function getDomainFavicon(url: string | null): string | null {
    if (!url) {
return null;
}

    try {
        const parsedUrl = new URL(url);
        const host = parsedUrl.hostname;

        // Skip local domains, private IPs, and dummy domains to prevent browser console 404 errors
        if (
            host === 'localhost' ||
            host.endsWith('.local') ||
            host.endsWith('.test') ||
            host.includes('growthcoder') ||
            host === 'api.clientportal.com' ||
            host.match(/^(127\.|192\.168\.|10\.)/)
        ) {
            return null;
        }

        return `https://www.google.com/s2/favicons?domain=${parsedUrl.origin}&sz=64`;
    } catch {
        return null;
    }
}

async function copyToClipboard(text: string, label: string) {
    await navigator.clipboard.writeText(text);
    toast.success(`${label} copied to clipboard`);
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

async function deleteProject(project: Project) {
    const isConfirmed = await confirm({
        title: 'Delete Project',
        message: `Delete "${project.name}"? This cannot be undone.`,
        confirmText: 'Delete',
        cancelText: 'Cancel',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(`/app/projects/${project.id}`);
    }
}
</script>

<template>
    <Head title="Projects" />

    <div class="space-y-6 px-6 py-6">
        <!-- Header -->
        <div
            class="flex flex-col justify-between gap-4 border-b pb-5 md:flex-row md:items-center"
        >
            <div class="space-y-1">
                <h2
                    class="flex items-center gap-2 text-2xl font-bold tracking-tight"
                >
                    <FolderKanban class="size-6 text-primary" />
                    Projects
                </h2>
                <p class="text-sm text-muted-foreground">
                    Manage your active projects, environments, and milestones.
                </p>
            </div>
            <Link :href="projectCreate().url">
                <Button class="h-9 gap-1.5 text-xs font-medium">
                    <Plus class="size-4" />
                    New Project
                </Button>
            </Link>
        </div>

        <!-- Status filter tabs + Search -->
        <div
            class="flex flex-col justify-between gap-4 md:flex-row md:items-center"
        >
            <!-- Status Tabs -->
            <div
                class="flex items-center gap-1 rounded-lg border bg-muted/50 p-1"
            >
                <button
                    v-for="tab in [
                        'all',
                        'active',
                        'pipeline',
                        'archived',
                    ] as const"
                    :key="tab"
                    class="flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium capitalize transition-all duration-150"
                    :class="
                        filterStatus === tab
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="filterStatus = tab"
                >
                    {{ tab === 'all' ? 'All' : statusConfig[tab].label }}
                    <span
                        class="inline-flex h-4 min-w-4 items-center justify-center rounded px-1 text-[10px] font-semibold"
                        :class="
                            filterStatus === tab
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-muted-foreground'
                        "
                    >
                        {{ statusCounts[tab] }}
                    </span>
                </button>
            </div>

            <!-- Search -->
            <div class="relative max-w-xs">
                <Search
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    placeholder="Search projects..."
                    class="h-9 pl-9 text-xs"
                />
            </div>
        </div>

        <!-- Projects Grid -->
        <div
            v-if="filteredProjects.length > 0"
            class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
            <div
                v-for="project in filteredProjects"
                :key="project.id"
                class="group flex flex-col overflow-hidden rounded-xl border bg-card shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
            >
                <!-- Card accent line -->
                <div
                    class="h-1 w-full"
                    :style="{ backgroundColor: project.color ?? '#5C59D9' }"
                />

                <div class="flex flex-1 flex-col gap-4 p-5">
                    <!-- Header: Avatar + Name + Status -->
                    <div class="flex items-start gap-3">
                        <!-- Avatar -->
                        <div
                            class="relative flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg text-sm font-bold text-white shadow-sm"
                            :style="{
                                backgroundColor: project.color ?? '#5C59D9',
                            }"
                        >
                            <img
                                v-if="getDomainFavicon(project.production_url)"
                                :src="getDomainFavicon(project.production_url)!"
                                :alt="project.name"
                                class="z-10 h-6 w-6 rounded transition-opacity duration-200"
                                :class="
                                    loadedFavicons[project.id]
                                        ? 'opacity-100'
                                        : 'absolute opacity-0'
                                "
                                @load="loadedFavicons[project.id] = true"
                                @error="loadedFavicons[project.id] = false"
                            />
                            <span
                                v-if="!loadedFavicons[project.id]"
                                class="z-0"
                                >{{ getInitials(project.name) }}</span
                            >
                        </div>

                        <div class="min-w-0 flex-1">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <Link
                                    :href="projectShow(project.id).url"
                                    class="truncate text-sm leading-tight font-semibold transition-colors hover:text-primary"
                                >
                                    {{ project.name }}
                                </Link>
                                <span
                                    class="inline-flex shrink-0 items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                    :class="
                                        statusConfig[project.status].classes
                                    "
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full"
                                        :class="
                                            statusConfig[project.status].dot
                                        "
                                    />
                                    {{ statusConfig[project.status].label }}
                                </span>
                            </div>
                            <p
                                v-if="project.description"
                                class="mt-0.5 line-clamp-2 text-xs text-muted-foreground"
                            >
                                {{ project.description }}
                            </p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div v-if="project.tasks_count > 0" class="space-y-1.5">
                        <div
                            class="flex items-center justify-between text-[11px] text-muted-foreground"
                        >
                            <div class="flex items-center gap-1">
                                <BarChart3 class="size-3" />
                                <span>Task Progress</span>
                            </div>
                            <span class="font-semibold text-foreground">
                                {{ project.progress_percent }}%
                                <span class="font-normal text-muted-foreground"
                                    >({{ project.done_tasks_count }}/{{
                                        project.tasks_count
                                    }})</span
                                >
                            </span>
                        </div>
                        <div
                            class="h-1.5 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full rounded-full transition-all duration-700"
                                :style="{
                                    width: `${project.progress_percent}%`,
                                    backgroundColor: project.color ?? '#5C59D9',
                                }"
                            />
                        </div>
                    </div>

                    <!-- URL Links & IP -->
                    <div class="flex flex-col gap-1.5">
                        <TooltipProvider v-if="project.production_url">
                            <div class="group/url flex items-center gap-2">
                                <Globe
                                    class="size-3 shrink-0 text-muted-foreground"
                                />
                                <a
                                    :href="project.production_url"
                                    target="_blank"
                                    class="flex-1 truncate text-xs text-muted-foreground transition-colors hover:text-primary"
                                >
                                    {{ project.production_url }}
                                </a>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <button
                                            class="opacity-0 transition-opacity group-hover/url:opacity-100"
                                            @click="
                                                copyToClipboard(
                                                    project.production_url!,
                                                    'Production URL',
                                                )
                                            "
                                        >
                                            <Copy
                                                class="size-3 text-muted-foreground hover:text-foreground"
                                            />
                                        </button>
                                    </TooltipTrigger>
                                    <TooltipContent
                                        ><p>Copy URL</p></TooltipContent
                                    >
                                </Tooltip>
                            </div>
                        </TooltipProvider>

                        <div
                            v-if="project.repository_url"
                            class="group/url flex items-center gap-2"
                        >
                            <GitBranch
                                class="size-3 shrink-0 text-muted-foreground"
                            />
                            <a
                                :href="project.repository_url"
                                target="_blank"
                                class="flex-1 truncate text-xs text-muted-foreground transition-colors hover:text-primary"
                            >
                                {{
                                    project.repository_url.replace(
                                        'https://github.com/',
                                        '',
                                    )
                                }}
                            </a>
                            <button
                                class="opacity-0 transition-opacity group-hover/url:opacity-100"
                                @click="
                                    copyToClipboard(
                                        project.repository_url!,
                                        'Repository URL',
                                    )
                                "
                            >
                                <Copy
                                    class="size-3 text-muted-foreground hover:text-foreground"
                                />
                            </button>
                        </div>

                        <div
                            v-if="project.server_ip"
                            class="group/url flex items-center gap-2"
                        >
                            <Server
                                class="size-3 shrink-0 text-muted-foreground"
                            />
                            <span
                                class="flex-1 font-mono text-xs text-muted-foreground"
                            >
                                {{ project.server_ip }}
                            </span>
                            <button
                                class="opacity-0 transition-opacity group-hover/url:opacity-100"
                                @click="
                                    copyToClipboard(
                                        project.server_ip!,
                                        'Server IP',
                                    )
                                "
                            >
                                <Copy
                                    class="size-3 text-muted-foreground hover:text-foreground"
                                />
                            </button>
                        </div>
                    </div>

                    <!-- Next Milestone -->
                    <div
                        v-if="project.milestones.length > 0"
                        class="flex items-center gap-2 rounded-lg bg-muted/40 px-3 py-2"
                    >
                        <CheckCircle2
                            class="size-3.5 shrink-0 text-muted-foreground"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] text-muted-foreground">
                                Next milestone
                            </p>
                            <p class="truncate text-xs font-medium">
                                {{ project.milestones[0].title }}
                            </p>
                        </div>
                        <span
                            class="shrink-0 text-[10px] text-muted-foreground"
                        >
                            {{ formatDate(project.milestones[0].due_date) }}
                        </span>
                    </div>
                </div>

                <!-- Card footer actions -->
                <div
                    class="flex items-center justify-between border-t bg-muted/20 px-5 py-3"
                >
                    <Link
                        :href="projectShow(project.id).url"
                        class="flex items-center gap-1 text-xs font-medium text-muted-foreground transition-colors hover:text-primary"
                    >
                        View details
                        <ChevronRight class="size-3" />
                    </Link>

                    <div class="flex items-center gap-2">
                        <Link
                            :href="projectEdit(project.id).url"
                            class="text-xs text-muted-foreground transition-colors hover:text-foreground"
                        >
                            Edit
                        </Link>
                        <span class="text-muted-foreground/50">·</span>
                        <button
                            class="text-xs text-muted-foreground transition-colors hover:text-destructive"
                            @click="deleteProject(project)"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="flex flex-col items-center justify-center space-y-4 py-24 text-center"
        >
            <div
                class="flex h-20 w-20 items-center justify-center rounded-2xl border-2 border-dashed border-muted-foreground/20 bg-muted/50"
            >
                <FolderKanban class="size-9 text-muted-foreground/40" />
            </div>
            <div class="space-y-1">
                <h3 class="font-semibold text-foreground">
                    {{
                        search || filterStatus !== 'all'
                            ? 'No projects match your filter'
                            : 'No projects yet'
                    }}
                </h3>
                <p class="max-w-xs text-sm text-muted-foreground">
                    {{
                        search || filterStatus !== 'all'
                            ? 'Try adjusting your search or filter.'
                            : 'Create your first project to track environments, milestones, and task progress.'
                    }}
                </p>
            </div>
            <Link
                v-if="!search && filterStatus === 'all'"
                :href="projectCreate().url"
            >
                <Button variant="default" class="mt-2 gap-2">
                    <Plus class="size-4" />
                    Create first project
                </Button>
            </Link>
            <Button
                v-else
                variant="outline"
                class="mt-2 gap-2"
                @click="
                    search = '';
                    filterStatus = 'all';
                "
            >
                Clear filters
            </Button>
        </div>
    </div>
</template>

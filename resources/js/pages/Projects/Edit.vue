<script setup lang="ts">
import { Head, Link, router, useForm, setLayoutProps } from '@inertiajs/vue3';
import {
    ArrowLeft,
    FolderKanban,
    Globe,
    GitBranch,
    Server,
    Palette,
    AlertCircle,
    Trash2,
} from '@lucide/vue';
import { update as projectUpdate } from '@/actions/App/Http/Controllers/ProjectController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useConfirm } from '@/composables/useConfirm';
import { index as projectsIndex, show as projectShow } from '@/routes/projects';

const { confirm } = useConfirm();

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
};

const props = defineProps<{ project: Project }>();


setLayoutProps({
    breadcrumbs: [
        { title: 'Projects', href: projectsIndex().url },
        { title: props.project.name, href: projectShow(props.project.id).url },
        { title: 'Edit', href: '#' },
    ],
});

const ACCENT_COLORS = [
    { hex: '#5C59D9', label: 'Indigo' },
    { hex: '#2BB673', label: 'Emerald' },
    { hex: '#2D2A6F', label: 'Navy' },
    { hex: '#E07B54', label: 'Coral' },
    { hex: '#9B59B6', label: 'Purple' },
    { hex: '#E74C3C', label: 'Red' },
    { hex: '#3498DB', label: 'Blue' },
    { hex: '#F39C12', label: 'Amber' },
    { hex: '#1ABC9C', label: 'Teal' },
    { hex: '#E91E63', label: 'Pink' },
];

const form = useForm({
    name: props.project.name,
    description: props.project.description ?? '',
    color: props.project.color ?? '#5C59D9',
    status: props.project.status,
    repository_url: props.project.repository_url ?? '',
    production_url: props.project.production_url ?? '',
    staging_url: props.project.staging_url ?? '',
    server_ip: props.project.server_ip ?? '',
    _method: 'PUT',
});

function submit() {
    form.post(`/app/projects/${props.project.id}`);
}

async function deleteProject() {
    const isConfirmed = await confirm({
        title: 'Delete Project',
        message: `Delete "${props.project.name}"? This cannot be undone.`,
        confirmText: 'Delete',
        cancelText: 'Cancel',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(`/app/projects/${props.project.id}`);
    }
}
</script>

<template>
    <Head :title="`Edit · ${project.name}`" />

    <div class="mx-auto max-w-2xl space-y-6 px-6 py-6">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <Link :href="projectShow(project.id).url">
                <Button variant="ghost" size="icon" class="size-9">
                    <ArrowLeft class="size-4" />
                </Button>
            </Link>
            <div>
                <h2
                    class="flex items-center gap-2 text-xl font-bold tracking-tight"
                >
                    <FolderKanban class="size-5 text-primary" />
                    Edit Project
                </h2>
                <p class="text-sm text-muted-foreground">
                    Update project details and environments.
                </p>
            </div>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <!-- Basic Info -->
            <div class="space-y-4 rounded-xl border bg-card p-5">
                <h3
                    class="flex items-center gap-2 text-sm font-semibold text-foreground"
                >
                    <FolderKanban class="size-4 text-muted-foreground" />
                    Basic Information
                </h3>

                <div class="space-y-1.5">
                    <Label for="name" class="text-xs font-medium"
                        >Project Name
                        <span class="text-destructive">*</span></Label
                    >
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="e.g., Workspace OS..."
                        class="h-9"
                        :class="{ 'border-destructive': form.errors.name }"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="space-y-1.5">
                    <Label for="description" class="text-xs font-medium"
                        >Description</Label
                    >
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        placeholder="Brief description..."
                        class="w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label class="text-xs font-medium"
                            >Status
                            <span class="text-destructive">*</span></Label
                        >
                        <Select v-model="form.status">
                            <SelectTrigger class="h-9 text-xs">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="active"
                                    >🔵 Active</SelectItem
                                >
                                <SelectItem value="pipeline"
                                    >⚫ Pipeline</SelectItem
                                >
                                <SelectItem value="archived"
                                    >🟠 Archived</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            class="flex items-center gap-1 text-xs font-medium"
                        >
                            <Palette class="size-3" />
                            Accent Color
                        </Label>
                        <div class="flex flex-wrap gap-1.5 pt-0.5">
                            <button
                                v-for="color in ACCENT_COLORS"
                                :key="color.hex"
                                type="button"
                                class="h-6 w-6 rounded-md border-2 transition-all duration-150 hover:scale-110"
                                :style="{ backgroundColor: color.hex }"
                                :class="
                                    form.color === color.hex
                                        ? 'scale-110 border-foreground'
                                        : 'border-transparent'
                                "
                                :title="color.label"
                                @click="form.color = color.hex"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Environments -->
            <div class="space-y-4 rounded-xl border bg-card p-5">
                <h3
                    class="flex items-center gap-2 text-sm font-semibold text-foreground"
                >
                    <Globe class="size-4 text-muted-foreground" />
                    Environments & Links
                </h3>
                <div class="grid grid-cols-1 gap-3">
                    <div class="space-y-1.5">
                        <Label
                            for="production_url"
                            class="flex items-center gap-1.5 text-xs font-medium"
                        >
                            <Globe class="size-3 text-emerald-500" />Production
                            URL
                        </Label>
                        <Input
                            id="production_url"
                            v-model="form.production_url"
                            type="url"
                            placeholder="https://your-app.com"
                            class="h-9 font-mono text-xs"
                        />
                        <InputError :message="form.errors.production_url" />
                    </div>
                    <div class="space-y-1.5">
                        <Label
                            for="staging_url"
                            class="flex items-center gap-1.5 text-xs font-medium"
                        >
                            <Globe class="size-3 text-amber-500" />Staging URL
                        </Label>
                        <Input
                            id="staging_url"
                            v-model="form.staging_url"
                            type="url"
                            placeholder="https://staging.your-app.com"
                            class="h-9 font-mono text-xs"
                        />
                        <InputError :message="form.errors.staging_url" />
                    </div>
                    <div class="space-y-1.5">
                        <Label
                            for="repository_url"
                            class="flex items-center gap-1.5 text-xs font-medium"
                        >
                            <GitBranch
                                class="size-3 text-muted-foreground"
                            />Repository URL
                        </Label>
                        <Input
                            id="repository_url"
                            v-model="form.repository_url"
                            type="url"
                            placeholder="https://github.com/user/repo"
                            class="h-9 font-mono text-xs"
                        />
                        <InputError :message="form.errors.repository_url" />
                    </div>
                    <div class="space-y-1.5">
                        <Label
                            for="server_ip"
                            class="flex items-center gap-1.5 text-xs font-medium"
                        >
                            <Server
                                class="size-3 text-muted-foreground"
                            />Server IP
                        </Label>
                        <Input
                            id="server_ip"
                            v-model="form.server_ip"
                            placeholder="192.168.1.100"
                            class="h-9 font-mono text-xs"
                        />
                        <InputError :message="form.errors.server_ip" />
                    </div>
                </div>
            </div>

            <!-- Error summary -->
            <div
                v-if="form.hasErrors"
                class="flex items-center gap-2 rounded-lg border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive"
            >
                <AlertCircle class="size-4 shrink-0" />
                Please fix the errors above before saving.
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between">
                <Link :href="projectShow(project.id).url">
                    <Button type="button" variant="ghost" class="gap-1.5">
                        <ArrowLeft class="size-4" />
                        Cancel
                    </Button>
                </Link>
                <Button
                    type="submit"
                    :disabled="form.processing"
                    class="min-w-32 gap-1.5"
                >
                    <span v-if="form.processing">Saving...</span>
                    <span v-else>Save Changes</span>
                </Button>
            </div>
        </form>

        <!-- Danger Zone -->
        <div
            class="space-y-3 rounded-xl border border-destructive/20 bg-destructive/5 p-5"
        >
            <h3
                class="flex items-center gap-2 text-sm font-semibold text-destructive"
            >
                <Trash2 class="size-4" />
                Danger Zone
            </h3>
            <p class="text-xs text-muted-foreground">
                Permanently delete this project and all related milestones. This
                action cannot be undone.
            </p>
            <Button
                variant="destructive"
                size="sm"
                class="gap-1.5"
                @click="deleteProject"
            >
                <Trash2 class="size-3.5" />
                Delete Project
            </Button>
        </div>
    </div>
</template>

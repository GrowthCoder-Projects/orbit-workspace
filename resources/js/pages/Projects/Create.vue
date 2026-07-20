<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    FolderKanban,
    Globe,
    GitBranch,
    Server,
    Palette,
    AlertCircle,
} from '@lucide/vue';
import { store as projectStore } from '@/actions/App/Http/Controllers/ProjectController';
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
import { index as projectsIndex } from '@/routes/projects';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Projects', href: projectsIndex().url },
            { title: 'New Project', href: '#' },
        ],
    },
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
    name: '',
    description: '',
    color: '#5C59D9',
    status: 'active' as 'active' | 'pipeline' | 'archived',
    repository_url: '',
    production_url: '',
    staging_url: '',
    server_ip: '',
});

function submit() {
    form.post(projectStore().url);
}
</script>

<template>
    <Head title="New Project" />

    <div class="mx-auto max-w-2xl space-y-6 px-6 py-6">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <Link :href="projectsIndex().url">
                <Button variant="ghost" size="icon" class="size-9">
                    <ArrowLeft class="size-4" />
                </Button>
            </Link>
            <div>
                <h2
                    class="flex items-center gap-2 text-xl font-bold tracking-tight"
                >
                    <FolderKanban class="size-5 text-primary" />
                    New Project
                </h2>
                <p class="text-sm text-muted-foreground">
                    Set up a new project with environment links and tracking.
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

                <!-- Name -->
                <div class="space-y-1.5">
                    <Label for="name" class="text-xs font-medium"
                        >Project Name
                        <span class="text-destructive">*</span></Label
                    >
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="e.g., Workspace OS, E-Commerce API..."
                        class="h-9"
                        :class="{ 'border-destructive': form.errors.name }"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <!-- Description -->
                <div class="space-y-1.5">
                    <Label for="description" class="text-xs font-medium"
                        >Description</Label
                    >
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        placeholder="Brief description of the project, its purpose, or tech stack..."
                        class="w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <!-- Status + Color Row -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Status -->
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
                        <InputError :message="form.errors.status" />
                    </div>

                    <!-- Color Picker -->
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
                    <!-- Production URL -->
                    <div class="space-y-1.5">
                        <Label
                            for="production_url"
                            class="flex items-center gap-1.5 text-xs font-medium"
                        >
                            <Globe class="size-3 text-emerald-500" />
                            Production URL
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

                    <!-- Staging URL -->
                    <div class="space-y-1.5">
                        <Label
                            for="staging_url"
                            class="flex items-center gap-1.5 text-xs font-medium"
                        >
                            <Globe class="size-3 text-amber-500" />
                            Staging URL
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

                    <!-- Repository URL -->
                    <div class="space-y-1.5">
                        <Label
                            for="repository_url"
                            class="flex items-center gap-1.5 text-xs font-medium"
                        >
                            <GitBranch class="size-3 text-muted-foreground" />
                            Repository URL
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

                    <!-- Server IP -->
                    <div class="space-y-1.5">
                        <Label
                            for="server_ip"
                            class="flex items-center gap-1.5 text-xs font-medium"
                        >
                            <Server class="size-3 text-muted-foreground" />
                            Server IP Address
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
                <Link :href="projectsIndex().url">
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
                    <span v-if="form.processing">Creating...</span>
                    <span v-else>Create Project</span>
                </Button>
            </div>
        </form>
    </div>
</template>

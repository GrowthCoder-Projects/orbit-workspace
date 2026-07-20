<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Database,
    Download,
    Trash2,
    Server,
    Archive,
    RefreshCw,
    Info,
} from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { useConfirm } from '@/composables/useConfirm';
import { run, download, destroy } from '@/routes/backups';

const { confirm } = useConfirm();

type Backup = {
    filename: string;
    size: string;
    created_at: string;
    created_at_diff: string;
};

const props = defineProps<{
    backups: Backup[];
    activeDisk: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Backups',
                href: '/app/settings/backups',
            },
        ],
    },
});

const isRunning = ref(false);

const runBackup = () => {
    isRunning.value = true;
    router.post(
        run.url(),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isRunning.value = false;
            },
        },
    );
};

const deleteBackup = async (filename: string) => {
    const isConfirmed = await confirm({
        title: 'Delete Backup',
        message: 'Are you sure you want to delete this backup file?',
        confirmText: 'Delete',
        cancelText: 'Cancel',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(destroy.url({ filename }), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Backups" />

    <h1 class="sr-only">Backups</h1>

    <div class="space-y-8">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <Heading
                variant="small"
                title="Database Backups"
                description="Manage your database dumps, download archives, or trigger an instant backup."
            />

            <Button
                type="button"
                :disabled="isRunning"
                @click="runBackup"
                class="flex items-center self-start rounded-lg bg-brand-primary px-4 py-2 font-medium text-white shadow-sm transition-all hover:bg-brand-primary-hover sm:self-center"
            >
                <span
                    v-if="isRunning"
                    class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"
                />
                <RefreshCw v-else class="mr-2 h-4 w-4 animate-pulse" />
                {{ isRunning ? 'Creating Backup...' : 'Run Backup Now' }}
            </Button>
        </div>

        <!-- Stats cards grid -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <!-- Active Disk Card -->
            <div
                class="flex items-center rounded-xl border border-border bg-card p-4 shadow-sm"
            >
                <div
                    class="mr-4 rounded-lg bg-brand-primary/10 p-3 text-brand-primary dark:text-brand-secondary"
                >
                    <Server class="h-6 w-6" />
                </div>
                <div>
                    <p
                        class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        Backup Destination
                    </p>
                    <p
                        class="mt-0.5 font-mono text-lg font-semibold text-foreground"
                    >
                        {{ props.activeDisk.toUpperCase() }}
                    </p>
                </div>
            </div>

            <!-- Total Backups Card -->
            <div
                class="flex items-center rounded-xl border border-border bg-card p-4 shadow-sm"
            >
                <div
                    class="mr-4 rounded-lg bg-brand-accent/10 p-3 text-brand-accent"
                >
                    <Archive class="h-6 w-6" />
                </div>
                <div>
                    <p
                        class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        Total Stored Backups
                    </p>
                    <p class="mt-0.5 text-lg font-semibold text-foreground">
                        {{ props.backups.length }} files
                    </p>
                </div>
            </div>
        </div>

        <!-- Info Alert Box -->
        <div
            class="flex items-start space-x-3 rounded-xl border border-blue-200 bg-blue-50/50 p-4 dark:border-blue-900/50 dark:bg-blue-950/20"
        >
            <Info
                class="mt-0.5 h-5 w-5 shrink-0 text-blue-600 dark:text-blue-400"
            />
            <div class="space-y-1">
                <h5
                    class="text-sm font-semibold text-blue-900 dark:text-blue-300"
                >
                    Background Processing Active
                </h5>
                <p
                    class="text-xs leading-relaxed text-blue-700 dark:text-blue-400"
                >
                    Database backup dijalankan secara background untuk mencegah
                    server hang di sistem Windows. Proses ini biasanya memakan
                    waktu sekitar <strong>3-5 detik</strong>. Anda dapat
                    memantau jalannya proses secara real-time di log
                    (<code>storage/logs/backup.log</code>). Silakan muat ulang
                    (refresh) halaman ini setelah beberapa saat untuk melihat
                    arsip zip terbaru.
                </p>
            </div>
        </div>

        <!-- Stored Archives Table -->
        <div class="space-y-3">
            <h3
                class="px-1 text-sm font-semibold tracking-tight text-foreground"
            >
                Stored Archives
            </h3>

            <div
                class="overflow-hidden rounded-xl border border-border bg-card shadow-sm"
            >
                <div
                    v-if="props.backups.length === 0"
                    class="flex flex-col items-center justify-center p-16 text-center"
                >
                    <div
                        class="mb-4 animate-bounce rounded-full bg-muted/40 p-4"
                    >
                        <Database
                            class="stroke-1.5 h-8 w-8 text-muted-foreground/60"
                        />
                    </div>
                    <h4 class="text-sm font-medium text-foreground">
                        No backups found
                    </h4>
                    <p class="mt-1.5 max-w-xs text-xs text-muted-foreground">
                        There are no database dumps currently stored on the
                        {{ props.activeDisk }} disk. Trigger a manual backup to
                        get started.
                    </p>
                </div>

                <div v-else class="divide-y divide-border">
                    <div
                        v-for="backup in props.backups"
                        :key="backup.filename"
                        class="group flex items-center justify-between p-4 transition-all hover:bg-muted/5"
                    >
                        <div class="flex min-w-0 items-center space-x-4 pr-4">
                            <div
                                class="rounded-lg bg-neutral-100 p-2 text-neutral-500 transition-colors group-hover:text-brand-primary dark:bg-neutral-800 dark:group-hover:text-brand-secondary"
                            >
                                <Database class="h-5 w-5" />
                            </div>
                            <div class="flex min-w-0 flex-col gap-1">
                                <span
                                    class="max-w-xs truncate font-mono text-sm font-medium text-foreground sm:max-w-md"
                                    :title="backup.filename"
                                >
                                    {{ backup.filename }}
                                </span>
                                <div
                                    class="flex items-center space-x-2 text-xs text-muted-foreground"
                                >
                                    <span
                                        class="rounded-full bg-neutral-100 px-2 py-0.5 text-[10px] font-medium dark:bg-neutral-800"
                                    >
                                        {{ backup.size }}
                                    </span>
                                    <span>•</span>
                                    <span :title="backup.created_at">{{
                                        backup.created_at_diff
                                    }}</span>
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex shrink-0 items-center gap-2 opacity-100 transition-opacity duration-200 group-hover:opacity-100 sm:opacity-0"
                        >
                            <a
                                :href="
                                    download.url({ filename: backup.filename })
                                "
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-input bg-background text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                                title="Download backup"
                            >
                                <Download class="h-4 w-4" />
                                <span class="sr-only">Download</span>
                            </a>

                            <Button
                                variant="outline"
                                size="icon"
                                class="h-9 w-9 rounded-lg border-input text-destructive transition-colors hover:bg-destructive/10 hover:text-destructive"
                                @click="deleteBackup(backup.filename)"
                                title="Delete backup"
                            >
                                <Trash2 class="h-4 w-4" />
                                <span class="sr-only">Delete</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

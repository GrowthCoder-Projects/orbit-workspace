<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Bell,
    Activity as ActivityIcon,
    CheckCheck,
    Filter,
    CalendarDays,
    FolderKanban,
    CircleCheck,
    Pencil,
    Trash2,
    LogIn,
    LogOut,
    ArrowLeftRight,
    TriangleAlert,
    ChevronLeft,
    ChevronRight,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import { Badge } from '@/components/ui/badge';
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
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as activityIndex } from '@/routes/activity';
import { markAllRead } from '@/routes/notifications';

// Page Types
type PaginatedData<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    links: { url: string | null; label: string; active: boolean }[];
};

type NotificationItem = {
    id: string;
    data: { title: string; body: string };
    read_at: string | null;
    created_at: string;
};

type ActivityLogItem = {
    id: number;
    action: string;
    subject_type: string | null;
    subject_id: number | null;
    subject_label: string | null;
    description: string;
    properties: { field: string; old: unknown; new: unknown }[] | null;
    ip_address: string | null;
    created_at: string;
};

type Filters = {
    tab: string;
    module: string;
    from: string | null;
    to: string | null;
};

const props = defineProps<{
    notifications: PaginatedData<NotificationItem>;
    activityLogs: PaginatedData<ActivityLogItem>;
    availableModules: string[];
    filters: Filters;
    unreadCount: number;
}>();

// Define Layout
defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Activity Logs',
                href: '/app/activity',
            },
        ],
    },
});

// Local state
const activeTab = ref<'notifications' | 'activity'>(
    props.filters.tab === 'activity' ? 'activity' : 'notifications',
);
const selectedModule = ref(props.filters.module ?? 'all');
const dateFrom = ref(props.filters.from ?? '');
const dateTo = ref(props.filters.to ?? '');

// Action icons map
function getActionIcon(action: string) {
    const map: Record<string, unknown> = {
        created: CircleCheck,
        updated: Pencil,
        deleted: Trash2,
        login: LogIn,
        logout: LogOut,
        status_changed: ArrowLeftRight,
        budget_alert: TriangleAlert,
    };

    return map[action] ?? ActivityIcon;
}

function getActionColor(action: string): string {
    const map: Record<string, string> = {
        created: 'text-green-500',
        updated: 'text-blue-500',
        deleted: 'text-red-500',
        login: 'text-violet-500',
        logout: 'text-orange-500',
        status_changed: 'text-cyan-500',
        budget_alert: 'text-yellow-500',
    };

    return map[action] ?? 'text-muted-foreground';
}

function getActionBadgeVariant(action: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (action === 'deleted') {
return 'destructive';
}

    if (action === 'budget_alert') {
return 'default';
}

    return 'secondary';
}

function formatTime(dateStr: string): string {
    const date = new Date(dateStr);

    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatRelativeTime(dateStr: string): string {
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 1) {
return 'Baru saja';
}

    if (diffMins < 60) {
return `${diffMins}m lalu`;
}

    if (diffHours < 24) {
return `${diffHours}j lalu`;
}

    return `${diffDays}h lalu`;
}

function switchTab(tab: 'notifications' | 'activity'): void {
    activeTab.value = tab;
    router.get(activityIndex(), { tab }, { preserveScroll: true, preserveState: true, replace: true });
}

function applyFilters(): void {
    router.get(activityIndex(), {
        tab: 'activity',
        module: selectedModule.value,
        from: dateFrom.value || undefined,
        to: dateTo.value || undefined,
    }, { preserveScroll: true });
}

function resetFilters(): void {
    selectedModule.value = 'all';
    dateFrom.value = '';
    dateTo.value = '';
    router.get(activityIndex(), { tab: 'activity' }, { preserveScroll: true });
}

function handleMarkAllRead(): void {
    router.post(markAllRead(), {}, { preserveScroll: true });
}

function goToPage(url: string | null): void {
    if (url) {
        router.get(url, {
            tab: activeTab.value,
            module: selectedModule.value,
            from: dateFrom.value || undefined,
            to: dateTo.value || undefined,
        }, { preserveScroll: true });
    }
}

// Format property diff for display
function formatPropertyValue(value: unknown): string {
    if (value === null || value === undefined) {
return '—';
}

    if (typeof value === 'boolean') {
return value ? 'true' : 'false';
}

    if (typeof value === 'object') {
return JSON.stringify(value);
}

    return String(value);
}
</script>

<template>
    <Head title="Activity & Notifications" />

    <div class="flex flex-1 flex-col gap-6 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Activity & Notifications</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    Pantau semua aktivitas dan notifikasi terbaru di workspace Anda.
                </p>
            </div>
        </div>

        <!-- Tab Switcher -->
        <div class="flex gap-1 rounded-xl border border-border/60 bg-muted/40 p-1 w-fit">
            <button
                :class="[
                    'flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all duration-200',
                    activeTab === 'notifications'
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground',
                ]"
                @click="switchTab('notifications')"
            >
                <Bell class="h-4 w-4" />
                Notifications
                <Badge
                    v-if="unreadCount > 0"
                    class="ml-0.5 h-5 min-w-5 rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white"
                >
                    {{ unreadCount }}
                </Badge>
            </button>
            <button
                :class="[
                    'flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all duration-200',
                    activeTab === 'activity'
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground',
                ]"
                @click="switchTab('activity')"
            >
                <ActivityIcon class="h-4 w-4" />
                Activity Log
            </button>
        </div>

        <!-- ============================= -->
        <!-- TAB: NOTIFICATIONS -->
        <!-- ============================= -->
        <div v-if="activeTab === 'notifications'" class="space-y-4">
            <!-- Toolbar -->
            <div class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    {{ notifications.total }} notifikasi
                    <template v-if="unreadCount > 0">
                        · <span class="font-medium text-blue-500">{{ unreadCount }} belum dibaca</span>
                    </template>
                </p>
                <Button
                    v-if="unreadCount > 0"
                    variant="outline"
                    size="sm"
                    class="gap-1.5"
                    @click="handleMarkAllRead"
                >
                    <CheckCheck class="h-4 w-4" />
                    Mark all as read
                </Button>
            </div>

            <!-- Empty State -->
            <div
                v-if="notifications.data.length === 0"
                class="flex flex-col items-center justify-center gap-4 rounded-2xl border border-dashed border-border/60 py-20 text-center"
            >
                <div class="rounded-full bg-muted p-4">
                    <Bell class="h-8 w-8 text-muted-foreground/50" />
                </div>
                <div>
                    <p class="font-medium">Belum ada notifikasi</p>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        Notifikasi akan muncul di sini saat ada aktivitas penting.
                    </p>
                </div>
            </div>

            <!-- Notification List -->
            <div v-else class="divide-y divide-border/60 overflow-hidden rounded-2xl border border-border/60">
                <div
                    v-for="notif in notifications.data"
                    :key="notif.id"
                    :class="[
                        'relative px-5 py-4 transition-colors',
                        !notif.read_at ? 'bg-blue-500/5' : 'bg-background',
                    ]"
                >
                    <!-- Unread indicator -->
                    <div
                        v-if="!notif.read_at"
                        class="absolute left-2.5 top-5 h-2 w-2 rounded-full bg-blue-500"
                    />
                    <div :class="!notif.read_at ? 'pl-2' : ''">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <p class="text-sm font-semibold leading-tight">{{ notif.data.title }}</p>
                                <p class="mt-0.5 text-sm text-muted-foreground">{{ notif.data.body }}</p>
                            </div>
                            <span class="shrink-0 text-xs text-muted-foreground/60">
                                {{ formatRelativeTime(notif.created_at) }}
                            </span>
                        </div>
                        <p class="mt-1.5 text-[11px] text-muted-foreground/50">
                            {{ formatTime(notif.created_at) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="notifications.last_page > 1" class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    Halaman {{ notifications.current_page }} dari {{ notifications.last_page }}
                </p>
                <div class="flex gap-1">
                    <Button
                        variant="outline"
                        size="icon"
                        class="h-8 w-8"
                        :disabled="notifications.current_page === 1"
                        @click="goToPage(notifications.links[0]?.url)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        class="h-8 w-8"
                        :disabled="notifications.current_page === notifications.last_page"
                        @click="goToPage(notifications.links[notifications.links.length - 1]?.url)"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- ============================= -->
        <!-- TAB: ACTIVITY LOG -->
        <!-- ============================= -->
        <div v-if="activeTab === 'activity'" class="grid grid-cols-1 gap-6 lg:grid-cols-[220px_1fr]">
            <!-- Filter Sidebar -->
            <aside class="space-y-4">
                <div class="rounded-xl border border-border/60 bg-card p-4">
                    <div class="mb-3 flex items-center gap-2">
                        <Filter class="h-4 w-4 text-muted-foreground" />
                        <span class="text-sm font-semibold">Filter</span>
                    </div>

                    <div class="space-y-4">
                        <!-- Module Filter -->
                        <div class="space-y-1.5">
                            <Label class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground/75">Modul</Label>
                            <Select v-model="selectedModule">
                                <SelectTrigger class="h-8 text-xs bg-zinc-950/20 border-border/80 focus:ring-1 focus:ring-zinc-700">
                                    <SelectValue placeholder="Semua modul" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="module in availableModules"
                                        :key="module"
                                        :value="module"
                                        class="text-xs"
                                    >
                                        {{ module === 'all' ? 'Semua Modul' : module }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Date Range Filter -->
                        <div class="space-y-1.5">
                            <Label class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground/75">Dari Tanggal</Label>
                            <div class="relative flex items-center">
                                <Input
                                    v-model="dateFrom"
                                    type="date"
                                    class="h-8 w-full text-xs pr-8 cursor-pointer select-none bg-zinc-950/20 border-border/80 focus:border-zinc-700 transition-colors relative [&::-webkit-calendar-picker-indicator]:absolute [&::-webkit-calendar-picker-indicator]:right-0 [&::-webkit-calendar-picker-indicator]:opacity-0 [&::-webkit-calendar-picker-indicator]:w-full [&::-webkit-calendar-picker-indicator]:h-full [&::-webkit-calendar-picker-indicator]:cursor-pointer [&::-webkit-calendar-picker-indicator]:z-10"
                                />
                                <CalendarDays class="absolute right-2.5 h-3.5 w-3.5 text-muted-foreground/60 pointer-events-none" />
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground/75">Sampai Tanggal</Label>
                            <div class="relative flex items-center">
                                <Input
                                    v-model="dateTo"
                                    type="date"
                                    class="h-8 w-full text-xs pr-8 cursor-pointer select-none bg-zinc-950/20 border-border/80 focus:border-zinc-700 transition-colors relative [&::-webkit-calendar-picker-indicator]:absolute [&::-webkit-calendar-picker-indicator]:right-0 [&::-webkit-calendar-picker-indicator]:opacity-0 [&::-webkit-calendar-picker-indicator]:w-full [&::-webkit-calendar-picker-indicator]:h-full [&::-webkit-calendar-picker-indicator]:cursor-pointer [&::-webkit-calendar-picker-indicator]:z-10"
                                />
                                <CalendarDays class="absolute right-2.5 h-3.5 w-3.5 text-muted-foreground/60 pointer-events-none" />
                            </div>
                        </div>

                        <Separator class="my-3 bg-border/40" />

                        <div class="flex flex-col gap-2 pt-1">
                            <Button size="sm" class="w-full bg-primary hover:bg-primary/90 font-semibold" @click="applyFilters">Terapkan</Button>
                            <Button
                                v-if="selectedModule !== 'all' || dateFrom || dateTo"
                                size="sm"
                                variant="outline"
                                class="w-full border-border hover:bg-muted/50"
                                @click="resetFilters"
                            >
                                Reset Filter
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Stats card -->
                <div class="rounded-xl border border-border/60 bg-card p-4">
                    <p class="text-sm font-semibold">Total Aktivitas</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight">{{ activityLogs.total }}</p>
                    <p class="text-xs text-muted-foreground">log tercatat</p>
                </div>
            </aside>

            <!-- Timeline -->
            <div class="space-y-4">
                <p class="text-sm text-muted-foreground">
                    Menampilkan {{ activityLogs.data.length }} dari {{ activityLogs.total }} aktivitas
                </p>

                <!-- Empty State -->
                <div
                    v-if="activityLogs.data.length === 0"
                    class="flex flex-col items-center justify-center gap-4 rounded-2xl border border-dashed border-border/60 py-20 text-center"
                >
                    <div class="rounded-full bg-muted p-4">
                        <ActivityIcon class="h-8 w-8 text-muted-foreground/50" />
                    </div>
                    <div>
                        <p class="font-medium">Tidak ada aktivitas ditemukan</p>
                        <p class="mt-0.5 text-sm text-muted-foreground">
                            Coba ubah filter atau mulai berinteraksi dengan aplikasi.
                        </p>
                    </div>
                </div>

                <!-- Activity Timeline -->
                <div v-else class="relative space-y-0">
                    <!-- Vertical line -->
                    <div class="absolute left-[19px] top-5 bottom-0 w-px bg-border/50" />

                    <div
                        v-for="(log, index) in activityLogs.data"
                        :key="log.id"
                        class="relative flex gap-4 pb-6 last:pb-0"
                    >
                        <!-- Icon circle -->
                        <div
                            :class="[
                                'relative z-10 mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 border-background bg-muted',
                            ]"
                        >
                            <component
                                :is="getActionIcon(log.action)"
                                :class="['h-4 w-4', getActionColor(log.action)]"
                            />
                        </div>

                        <!-- Content card -->
                        <div class="flex-1 rounded-xl border border-border/60 bg-card p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <Badge
                                            :variant="getActionBadgeVariant(log.action)"
                                            class="h-5 rounded-md px-1.5 text-[10px] font-semibold uppercase tracking-wide"
                                        >
                                            {{ log.action.replace('_', ' ') }}
                                        </Badge>
                                        <span
                                            v-if="log.subject_label"
                                            class="rounded-md bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground"
                                        >
                                            {{ log.subject_label }}
                                        </span>
                                    </div>
                                    <p class="mt-1.5 text-sm font-medium leading-tight">
                                        {{ log.description }}
                                    </p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-xs font-medium text-muted-foreground">
                                        {{ formatRelativeTime(log.created_at) }}
                                    </p>
                                    <p class="mt-0.5 text-[10px] text-muted-foreground/50">
                                        {{ formatTime(log.created_at) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Properties Diff -->
                            <div
                                v-if="log.properties && log.properties.length > 0"
                                class="mt-3 rounded-lg bg-muted/60 p-3"
                            >
                                <div
                                    v-for="prop in log.properties"
                                    :key="prop.field"
                                    class="flex items-start gap-2 font-mono text-[11px]"
                                >
                                    <span class="shrink-0 text-muted-foreground/70">{{ prop.field }}:</span>
                                    <div class="flex flex-wrap items-center gap-1 min-w-0">
                                        <span
                                            v-if="prop.old !== null"
                                            class="rounded bg-red-500/15 px-1 py-0.5 text-red-600 dark:text-red-400 line-through"
                                        >
                                            {{ formatPropertyValue(prop.old) }}
                                        </span>
                                        <span
                                            v-if="prop.old !== null && prop.new !== null"
                                            class="text-muted-foreground/50"
                                        >→</span>
                                        <span
                                            v-if="prop.new !== null"
                                            class="rounded bg-green-500/15 px-1 py-0.5 text-green-600 dark:text-green-400"
                                        >
                                            {{ formatPropertyValue(prop.new) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- IP Address (auth events) -->
                            <p
                                v-if="log.ip_address && (log.action === 'login' || log.action === 'logout')"
                                class="mt-2 font-mono text-[10px] text-muted-foreground/50"
                            >
                                IP: {{ log.ip_address }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="activityLogs.last_page > 1" class="flex items-center justify-between pt-2">
                    <p class="text-sm text-muted-foreground">
                        Halaman {{ activityLogs.current_page }} dari {{ activityLogs.last_page }}
                    </p>
                    <div class="flex gap-1">
                        <Button
                            variant="outline"
                            size="icon"
                            class="h-8 w-8"
                            :disabled="activityLogs.current_page === 1"
                            @click="goToPage(activityLogs.links[0]?.url)"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="outline"
                            size="icon"
                            class="h-8 w-8"
                            :disabled="activityLogs.current_page === activityLogs.last_page"
                            @click="goToPage(activityLogs.links[activityLogs.links.length - 1]?.url)"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

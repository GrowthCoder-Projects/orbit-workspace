<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Bell, BellOff, ArrowRight, CheckCheck } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { index as activityIndex } from '@/routes/activity';
import { markAllRead } from '@/routes/notifications';

type Notification = {
    id: string;
    title: string;
    body: string;
    read_at: string | null;
    created_at: string;
};

const props = defineProps<{
    open: boolean;
    notifications: Notification[];
    unreadCount: number;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    read: [];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const unreadNotifications = computed(() => props.notifications.filter((n) => !n.read_at));
const readNotifications = computed(() => props.notifications.filter((n) => n.read_at));

function handleMarkAllRead(): void {
    router.post(markAllRead(), {}, {
        preserveScroll: true,
        onSuccess: () => emit('read'),
    });
}

function formatTime(dateStr: string): string {
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
return `${diffMins}m yang lalu`;
}

    if (diffHours < 24) {
return `${diffHours}j yang lalu`;
}

    return `${diffDays}h yang lalu`;
}
</script>

<template>
    <Sheet v-model:open="isOpen">
        <SheetContent class="flex w-[380px] flex-col p-0 sm:max-w-[380px]">
            <SheetHeader class="border-b px-5 py-4">
                <div class="flex items-center justify-between">
                    <SheetTitle class="flex items-center gap-2 text-base font-semibold">
                        <Bell class="h-4 w-4" />
                        Notifications
                        <Badge
                            v-if="unreadCount > 0"
                            class="ml-1 h-5 min-w-5 rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white"
                        >
                            {{ unreadCount }}
                        </Badge>
                    </SheetTitle>
                    <Button
                        v-if="unreadCount > 0"
                        variant="ghost"
                        size="sm"
                        class="h-8 gap-1.5 text-xs text-muted-foreground"
                        @click="handleMarkAllRead"
                    >
                        <CheckCheck class="h-3.5 w-3.5" />
                        Mark all read
                    </Button>
                </div>
            </SheetHeader>

            <div class="flex-1 overflow-y-auto">
                <!-- Empty state -->
                <div
                    v-if="notifications.length === 0"
                    class="flex flex-col items-center justify-center gap-3 py-16 text-center"
                >
                    <BellOff class="h-10 w-10 text-muted-foreground/40" />
                    <p class="text-sm text-muted-foreground">Tidak ada notifikasi</p>
                </div>

                <!-- Unread notifications -->
                <template v-if="unreadNotifications.length > 0">
                    <div class="px-4 pb-1 pt-3">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                            Belum Dibaca
                        </span>
                    </div>
                    <div
                        v-for="notif in unreadNotifications"
                        :key="notif.id"
                        class="relative border-b border-border/60 bg-blue-500/5 px-5 py-3.5 last:border-b-0"
                    >
                        <div class="absolute left-2 top-4 h-2 w-2 rounded-full bg-blue-500" />
                        <p class="pl-2 text-sm font-medium leading-tight">{{ notif.title }}</p>
                        <p class="mt-0.5 pl-2 text-xs text-muted-foreground">{{ notif.body }}</p>
                        <p class="mt-1 pl-2 text-[11px] text-muted-foreground/60">
                            {{ formatTime(notif.created_at) }}
                        </p>
                    </div>
                </template>

                <!-- Read notifications -->
                <template v-if="readNotifications.length > 0">
                    <div class="px-4 pb-1 pt-3">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                            Sudah Dibaca
                        </span>
                    </div>
                    <div
                        v-for="notif in readNotifications"
                        :key="notif.id"
                        class="border-b border-border/60 px-5 py-3.5 last:border-b-0"
                    >
                        <p class="text-sm font-medium leading-tight text-muted-foreground">{{ notif.title }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground/70">{{ notif.body }}</p>
                        <p class="mt-1 text-[11px] text-muted-foreground/50">
                            {{ formatTime(notif.created_at) }}
                        </p>
                    </div>
                </template>
            </div>

            <!-- Footer -->
            <div class="border-t p-3">
                <Link
                    :href="activityIndex()"
                    class="flex w-full items-center justify-center gap-1.5 rounded-md py-2 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                    @click="isOpen = false"
                >
                    Lihat semua aktivitas
                    <ArrowRight class="h-3.5 w-3.5" />
                </Link>
            </div>
        </SheetContent>
    </Sheet>
</template>

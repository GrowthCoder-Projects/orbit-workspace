<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Bell } from '@lucide/vue';
import { ref, onMounted, onUnmounted } from 'vue';
import { toast } from 'vue-sonner';
import NotificationDrawer from '@/components/NotificationDrawer.vue';
import { Button } from '@/components/ui/button';
import { poll as pollRoute } from '@/routes/notifications';

type Notification = {
    id: string;
    title: string;
    body: string;
    read_at: string | null;
    created_at: string;
};

const drawerOpen = ref(false);
const unreadCount = ref(0);
const notifications = ref<Notification[]>([]);
let pollInterval: ReturnType<typeof setInterval> | null = null;
let lastKnownIds = new Set<string>();

async function fetchNotifications(): Promise<void> {
    try {
        const response = await fetch(pollRoute().url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!response.ok) {
            return;
        }

        const data = await response.json();
        const newUnreadCount: number = data.unread_count;
        const fetchedNotifications: Notification[] = data.notifications;

        // Detect new notifications since last poll
        const newOnes = fetchedNotifications.filter(
            (n) => !lastKnownIds.has(n.id) && !n.read_at,
        );

        if (newOnes.length > 0 && lastKnownIds.size > 0) {
            newOnes.forEach((n) => {
                toast(n.title, { description: n.body });
            });
        }

        lastKnownIds = new Set(fetchedNotifications.map((n) => n.id));
        unreadCount.value = newUnreadCount;
        notifications.value = fetchedNotifications;
    } catch {
        // Silently fail — polling should not disrupt the UI
    }
}

onMounted(() => {
    fetchNotifications();
    pollInterval = setInterval(fetchNotifications, 30000);
});

onUnmounted(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
});
</script>

<template>
    <div class="relative">
        <Button
            variant="ghost"
            size="icon"
            class="relative h-9 w-9 rounded-full"
            @click="drawerOpen = true"
        >
            <Bell class="h-4 w-4" />
            <span
                v-if="unreadCount > 0"
                class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white leading-none"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </Button>

        <NotificationDrawer
            v-model:open="drawerOpen"
            :notifications="notifications"
            :unread-count="unreadCount"
            @read="fetchNotifications"
        />
    </div>
</template>

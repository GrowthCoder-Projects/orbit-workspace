<script setup lang="ts">
import { computed } from 'vue';
import { useSidebar } from '@/components/ui/sidebar';

let sidebar: any;

try {
    sidebar = useSidebar();
} catch (e) {
    // Rendered outside SidebarProvider (e.g., AppHeader)
}

const isCollapsed = computed(() => sidebar?.state.value === 'collapsed');
</script>

<template>
    <div
        class="flex w-full items-center justify-center transition-all duration-200"
        :class="[isCollapsed ? 'h-8 w-8' : 'h-11 w-auto']"
    >
        <!-- Light Mode Logo (Dark text on light bg) -->
        <img
            src="/logo/logo-gc-light.png"
            alt="GrowthCoder"
            class="block object-contain transition-all duration-200 dark:hidden"
            :class="[
                isCollapsed
                    ? 'h-8 w-8 min-w-8 rounded-md object-cover object-left'
                    : 'h-10 w-auto',
            ]"
        />
        <!-- Dark Mode Logo (Light text on dark bg) -->
        <img
            src="/logo/logo-gc-dark.png"
            alt="GrowthCoder"
            class="hidden object-contain transition-all duration-200 dark:block"
            :class="[
                isCollapsed
                    ? 'h-8 w-8 min-w-8 rounded-md object-cover object-left'
                    : 'h-10 w-auto',
            ]"
        />
    </div>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useSidebar } from '@/components/ui/sidebar';

const props = withDefaults(
    defineProps<{
        size?: 'sm' | 'md' | 'lg' | 'xl';
    }>(),
    {
        size: 'md',
    },
);

let sidebar: any;

try {
    sidebar = useSidebar();
} catch (e) {
    // Rendered outside SidebarProvider (e.g., AppHeader)
}

const isCollapsed = computed(() => sidebar?.state.value === 'collapsed');

const page = usePage();
const appLogo = computed(
    () => (page.props.settings as any)?.app_logo || '/storage/logo/logo-orbit.png',
);
const appIcon = computed(
    () => (page.props.settings as any)?.app_icon || '/storage/logo/icon-workspace.png',
);

const activeLogoSrc = computed(() => {
    return isCollapsed.value ? appIcon.value : appLogo.value;
});

const logoHeightClass = computed(() => {
    if (isCollapsed.value) {
        return 'h-8 w-8 min-w-8 rounded-md object-contain';
    }
    switch (props.size) {
        case 'sm':
            return 'h-8 max-w-full w-auto';
        case 'lg':
            return 'h-14 max-w-full w-auto';
        case 'xl':
            return 'h-20 max-w-full w-auto';
        case 'md':
        default:
            return 'h-11 max-w-full w-auto';
    }
});
</script>

<template>
    <div
        class="flex w-full items-center justify-center transition-all duration-200"
        :class="[isCollapsed ? 'h-8 w-8' : 'h-auto w-auto']"
    >
        <img
            :src="activeLogoSrc"
            alt="Logo"
            class="block object-contain transition-all duration-200"
            :class="logoHeightClass"
        />
    </div>
</template>

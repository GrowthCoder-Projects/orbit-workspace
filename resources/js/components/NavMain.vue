<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    label?: string;
    items: NavItem[];
}>();

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();
const { state } = useSidebar();

const isSubmenuActive = (item: NavItem) => {
    if (!item.items) {
return false;
}

    return item.items.some((subItem) => isCurrentOrParentUrl(subItem.href));
};

const isItemActive = (item: NavItem) => {
    if (item.href === '/' || item.href === '/dashboard' || item.href === '/app/dashboard') {
        return isCurrentUrl(item.href);
    }

    return isCurrentOrParentUrl(item.href);
};
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel
            v-if="label"
            class="text-[10px] font-bold uppercase tracking-wider text-sidebar-foreground"
        >
            {{ label }}
        </SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="item.title">
                <!-- If item has sub-items, render collapsible menu -->
                <Collapsible
                    v-if="item.items && item.items.length > 0"
                    as-child
                    :default-open="isSubmenuActive(item)"
                    class="group/collapsible relative"
                >
                    <SidebarMenuItem>
                        <!-- Active Indicator Bar -->
                        <div
                            v-if="
                                isSubmenuActive(item) && state !== 'collapsed'
                            "
                            class="absolute top-1.5 bottom-1.5 left-0 z-10 w-1 rounded-r-full bg-primary"
                        ></div>

                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton
                                :is-active="isSubmenuActive(item)"
                                :tooltip="item.title"
                                class="transition-all duration-200"
                                :class="[
                                    isSubmenuActive(item)
                                        ? 'bg-sidebar-accent/60 pl-4 font-semibold text-primary'
                                        : 'text-sidebar-foreground/75 hover:bg-sidebar-accent/40 hover:text-sidebar-foreground',
                                ]"
                            >
                                <component
                                    :is="item.icon"
                                    class="transition-transform duration-200"
                                    :class="[
                                        isSubmenuActive(item)
                                            ? 'scale-110 text-primary'
                                            : 'text-sidebar-foreground/60 group-hover:text-sidebar-foreground',
                                    ]"
                                />
                                <span>{{ item.title }}</span>
                                <ChevronRight
                                    class="ml-auto size-4 text-sidebar-foreground/45 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                                />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>

                        <CollapsibleContent>
                            <SidebarMenuSub
                                class="ml-5 space-y-0.5 border-l-0 pl-3"
                            >
                                <SidebarMenuSubItem
                                    v-for="subItem in item.items"
                                    :key="subItem.title"
                                    class="relative"
                                >
                                    <SidebarMenuSubButton
                                        as-child
                                        :is-active="isCurrentOrParentUrl(subItem.href)"
                                        class="h-7 py-1 text-xs transition-colors"
                                        :class="[
                                            isCurrentOrParentUrl(subItem.href)
                                                ? 'font-medium text-primary'
                                                : 'text-sidebar-foreground/60 hover:text-sidebar-foreground',
                                        ]"
                                    >
                                        <Link :href="subItem.href">
                                            <span>{{ subItem.title }}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </SidebarMenuItem>
                </Collapsible>

                <!-- Regular Nav Item -->
                <SidebarMenuItem v-else class="relative">
                    <div
                        v-if="isItemActive(item) && state !== 'collapsed'"
                        class="absolute top-1.5 bottom-1.5 left-0 z-10 w-1 rounded-r-full bg-primary"
                    ></div>

                    <SidebarMenuButton
                        as-child
                        :is-active="isItemActive(item)"
                        :tooltip="item.title"
                        class="transition-all duration-200"
                        :class="[
                            isItemActive(item)
                                ? 'bg-sidebar-accent/60 pl-4 font-semibold text-primary'
                                : 'text-sidebar-foreground/75 hover:bg-sidebar-accent/40 hover:text-sidebar-foreground',
                        ]"
                    >
                        <Link :href="item.href">
                            <component
                                :is="item.icon"
                                class="transition-transform duration-200"
                                :class="[
                                    isItemActive(item)
                                        ? 'scale-110 text-primary'
                                        : 'text-sidebar-foreground/60 group-hover:text-sidebar-foreground',
                                ]"
                            />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>

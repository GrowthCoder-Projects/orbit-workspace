<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Home, ChevronRight } from '@lucide/vue';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItemType[];
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();

const formattedBreadcrumbs = computed<BreadcrumbItemType[]>(() => {
    const list: BreadcrumbItemType[] = [...(props.breadcrumbs || [])];
    const currentUrl = page.url || '';

    // If completely empty, default to Dashboard
    if (list.length === 0) {
        return [{ title: 'Dashboard', href: '/app/dashboard' }];
    }

    // Check if root (Dashboard/Home) is already the first item
    const first = list[0];
    const isRootPresent =
        first.title.toLowerCase() === 'dashboard' ||
        first.title.toLowerCase() === 'home' ||
        first.href === '/app/dashboard' ||
        first.href === '/app';

    if (!isRootPresent) {
        list.unshift({ title: 'Dashboard', href: '/app/dashboard' });
    }

    // Smart Category Inference:
    // If intermediate parent category is missing based on route prefix, insert parent item.
    if (currentUrl.startsWith('/app/finance') && !list.some((item) => item.title.toLowerCase() === 'finance')) {
        list.splice(1, 0, { title: 'Finance', href: '/app/finance' });
    } else if (currentUrl.startsWith('/app/projects') && !list.some((item) => item.title.toLowerCase() === 'projects')) {
        list.splice(1, 0, { title: 'Projects', href: '/app/projects' });
    } else if (currentUrl.startsWith('/app/invoices') && !list.some((item) => item.title.toLowerCase() === 'invoices')) {
        list.splice(1, 0, { title: 'Invoices', href: '/app/invoices' });
    } else if (currentUrl.startsWith('/app/settings') && !list.some((item) => item.title.toLowerCase() === 'settings')) {
        list.splice(1, 0, { title: 'Settings', href: '/app/settings/profile' });
    } else if (currentUrl.startsWith('/app/documents') && !list.some((item) => item.title.toLowerCase() === 'documents')) {
        list.splice(1, 0, { title: 'Documents', href: '/app/documents' });
    }

    return list;
});
</script>

<template>
    <Breadcrumb class="py-1">
        <BreadcrumbList class="flex-wrap items-center gap-1 text-xs sm:text-sm">
            <template v-for="(item, index) in formattedBreadcrumbs" :key="index">
                <BreadcrumbItem>
                    <!-- Last item (Current page active indicator) -->
                    <template v-if="index === formattedBreadcrumbs.length - 1">
                        <BreadcrumbPage
                            class="inline-flex max-w-[160px] items-center rounded-md bg-accent/60 px-2.5 py-1 text-xs font-semibold text-foreground shadow-2xs sm:max-w-[280px] dark:bg-accent/40"
                            :title="item.title"
                        >
                            <span class="truncate">{{ item.title }}</span>
                        </BreadcrumbPage>
                    </template>

                    <!-- First item (Dashboard / Root Home) -->
                    <template v-else-if="index === 0">
                        <BreadcrumbLink as-child>
                            <Link
                                :href="item.href"
                                class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium text-muted-foreground transition-all duration-200 hover:bg-muted hover:text-foreground focus-visible:outline-2 focus-visible:outline-ring"
                                :title="item.title"
                            >
                                <Home class="h-3.5 w-3.5 shrink-0 text-muted-foreground/80" />
                                <span class="hidden sm:inline">{{ item.title }}</span>
                            </Link>
                        </BreadcrumbLink>
                    </template>

                    <!-- Intermediate items -->
                    <template v-else>
                        <BreadcrumbLink as-child>
                            <Link
                                :href="item.href"
                                class="inline-flex max-w-[120px] items-center rounded-md px-2 py-1 text-xs font-medium text-muted-foreground transition-all duration-200 hover:bg-muted hover:text-foreground sm:max-w-[180px]"
                                :title="item.title"
                            >
                                <span class="truncate">{{ item.title }}</span>
                            </Link>
                        </BreadcrumbLink>
                    </template>
                </BreadcrumbItem>

                <!-- Separator -->
                <BreadcrumbSeparator
                    v-if="index !== formattedBreadcrumbs.length - 1"
                    class="mx-0.5 shrink-0 text-muted-foreground/40"
                >
                    <ChevronRight class="h-3.5 w-3.5" />
                </BreadcrumbSeparator>
            </template>
        </BreadcrumbList>
    </Breadcrumb>
</template>


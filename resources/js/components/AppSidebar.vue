<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    FolderKanban,
    LayoutGrid,
    Settings,
    ClipboardList,
    Users,
    FileText,
    BookOpen,
    Bookmark,
    Calendar,
    Wallet,
    Receipt,
    Files,
    Activity,
    CheckSquare,
    Code2,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';
import { index as accountsIndex } from '@/routes/accounts';
import { index as activityIndex } from '@/routes/activity';
import { index as assetsIndex } from '@/routes/assets';
import { index as billsIndex } from '@/routes/bills';
import { index as bookmarksIndex } from '@/routes/bookmarks';
import { index as budgetsIndex } from '@/routes/budgets';
import { index as calendarIndex } from '@/routes/calendar';
import { index as clientsIndex } from '@/routes/clients';
import { index as documentsIndex } from '@/routes/documents';
import { index as financeIndex } from '@/routes/finance';
import { index as reportsIndex } from '@/routes/finance/reports';
import { index as habitsIndex } from '@/routes/habits';
import { index as investmentsIndex } from '@/routes/investments';
import { index as invoicesIndex } from '@/routes/invoices';
import { index as kbIndex } from '@/routes/kb';
import { index as notesIndex } from '@/routes/notes';
import { edit as editProfile } from '@/routes/profile';
import { index as projectsIndex } from '@/routes/projects';
import { index as savingsIndex } from '@/routes/savings';
import { index as tasksIndex } from '@/routes/tasks';
import { index as transactionsIndex } from '@/routes/transactions';
import type { NavItem } from '@/types';

const { state } = useSidebar();
const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

const isItemActive = (item: NavItem) => {
    if (item.href === '/' || item.href === '/dashboard' || item.href === '/app/dashboard') {
        return isCurrentUrl(item.href);
    }

    return isCurrentOrParentUrl(item.href);
};

const overviewNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Calendar',
        href: calendarIndex(),
        icon: Calendar,
    },
];

const workNavItems: NavItem[] = [
    {
        title: 'Projects',
        href: projectsIndex(),
        icon: FolderKanban,
    },
    {
        title: 'Tasks',
        href: tasksIndex(),
        icon: ClipboardList,
    },
    {
        title: 'Clients',
        href: clientsIndex(),
        icon: Users,
    },
    {
        title: 'Invoices',
        href: invoicesIndex(),
        icon: Receipt,
    },
];

const personalNavItems: NavItem[] = [
    {
        title: 'Habit Tracker',
        href: habitsIndex(),
        icon: CheckSquare,
    },
    {
        title: 'Finance',
        href: financeIndex(),
        icon: Wallet,
        items: [
            {
                title: 'Overview',
                href: financeIndex(),
            },
            {
                title: 'Rekening',
                href: accountsIndex(),
            },
            {
                title: 'Transaksi Ledger',
                href: transactionsIndex(),
            },
            {
                title: 'Anggaran',
                href: budgetsIndex(),
            },
            {
                title: 'Tabungan & Target',
                href: savingsIndex(),
            },
            {
                title: 'Investasi',
                href: investmentsIndex(),
            },
            {
                title: 'Aset & Utang',
                href: assetsIndex(),
            },
            {
                title: 'Tagihan & Langganan',
                href: billsIndex(),
            },
            {
                title: 'Laporan & Analisis',
                href: reportsIndex(),
            },
        ],
    },
];

const resourceNavItems: NavItem[] = [
    {
        title: 'Documents',
        href: documentsIndex(),
        icon: Files,
    },
    {
        title: 'Notes',
        href: notesIndex(),
        icon: FileText,
    },
    {
        title: 'Knowledge Base',
        href: kbIndex(),
        icon: BookOpen,
    },
    {
        title: 'Bookmarks',
        href: bookmarksIndex(),
        icon: Bookmark,
    },
    {
        title: 'API Docs',
        href: '/app/docs',
        icon: Code2,
    },
];

const secondaryNavItems: NavItem[] = [
    {
        title: 'Activity',
        href: activityIndex(),
        icon: Activity,
    },
    {
        title: 'Settings',
        href: editProfile(),
        icon: Settings,
    },
];

const page = usePage();
const enabledModules = computed(() => {
    return (page.props.settings as any)?.enabled_modules || {
        projects: true,
        tasks: true,
        clients: true,
        invoices: true,
        calendar: true,
        finance: true,
        habits: true,
        documents: true,
        notes: true,
        kb: true,
        bookmarks: true,
    };
});

const filteredOverviewNavItems = computed(() => {
    return overviewNavItems.filter((item) => {
        if (item.title === 'Calendar') return enabledModules.value.calendar !== false;
        return true;
    });
});

const filteredWorkNavItems = computed(() => {
    return workNavItems.filter((item) => {
        if (item.title === 'Projects') return enabledModules.value.projects !== false;
        if (item.title === 'Tasks') return enabledModules.value.tasks !== false;
        if (item.title === 'Clients') return enabledModules.value.clients !== false;
        if (item.title === 'Invoices') return enabledModules.value.invoices !== false;
        return true;
    });
});

const filteredPersonalNavItems = computed(() => {
    return personalNavItems.filter((item) => {
        if (item.title === 'Habit Tracker') return enabledModules.value.habits !== false;
        if (item.title === 'Finance') return enabledModules.value.finance !== false;
        return true;
    });
});

const filteredResourceNavItems = computed(() => {
    return resourceNavItems.filter((item) => {
        if (item.title === 'Documents') return enabledModules.value.documents !== false;
        if (item.title === 'Notes') return enabledModules.value.notes !== false;
        if (item.title === 'Knowledge Base') return enabledModules.value.kb !== false;
        if (item.title === 'Bookmarks') return enabledModules.value.bookmarks !== false;
        return true;
    });
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        class="justify-center hover:bg-transparent active:bg-transparent"
                    >
                        <Link
                            :href="dashboard()"
                            class="flex w-full justify-center"
                        >
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain v-if="filteredOverviewNavItems.length > 0" label="Overview" :items="filteredOverviewNavItems" />
            <NavMain v-if="filteredWorkNavItems.length > 0" label="Work & Projects" :items="filteredWorkNavItems" />
            <NavMain v-if="filteredPersonalNavItems.length > 0" label="Personal & Finance" :items="filteredPersonalNavItems" />
            <NavMain v-if="filteredResourceNavItems.length > 0" label="Knowledge & Storage" :items="filteredResourceNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu class="mb-2 px-2">
                <SidebarMenuItem v-for="item in secondaryNavItems" :key="item.title" class="relative">
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
            </SidebarMenu>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { toast } from 'vue-sonner';
import {
    FolderKanban,
    ClipboardList,
    Users,
    Receipt,
    Calendar,
    Wallet,
    CheckSquare,
    Files,
    FileText,
    BookOpen,
    Bookmark,
} from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

const props = defineProps<{
    enabledModules: {
        projects: boolean;
        tasks: boolean;
        clients: boolean;
        invoices: boolean;
        calendar: boolean;
        finance: boolean;
        habits: boolean;
        documents: boolean;
        notes: boolean;
        kb: boolean;
        bookmarks: boolean;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Features settings',
                href: '/app/settings/features',
            },
        ],
    },
});

const form = useForm({
    projects: props.enabledModules.projects ?? true,
    tasks: props.enabledModules.tasks ?? true,
    clients: props.enabledModules.clients ?? true,
    invoices: props.enabledModules.invoices ?? true,
    calendar: props.enabledModules.calendar ?? true,
    finance: props.enabledModules.finance ?? true,
    habits: props.enabledModules.habits ?? true,
    documents: props.enabledModules.documents ?? true,
    notes: props.enabledModules.notes ?? true,
    kb: props.enabledModules.kb ?? true,
    bookmarks: props.enabledModules.bookmarks ?? true,
});

const toggleModule = (key: keyof typeof form.data) => {
    if (form.processing) return;
    
    // Toggle locally first
    (form as any)[key] = !(form as any)[key];
    
    form.patch('/app/settings/features', {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Pengaturan fitur diperbarui.');
        },
        onError: () => {
            // Revert on error
            (form as any)[key] = !(form as any)[key];
            toast.error('Gagal memperbarui pengaturan.');
        },
    });
};

const categories = [
    {
        title: 'Work & Productivity',
        description: 'Kelola proyek, tugas, direktori klien, dan penagihan.',
        modules: [
            {
                key: 'projects' as const,
                title: 'Projects',
                description: 'Kelola proyek, papan kerja, dan pencapaian (milestones).',
                icon: FolderKanban,
            },
            {
                key: 'tasks' as const,
                title: 'Tasks',
                description: 'Daftar tugas (todo list) dengan checklist penunjang.',
                icon: ClipboardList,
            },
            {
                key: 'clients' as const,
                title: 'Clients',
                description: 'Manajemen basis data dan detail informasi klien.',
                icon: Users,
            },
            {
                key: 'invoices' as const,
                title: 'Invoices',
                description: 'Pembuatan, pelacakan status, dan cetak invoice pembayaran.',
                icon: Receipt,
            },
        ],
    },
    {
        title: 'Personal & Utilities',
        description: 'Pantau jadwal, keuangan pribadi, dan kebiasaan harian Anda.',
        modules: [
            {
                key: 'calendar' as const,
                title: 'Calendar',
                description: 'Kalender interaktif untuk menjadwalkan agenda dan event.',
                icon: Calendar,
            },
            {
                key: 'finance' as const,
                title: 'Finance',
                description: 'Buku kas, anggaran belanja, tabungan, investasi, dan laporan keuangan.',
                icon: Wallet,
            },
            {
                key: 'habits' as const,
                title: 'Habit Tracker',
                description: 'Pelacak kebiasaan harian untuk membangun rutinitas positif.',
                icon: CheckSquare,
            },
        ],
    },
    {
        title: 'Knowledge & Storage',
        description: 'Pusat penyimpanan dokumen, catatan personal, dan bookmark web.',
        modules: [
            {
                key: 'documents' as const,
                title: 'Documents',
                description: 'Penyimpanan file dan dokumen dengan manajemen versi.',
                icon: Files,
            },
            {
                key: 'notes' as const,
                title: 'Notes',
                description: 'Aplikasi catatan dengan editor teks kaya (Rich Text Editor).',
                icon: FileText,
            },
            {
                key: 'kb' as const,
                title: 'Knowledge Base',
                description: 'Pusat dokumentasi, artikel panduan, dan basis pengetahuan.',
                icon: BookOpen,
            },
            {
                key: 'bookmarks' as const,
                title: 'Bookmarks',
                description: 'Penyimpan tautan web favorit dan pengelompokan kategori.',
                icon: Bookmark,
            },
        ],
    },
];
</script>

<template>
    <Head title="Manajemen Fitur" />

    <div class="space-y-6">
        <Heading
            title="Manajemen Fitur"
            description="Aktifkan atau nonaktifkan modul aplikasi secara dinamis untuk menyesuaikan produktivitas Anda."
        />

        <div class="space-y-8">
            <div v-for="category in categories" :key="category.title" class="space-y-4">
                <div class="border-b pb-2">
                    <h2 class="text-lg font-semibold tracking-tight text-foreground">{{ category.title }}</h2>
                    <p class="text-sm text-muted-foreground">{{ category.description }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <Card
                        v-for="mod in category.modules"
                        :key="mod.key"
                        class="transition-all duration-200 hover:shadow-md"
                        :class="{ 'opacity-70 bg-muted/40': !form[mod.key] }"
                    >
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="rounded-lg p-2"
                                    :class="form[mod.key] ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'"
                                >
                                    <component :is="mod.icon" class="h-5 w-5" />
                                </div>
                                <CardTitle class="text-base font-semibold">{{ mod.title }}</CardTitle>
                            </div>
                            <Switch
                                :checked="form[mod.key]"
                                :disabled="form.processing"
                                @update:checked="toggleModule(mod.key)"
                            />
                        </CardHeader>
                        <CardContent>
                            <CardDescription class="text-xs leading-relaxed">
                                {{ mod.description }}
                            </CardDescription>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </div>
</template>

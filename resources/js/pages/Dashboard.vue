<script setup lang="ts">
import { Head, usePage, useForm, router, Link } from '@inertiajs/vue3';
import {
    FolderKanban,
    Receipt,
    Coins,
    CalendarDays,
    Plus,
    FileText,
    CheckSquare,
    CheckCircle2,
    Clock,
    Sparkles,
    ArrowUpRight,
    Loader2,
    Check,
    TrendingUp,
    ListTodo,
    ChevronRight,
    Flame,
    CalendarOff,
    CheckCheck,
    AlertCircle,
    Play,
    Square,
    Pause,
    Timer,
} from '@lucide/vue';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    ArcElement,
    BarElement,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Filler,
} from 'chart.js';
import { ref, computed, watch, onMounted } from 'vue';
import { Doughnut, Bar, Line } from 'vue-chartjs';
import { toast } from 'vue-sonner';
import TaskModal from '@/components/TaskModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

// Register ChartJS components
ChartJS.register(
    Title,
    Tooltip,
    Legend,
    ArcElement,
    BarElement,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Filler
);

// Type definitions
type ChecklistItem = {
    id: number;
    task_id: number;
    item_text: string;
    is_completed: boolean;
    sort_order: number;
};

type ProjectType = {
    id: number;
    name: string;
    color: string | null;
    milestones?: {
        id: number;
        title: string;
        description: string | null;
        due_date: string;
        status: 'pending' | 'completed';
    }[];
};

type TaskType = {
    id: number;
    project_id: number | null;
    project_milestone_id: number | null;
    project?: ProjectType | null;
    project_milestone?: {
        id: number;
        title: string;
    } | null;
    title: string;
    description: string | null;
    status: 'todo' | 'in_progress' | 'blocked' | 'done';
    priority: 'low' | 'medium' | 'high' | 'urgent';
    due_date: string | null;
    tags: string[] | null;
    is_recurring: boolean;
    recurrence_rule: string | null;
    checklists: ChecklistItem[];
    checklist_progress?: string;
};

type ScheduleItem = {
    id: number;
    title: string;
    type: 'event' | 'task' | 'milestone';
    start_time: string;
    end_time: string;
    is_all_day?: boolean;
    color?: string;
    status?: string;
    priority?: string;
    project_name?: string;
    project_color?: string;
    description?: string;
    duration?: string;
};

type BillType = {
    id: number;
    name: string;
    type: 'bill' | 'subscription';
    amount: number;
    currency: string;
    due_day: number;
    is_active: boolean;
    category?: {
        name: string;
        color: string | null;
    };
};

type FolderType = {
    id: number;
    name: string;
};

type ClientType = {
    id: number;
    name: string;
};

type NoteItem = {
    id: number;
    title: string;
    content: string;
    preview: string;
    updated_at: string;
};

const props = defineProps<{
    stats: {
        activeProjectsCount: number;
        pendingTasksCount: number;
        netWorthIDR: number;
        todayEventsCount: number;
        totalIncomeThisMonth: number;
        totalExpenseThisMonth: number;
    };
    todaySchedule: ScheduleItem[];
    activeTasks: TaskType[];
    weeklyChartData: {
        labels: string[];
        completedTasks: number[];
        focusHours: number[];
    };
    expenseChartData: any;
    financeTrendChartData: any;
    upcomingBills: BillType[];
    invoiceStats: {
        paid: { count: number; total: number };
        sent: { count: number; total: number };
        overdue: { count: number; total: number };
    };
    quickDraftNote: {
        id: number;
        title: string;
        content: string;
    };
    recentNotes: NoteItem[];
    projects: ProjectType[];
    clients: ClientType[];
    folders: FolderType[];
    nextInvoiceNumber: string;
    todayHabits: {
        id: number;
        name: string;
        frequency_type: 'daily' | 'weekly' | 'custom_days';
        frequency_days?: string[];
        frequency_count: number;
        color_accent: 'emerald' | 'indigo' | 'amber' | 'violet' | 'rose';
        streak_current: number;
        logs: { id: number; completed_date: string }[];
    }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: '/app/dashboard',
            },
        ],
    },
});

// Dynamic Greeting & Time
const page = usePage();
const userName = computed(() => page.props.auth?.user?.name || 'Developer');
const greeting = ref('');
const hours = ref('00');
const minutes = ref('00');
const seconds = ref('00');

const quotes = [
    { text: "First, solve the problem. Then, write the code.", author: "John Johnson" },
    { text: "Make it work, make it right, make it fast.", author: "Kent Beck" },
    { text: "Talk is cheap. Show me the code.", author: "Linus Torvalds" },
    { text: "Java is to JavaScript what car is to Carpet.", author: "Chris Heilmann" },
    { text: "Simplicity is the soul of efficiency.", author: "Austin Freeman" },
    { text: "Before software can be reusable it first has to be usable.", author: "Ralph Johnson" }
];
const randomQuote = ref(quotes[0]);

const updateGreetingAndTime = () => {
    const nowTime = new Date();
    const hr = nowTime.getHours();

    if (hr >= 4 && hr < 12) {
        greeting.value = 'Selamat Pagi';
    } else if (hr >= 12 && hr < 15) {
        greeting.value = 'Selamat Siang';
    } else if (hr >= 15 && hr < 19) {
        greeting.value = 'Selamat Sore';
    } else {
        greeting.value = 'Selamat Malam';
    }

    hours.value = String(hr).padStart(2, '0');
    minutes.value = String(nowTime.getMinutes()).padStart(2, '0');
    seconds.value = String(nowTime.getSeconds()).padStart(2, '0');
};

import { toggle as toggleHabitRoute } from '@/routes/habits';

const toggleDashboardHabit = (habitId: number) => {
    const todayStr = new Date().toISOString().substring(0, 10);
    router.post(toggleHabitRoute.url({ habit: habitId }), {
        date: todayStr
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Habit updated.');
        }
    });
};

const isHabitCompletedToday = (habit: any) => {
    return habit.logs.length > 0;
};

const colorClasses = {
    emerald: { text: 'text-emerald-500', bg: 'bg-emerald-500', bgLight: 'bg-emerald-500/10' },
    indigo: { text: 'text-indigo-500', bg: 'bg-indigo-500', bgLight: 'bg-indigo-500/10' },
    amber: { text: 'text-amber-500', bg: 'bg-amber-500', bgLight: 'bg-amber-500/10' },
    violet: { text: 'text-violet-500', bg: 'bg-violet-500', bgLight: 'bg-violet-500/10' },
    rose: { text: 'text-rose-500', bg: 'bg-rose-500', bgLight: 'bg-rose-500/10' },
};

onMounted(() => {
    updateGreetingAndTime();
    setInterval(updateGreetingAndTime, 1000);
    randomQuote.value = quotes[Math.floor(Math.random() * quotes.length)];
});

// Currency formatting
const formatIDR = (val: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(val);
};

// Task Edit Modal State
const isTaskModalOpen = ref(false);
const selectedTask = ref<TaskType | null>(null);

const openTaskEdit = (task: TaskType) => {
    selectedTask.value = task;
    isTaskModalOpen.value = true;
};

const openTaskCreate = () => {
    selectedTask.value = null;
    isTaskModalOpen.value = true;
};

// Check/Uncheck Task directly from Dashboard
const toggleTaskStatus = (task: TaskType) => {
    const nextStatus = task.status === 'done' ? 'todo' : 'done';
    router.patch(`/app/tasks/${task.id}`, {
        status: nextStatus,
        title: task.title,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Tugas ditandai sebagai ${nextStatus === 'done' ? 'selesai' : 'belum selesai'}`);
        }
    });
};

// Quick Note Auto-save Logic
const stripHtml = (html: string) => {
    if (!html) {
return '';
}

    const doc = new DOMParser().parseFromString(html, 'text/html');

    return doc.body.textContent || '';
};

const textareaContent = ref(stripHtml(props.quickDraftNote?.content || ''));
const isSavingNote = ref(false);
const isSavedNote = ref(false);
let saveTimeout: any = null;

watch(textareaContent, (newVal) => {
    isSavingNote.value = true;
    isSavedNote.value = false;

    if (saveTimeout) {
clearTimeout(saveTimeout);
}
    
    saveTimeout = setTimeout(() => {
        router.patch(
            `/app/notes/${props.quickDraftNote.id}`,
            {
                title: 'Quick Draft',
                content: `<p>${newVal.replace(/\n/g, '<br>')}</p>`,
            },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    isSavingNote.value = false;
                    isSavedNote.value = true;
                    setTimeout(() => {
                        isSavedNote.value = false;
                    }, 2000);
                },
                onError: () => {
                    isSavingNote.value = false;
                    toast.error('Gagal menyimpan catatan otomatis');
                }
            }
        );
    }, 1200);
});

// Dialogue Form States
const isNoteModalOpen = ref(false);
const noteForm = useForm({
    title: '',
    folder_id: '',
});

const submitNoteForm = () => {
    noteForm.post('/app/notes', {
        onSuccess: () => {
            isNoteModalOpen.value = false;
            noteForm.reset();
            toast.success('Catatan berhasil ditambahkan!');
        }
    });
};

const isInvoiceModalOpen = ref(false);
const invoiceForm = useForm({
    client_id: '',
    project_id: '',
    invoice_number: props.nextInvoiceNumber || '',
    status: 'draft',
    issue_date: new Date().toISOString().split('T')[0],
    due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    currency: 'IDR',
    discount_type: 'fixed',
    template_name: 'modern',
    color_accent: '#3b82f6',
    font_family: 'Inter',
    spacing: 'cozy',
    items: [
        {
            description: 'Project Services / Jasa Project',
            quantity: 1,
            unit_price: 0,
        }
    ]
});

const submitInvoiceForm = () => {
    invoiceForm.post('/app/invoices', {
        onSuccess: () => {
            isInvoiceModalOpen.value = false;
            invoiceForm.reset();
            toast.success('Draft invoice berhasil dibuat!');
        }
    });
};

// Focus Timer Logic
const activeTimerTaskId = ref<number | null>(null);
const timerSeconds = ref<number>(0);
const timerStartedAt = ref<string | null>(null);
let timerInterval: any = null;

const startFocusTimer = (taskId: number) => {
    if (activeTimerTaskId.value === taskId) return;
    if (activeTimerTaskId.value !== null) {
        stopFocusTimer();
    }
    activeTimerTaskId.value = taskId;
    timerSeconds.value = 0;
    timerStartedAt.value = new Date().toISOString();
    timerInterval = setInterval(() => {
        timerSeconds.value++;
    }, 1000);
    toast.info('Timer Fokus Dimulai! Selamat bekerja 🔥');
};

const stopFocusTimer = () => {
    if (!activeTimerTaskId.value || !timerStartedAt.value) return;
    const taskId = activeTimerTaskId.value;
    const duration = timerSeconds.value;
    const startedAt = timerStartedAt.value;
    const endedAt = new Date().toISOString();

    clearInterval(timerInterval);
    timerInterval = null;
    activeTimerTaskId.value = null;
    timerSeconds.value = 0;

    if (duration < 5) {
        toast.info('Durasi timer terlalu singkat (< 5 detik). Waktu tidak disimpan.');
        return;
    }

    router.post(
        `/app/tasks/${taskId}/time-logs`,
        {
            started_at: startedAt,
            ended_at: endedAt,
            duration_seconds: duration,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`Waktu fokus (${formatSeconds(duration)}) berhasil dicatat!`);
            },
        }
    );
};

const formatSeconds = (sec: number) => {
    const hrs = Math.floor(sec / 3600);
    const mins = Math.floor((sec % 3600) / 60);
    const secs = sec % 60;
    if (hrs > 0) {
        return `${hrs}:${mins < 10 ? '0' : ''}${mins}:${secs < 10 ? '0' : ''}${secs}`;
    }
    return `${mins < 10 ? '0' : ''}${mins}:${secs < 10 ? '0' : ''}${secs}`;
};

// Weekly overview mixed chart configurations
const weeklyChartData = computed(() => {
    return {
        labels: props.weeklyChartData?.labels || ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
        datasets: [
            {
                type: 'bar' as const,
                label: 'Tugas Selesai',
                data: props.weeklyChartData?.completedTasks || [12, 8, 14, 9, 16, 11, 15],
                backgroundColor: 'rgba(99, 102, 241, 0.8)',
                borderColor: 'rgb(99, 102, 241)',
                borderWidth: 1,
                borderRadius: 4,
                yAxisID: 'y',
            },
            {
                type: 'line' as const,
                label: 'Waktu Fokus (jam)',
                data: props.weeklyChartData?.focusHours || [5, 3.5, 6.5, 4, 6, 5, 6],
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                pointRadius: 3,
                yAxisID: 'y1',
            }
        ]
    };
});

const weeklyChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        y: {
            type: 'linear' as const,
            display: true,
            position: 'left' as const,
            grid: {
                color: 'rgba(228, 228, 231, 0.2)',
            },
            ticks: {
                color: '#71717a',
                font: { family: 'Inter, sans-serif', size: 10 },
            },
        },
        y1: {
            type: 'linear' as const,
            display: true,
            position: 'right' as const,
            grid: {
                drawOnChartArea: false,
            },
            ticks: {
                color: '#71717a',
                font: { family: 'Inter, sans-serif', size: 10 },
                callback: (val: any) => val + 'j',
            },
        },
        x: {
            grid: {
                display: false,
            },
            ticks: {
                color: '#71717a',
                font: { family: 'Inter, sans-serif', size: 10 },
            },
        }
    },
    plugins: {
        legend: {
            position: 'top' as const,
            labels: {
                color: '#71717a',
                boxWidth: 8,
                font: { family: 'Inter, sans-serif', size: 11 },
            },
        },
    },
};

// Finance Trend mini chart configurations
const financeTrendChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        y: { display: false },
        x: { display: false },
    },
    plugins: {
        legend: { display: false },
        tooltip: { enabled: true },
    },
    elements: {
        point: { radius: 0, hoverRadius: 4 },
    }
};

const noteColors = [
    { bg: 'bg-purple-500/10 border-purple-500/20 text-purple-700 dark:text-purple-300', tag: 'bg-purple-500/20' },
    { bg: 'bg-amber-500/10 border-amber-500/20 text-amber-700 dark:text-amber-300', tag: 'bg-amber-500/20' },
    { bg: 'bg-sky-500/10 border-sky-500/20 text-sky-700 dark:text-sky-300', tag: 'bg-sky-500/20' },
];
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-6">
        <!-- 1. Banner Sapaan Modern -->
        <div class="relative overflow-hidden rounded-2xl border border-zinc-200/60 dark:border-zinc-800/80 bg-white/80 dark:bg-zinc-950/80 backdrop-blur-md p-6 md:p-8 text-zinc-900 dark:text-zinc-50 shadow-sm transition-all hover:shadow-md">
            <div class="absolute -right-16 -top-16 w-64 h-64 bg-zinc-100 dark:bg-zinc-900/40 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-zinc-100 dark:bg-zinc-900/40 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <Sparkles class="h-4 w-4 text-violet-500 animate-pulse" />
                        <span class="text-xs font-semibold tracking-wider uppercase text-zinc-500 dark:text-zinc-400">Workspace Dashboard</span>
                    </div>
                    <h1 class="text-3xl font-extrabold tracking-tight bg-gradient-to-r from-zinc-900 via-zinc-800 to-zinc-700 dark:from-zinc-100 dark:via-zinc-200 dark:to-zinc-300 bg-clip-text text-transparent">
                        {{ greeting }}, {{ userName }}!
                    </h1>
                    <p class="text-zinc-500 dark:text-zinc-400 mt-2 text-sm max-w-xl italic">
                        "{{ randomQuote.text }}" <span class="font-semibold text-xs not-italic text-zinc-400">— {{ randomQuote.author }}</span>
                    </p>
                </div>
                
                <div class="flex flex-col items-start md:items-end justify-center bg-zinc-100/80 dark:bg-zinc-900/80 backdrop-blur-md border border-zinc-200/60 dark:border-zinc-800/80 rounded-xl px-5 py-3.5 self-stretch md:self-auto min-w-[170px] transition-all hover:bg-zinc-200/20 dark:hover:bg-zinc-900">
                    <div class="flex items-center gap-1.5 text-xs font-bold tracking-wide text-zinc-500 dark:text-zinc-400 mb-1">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-500 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        Waktu Lokal
                    </div>
                    <div class="flex items-baseline font-mono tracking-wider font-extrabold text-3xl text-zinc-900 dark:text-zinc-50">
                        <span>{{ hours }}</span>
                        <span class="animate-pulse mx-0.5 text-zinc-400 dark:text-zinc-600">:</span>
                        <span>{{ minutes }}</span>
                        <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 ml-1.5 align-baseline select-none">{{ seconds }}</span>
                    </div>
                    <span class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 mt-1 uppercase tracking-widest">{{ new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short' }) }}</span>
                </div>
            </div>
        </div>

        <!-- 2. KPI Summary Grid (4 Columns) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Tugas -->
            <Link href="/app/tasks" class="group transition-all duration-300 hover:shadow-md hover:-translate-y-0.5">
                <Card class="h-full border-zinc-200/60 dark:border-zinc-800/80 bg-white dark:bg-zinc-950/60 backdrop-blur-md relative overflow-hidden p-6">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold tracking-wider text-zinc-500 uppercase dark:text-zinc-400">Tugas</span>
                            <div class="text-3xl font-extrabold text-zinc-900 dark:text-zinc-50">
                                {{ stats.pendingTasksCount }}
                            </div>
                            <span class="text-xs text-zinc-400">Tugas tertunda</span>
                        </div>
                        <div class="p-3 rounded-xl bg-indigo-500/10 text-indigo-500 group-hover:scale-110 transition-transform duration-300">
                            <ListTodo class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-1.5 text-[11px] font-medium text-emerald-600 dark:text-emerald-400">
                        <TrendingUp class="h-3.5 w-3.5" />
                        <span>20% dari kemarin</span>
                    </div>
                </Card>
            </Link>

            <!-- Kalender -->
            <Link href="/app/calendar" class="group transition-all duration-300 hover:shadow-md hover:-translate-y-0.5">
                <Card class="h-full border-zinc-200/60 dark:border-zinc-800/80 bg-white dark:bg-zinc-950/60 backdrop-blur-md relative overflow-hidden p-6">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold tracking-wider text-zinc-500 uppercase dark:text-zinc-400">Kalender</span>
                            <div class="text-3xl font-extrabold text-zinc-900 dark:text-zinc-50">
                                {{ stats.todayEventsCount }}
                            </div>
                            <span class="text-xs text-zinc-400">Acara hari ini</span>
                        </div>
                        <div class="p-3 rounded-xl bg-blue-500/10 text-blue-500 group-hover:scale-110 transition-transform duration-300">
                            <CalendarDays class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-1.5 text-[11px] font-medium text-blue-600 dark:text-blue-400">
                        <Clock class="h-3.5 w-3.5" />
                        <span>1 acara mendatang</span>
                    </div>
                </Card>
            </Link>

            <!-- Proyek -->
            <Link href="/app/projects" class="group transition-all duration-300 hover:shadow-md hover:-translate-y-0.5">
                <Card class="h-full border-zinc-200/60 dark:border-zinc-800/80 bg-white dark:bg-zinc-950/60 backdrop-blur-md relative overflow-hidden p-6">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold tracking-wider text-zinc-500 uppercase dark:text-zinc-400">Proyek</span>
                            <div class="text-3xl font-extrabold text-zinc-900 dark:text-zinc-50">
                                {{ stats.activeProjectsCount }}
                            </div>
                            <span class="text-xs text-zinc-400">Proyek aktif</span>
                        </div>
                        <div class="p-3 rounded-xl bg-emerald-500/10 text-emerald-500 group-hover:scale-110 transition-transform duration-300">
                            <FolderKanban class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-1.5 text-[11px] font-medium text-amber-600 dark:text-amber-400">
                        <AlertCircle class="h-3.5 w-3.5" />
                        <span>2 berisiko</span>
                    </div>
                </Card>
            </Link>

            <!-- Keuangan -->
            <Link href="/app/finance" class="group transition-all duration-300 hover:shadow-md hover:-translate-y-0.5">
                <Card class="h-full border-zinc-200/60 dark:border-zinc-800/80 bg-white dark:bg-zinc-950/60 backdrop-blur-md relative overflow-hidden p-6">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1 min-w-0 flex-1">
                            <span class="text-xs font-semibold tracking-wider text-zinc-500 uppercase dark:text-zinc-400">Keuangan</span>
                            <div class="text-xl font-extrabold text-zinc-900 dark:text-zinc-50 truncate mt-1">
                                {{ formatIDR(stats.netWorthIDR) }}
                            </div>
                            <span class="text-xs text-zinc-400">Total saldo</span>
                        </div>
                        <div class="p-3 rounded-xl bg-amber-500/10 text-amber-500 shrink-0 group-hover:scale-110 transition-transform duration-300">
                            <Coins class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-1.5 text-[11px] font-medium text-emerald-600 dark:text-emerald-400">
                        <ArrowUpRight class="h-3.5 w-3.5" />
                        <span>8% dari bulan lalu</span>
                    </div>
                </Card>
            </Link>
        </div>

        <!-- 3. Middle Row (Two Columns: Weekly Overview + Today's Schedule) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Weekly Chart (spans 2) -->
            <Card class="lg:col-span-2 border-zinc-200/60 dark:border-zinc-800/80 bg-white/50 dark:bg-zinc-950/50 backdrop-blur-md flex flex-col">
                <CardHeader class="flex flex-row items-center justify-between pb-4">
                    <div>
                        <CardTitle class="text-base font-bold flex items-center gap-2">
                            <TrendingUp class="h-4 w-4 text-indigo-500" />
                            Ikhtisar Pekan Ini
                        </CardTitle>
                        <CardDescription class="text-xs">Rasio penyelesaian tugas vs waktu fokus produktivitas.</CardDescription>
                    </div>
                    <Select defaultValue="this-week">
                        <SelectTrigger class="w-32 h-8 text-xs">
                            <SelectValue placeholder="Pilih Waktu" />
                        </SelectTrigger>
                        <SelectContent class="text-xs">
                            <SelectItem value="this-week">Pekan Ini</SelectItem>
                            <SelectItem value="last-week">Pekan Lalu</SelectItem>
                        </SelectContent>
                    </Select>
                </CardHeader>
                <CardContent class="flex-1 min-h-[260px] pb-6">
                    <Bar :data="weeklyChartData" :options="weeklyChartOptions as any" />
                </CardContent>
            </Card>

            <!-- Today's Schedule -->
            <Card class="border-zinc-200/60 dark:border-zinc-800/80 bg-white/50 dark:bg-zinc-950/50 backdrop-blur-md flex flex-col">
                <CardHeader class="flex flex-row items-center justify-between pb-3">
                    <div>
                        <CardTitle class="text-base font-bold flex items-center gap-2">
                            <CalendarDays class="h-4 w-4 text-blue-500" />
                            Jadwal Hari Ini
                        </CardTitle>
                        <CardDescription class="text-xs">Agenda, deadline, dan kegiatan hari ini.</CardDescription>
                    </div>
                    <Button variant="outline" size="sm" class="h-7 text-[11px] px-2" as-child>
                        <Link href="/app/calendar">Lihat Kalender</Link>
                    </Button>
                </CardHeader>
                <CardContent class="flex-1 pb-6 overflow-y-auto max-h-[300px]">
                    <div v-if="todaySchedule.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
                        <div class="p-3 rounded-full bg-zinc-100 dark:bg-zinc-900 text-zinc-400 mb-2">
                            <CalendarOff class="h-6 w-6" />
                        </div>
                        <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Hari ini bebas dari agenda</p>
                    </div>
                    <div v-else class="relative pl-4 border-l border-zinc-200 dark:border-zinc-800 ml-2 space-y-4 pt-1">
                        <div v-for="item in todaySchedule" :key="item.id" class="relative group">
                            <!-- timeline marker -->
                            <div class="absolute -left-[21px] top-1.5 h-2.5 w-2.5 rounded-full border-2 bg-white dark:bg-zinc-950 transition-all group-hover:scale-125"
                                 :style="{ borderColor: item.color || '#3b82f6' }"></div>
                            
                            <div class="flex items-center justify-between gap-3 p-2.5 rounded-lg bg-zinc-50/50 dark:bg-zinc-900/30 border border-zinc-200/40 dark:border-zinc-800/40 hover:bg-zinc-50 dark:hover:bg-zinc-900/50 transition-colors">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap mb-0.5">
                                        <span class="text-[10px] font-bold text-zinc-500 font-mono">{{ item.start_time }}</span>
                                        <span v-if="item.project_name" class="text-[9px] font-medium text-muted-foreground flex items-center gap-1">
                                            <span class="w-1 h-1 rounded-full" :style="{ backgroundColor: item.project_color || '#a1a1aa' }"></span>
                                            {{ item.project_name }}
                                        </span>
                                    </div>
                                    <h4 class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 truncate">{{ item.title }}</h4>
                                    <p v-if="item.description" class="text-[10px] text-zinc-400 truncate mt-0.5">{{ item.description }}</p>
                                </div>
                                <span v-if="item.duration" class="shrink-0 text-[10px] font-semibold px-2 py-0.5 rounded bg-muted text-muted-foreground">
                                    {{ item.duration }}
                                </span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- 4. Bottom Row (Four Columns: Recent Tasks, Habits, Finance Summary, Quick Notes) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Col 1: Recent Tasks -->
            <Card class="border-zinc-200/60 dark:border-zinc-800/80 bg-white/50 dark:bg-zinc-950/50 backdrop-blur-md flex flex-col">
                <CardHeader class="flex flex-row items-center justify-between pb-3">
                    <CardTitle class="text-sm font-bold">Tugas Terbaru</CardTitle>
                    <Button variant="ghost" size="sm" class="h-6 text-[10px] px-1.5 text-zinc-400 hover:text-zinc-900" as-child>
                        <Link href="/app/tasks">Lihat Semua</Link>
                    </Button>
                </CardHeader>
                <CardContent class="flex-1 space-y-3 pb-6">
                    <div v-if="activeTasks.length === 0" class="flex flex-col items-center justify-center py-12 text-xs text-zinc-400 gap-1.5">
                        <CheckCheck class="h-5 w-5 text-emerald-500" />
                        <span>Semua tugas selesai</span>
                    </div>
                    <div v-else v-for="task in activeTasks" :key="task.id" class="flex items-start gap-2.5 p-2 rounded-lg border border-zinc-200/30 dark:border-zinc-800/30 hover:bg-zinc-50/50 dark:hover:bg-zinc-900/10 transition-colors">
                        <button @click="toggleTaskStatus(task)" class="mt-0.5 shrink-0 h-4.5 w-4.5 rounded border border-zinc-300 dark:border-zinc-700 flex items-center justify-center hover:border-emerald-500 transition-colors"
                                :class="{ 'bg-emerald-500 border-emerald-500 text-white': task.status === 'done' }">
                            <Check v-if="task.status === 'done'" class="h-3 w-3" />
                        </button>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 leading-snug cursor-pointer hover:underline" @click="openTaskEdit(task)">{{ task.title }}</p>
                            <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                <span v-if="task.project" class="text-[9px] font-semibold px-1 rounded" :style="{ color: task.project.color || '#6b7280', backgroundColor: (task.project.color || '#6b7280') + '15' }">
                                    {{ task.project.name }}
                                </span>
                                <Badge v-if="task.due_date" variant="outline" class="text-[8px] px-1 py-0 h-4 text-zinc-400">
                                    {{ new Date(task.due_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}
                                </Badge>
                                <span v-if="activeTimerTaskId === task.id" class="inline-flex items-center gap-1 text-[9px] font-mono font-bold text-emerald-600 dark:text-emerald-400 animate-pulse bg-emerald-500/10 px-1.5 py-0.5 rounded">
                                    <Timer class="h-3 w-3" />
                                    {{ formatSeconds(timerSeconds) }}
                                </span>
                            </div>
                        </div>
                        <div class="shrink-0">
                            <button v-if="activeTimerTaskId === task.id"
                                    @click.stop="stopFocusTimer"
                                    title="Hentikan & Simpan Waktu Fokus"
                                    class="h-6 w-6 rounded-full bg-rose-500 text-white flex items-center justify-center hover:bg-rose-600 transition-colors shadow-sm">
                                <Square class="h-3 w-3 fill-current" />
                            </button>
                            <button v-else
                                    @click.stop="startFocusTimer(task.id)"
                                    title="Mulai Waktu Fokus"
                                    class="h-6 w-6 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center hover:bg-emerald-500 hover:text-white transition-colors">
                                <Play class="h-3 w-3 fill-current ml-0.5" />
                            </button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Col 2: Habits Overview -->
            <Card class="border-zinc-200/60 dark:border-zinc-800/80 bg-white/50 dark:bg-zinc-950/50 backdrop-blur-md flex flex-col">
                <CardHeader class="flex flex-row items-center justify-between pb-3">
                    <CardTitle class="text-sm font-bold">Kebiasaan Hari Ini</CardTitle>
                    <Button variant="ghost" size="sm" class="h-6 text-[10px] px-1.5 text-zinc-400 hover:text-zinc-900" as-child>
                        <Link href="/app/habits">Kelola</Link>
                    </Button>
                </CardHeader>
                <CardContent class="flex-1 space-y-3 pb-6">
                    <div v-if="todayHabits.length === 0" class="text-center py-12 text-xs text-zinc-400">
                        Tidak ada kebiasaan aktif hari ini.
                    </div>
                    <div v-else v-for="habit in todayHabits" :key="habit.id" class="flex flex-col gap-1.5 p-2.5 rounded-lg border border-zinc-200/30 dark:border-zinc-800/30 hover:bg-zinc-50/50 dark:hover:bg-zinc-900/10 cursor-pointer transition-all"
                         @click="toggleDashboardHabit(habit.id)">
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-1.5 font-semibold">
                                <span class="h-2 w-2 rounded-full animate-none" :class="colorClasses[habit.color_accent]?.bg"></span>
                                <span :class="{ 'line-through text-muted-foreground/60': isHabitCompletedToday(habit) }">{{ habit.name }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span v-if="habit.streak_current > 0" class="flex items-center gap-0.5 text-[10px] text-orange-400 font-bold">
                                    <Flame class="h-3 w-3 fill-orange-500 text-orange-500" />
                                    <span>{{ habit.streak_current }}d</span>
                                </span>
                                <div class="h-4 w-4 rounded border border-zinc-300 dark:border-zinc-700 flex items-center justify-center"
                                     :class="isHabitCompletedToday(habit) ? `${colorClasses[habit.color_accent]?.bg} border-transparent text-white` : ''">
                                    <Check v-if="isHabitCompletedToday(habit)" class="h-2.5 w-2.5 stroke-[3]" />
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Col 3: Finance Summary -->
            <Card class="border-zinc-200/60 dark:border-zinc-800/80 bg-white/50 dark:bg-zinc-950/50 backdrop-blur-md flex flex-col">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-bold">Ringkasan Keuangan</CardTitle>
                    <Select defaultValue="this-month">
                        <SelectTrigger class="w-24 h-6 text-[10px]">
                            <SelectValue placeholder="Bulan" />
                        </SelectTrigger>
                        <SelectContent class="text-[10px]">
                            <SelectItem value="this-month">Bulan Ini</SelectItem>
                        </SelectContent>
                    </Select>
                </CardHeader>
                <CardContent class="flex-1 flex flex-col justify-between gap-4 pb-6">
                    <div class="grid grid-cols-2 gap-2 text-xs border-b border-zinc-100 dark:border-zinc-800 pb-3">
                        <div>
                            <p class="text-[10px] text-zinc-400">Total Pemasukan</p>
                            <p class="font-extrabold text-green-600 dark:text-green-400 mt-0.5 truncate">{{ formatIDR(stats.totalIncomeThisMonth) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-zinc-400">Total Pengeluaran</p>
                            <p class="font-extrabold text-red-500 dark:text-red-400 mt-0.5 truncate">{{ formatIDR(stats.totalExpenseThisMonth) }}</p>
                        </div>
                    </div>
                    <div class="flex-1 flex flex-col justify-between">
                        <div class="mb-2">
                            <p class="text-[10px] text-zinc-400">Saldo Akhir</p>
                            <p class="text-lg font-black text-zinc-800 dark:text-zinc-200 mt-0.5">{{ formatIDR(stats.netWorthIDR) }}</p>
                        </div>
                        <div class="h-16 relative">
                            <Line :data="financeTrendChartData" :options="financeTrendChartOptions" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Col 4: Quick Notes -->
            <Card class="border-zinc-200/60 dark:border-zinc-800/80 bg-white/50 dark:bg-zinc-950/50 backdrop-blur-md flex flex-col">
                <CardHeader class="flex flex-row items-center justify-between pb-3">
                    <CardTitle class="text-sm font-bold">Catatan Cepat</CardTitle>
                    <Button variant="ghost" size="sm" class="h-6 text-[10px] px-1.5 text-zinc-400 hover:text-zinc-900" @click="isNoteModalOpen = true">
                        <Plus class="h-3 w-3 mr-1" /> Baru
                    </Button>
                </CardHeader>
                <CardContent class="flex-1 space-y-3 pb-6">
                    <div v-if="recentNotes.length === 0" class="text-center py-12 text-xs text-zinc-400">
                        Belum ada catatan.
                    </div>
                    <div v-else v-for="(note, idx) in recentNotes" :key="note.id" 
                         :class="['p-3 rounded-xl border flex flex-col justify-between gap-1 transition-all hover:shadow-sm cursor-pointer', noteColors[idx % 3].bg]"
                         @click="router.get('/app/notes', { active_id: note.id })">
                        <div class="flex items-center justify-between text-[10px] font-bold">
                            <span class="truncate pr-2 font-black uppercase tracking-wide">{{ note.title }}</span>
                            <span class="shrink-0 text-zinc-400/80">{{ new Date(note.updated_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}</span>
                        </div>
                        <p class="text-[11px] opacity-80 leading-normal line-clamp-2 mt-1">{{ note.preview || 'Catatan kosong' }}</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- 5. Row Tambahan (Quick Actions & Coretan Cepat) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Quick Actions -->
            <Card class="lg:col-span-1 border-zinc-200/60 dark:border-zinc-800/80 bg-white/50 dark:bg-zinc-950/50 backdrop-blur-md flex flex-col justify-between">
                <CardHeader class="pb-3">
                    <CardTitle class="text-sm font-bold flex items-center gap-2">
                        <Sparkles class="h-4 w-4 text-violet-500" />
                        Aksi Cepat
                    </CardTitle>
                    <CardDescription class="text-xs">Buat tugas, catatan, atau tagihan instan langsung.</CardDescription>
                </CardHeader>
                <CardContent class="grid grid-cols-3 gap-3 pb-6">
                    <Button variant="outline" class="flex flex-col items-center justify-center h-20 gap-2 border-dashed border-zinc-200 hover:border-violet-500 hover:bg-violet-500/5 transition-all text-xs" @click="openTaskCreate">
                        <CheckSquare class="h-5 w-5 text-emerald-500" />
                        <span>Tugas</span>
                    </Button>
                    <Button variant="outline" class="flex flex-col items-center justify-center h-20 gap-2 border-dashed border-zinc-200 hover:border-violet-500 hover:bg-violet-500/5 transition-all text-xs" @click="isNoteModalOpen = true">
                        <FileText class="h-5 w-5 text-blue-500" />
                        <span>Catatan</span>
                    </Button>
                    <Button variant="outline" class="flex flex-col items-center justify-center h-20 gap-2 border-dashed border-zinc-200 hover:border-violet-500 hover:bg-violet-500/5 transition-all text-xs" @click="isInvoiceModalOpen = true">
                        <Receipt class="h-5 w-5 text-amber-500" />
                        <span>Tagihan</span>
                    </Button>
                </CardContent>
            </Card>

            <!-- Coretan Cepat (Autosave text area) -->
            <Card class="lg:col-span-2 border-zinc-200/60 dark:border-zinc-800/80 bg-white/50 dark:bg-zinc-950/50 backdrop-blur-md flex flex-col">
                <CardHeader class="pb-2 flex flex-row items-center justify-between">
                    <div>
                        <CardTitle class="text-sm font-bold flex items-center gap-2">
                            <FileText class="h-4 w-4 text-blue-500" />
                            Coretan Cepat (Quick Draft)
                        </CardTitle>
                        <CardDescription class="text-xs">Menulis coretan ide di sini yang tersimpan otomatis.</CardDescription>
                    </div>
                </CardHeader>
                <CardContent class="relative pb-4 flex-1 min-h-[120px]">
                    <textarea v-model="textareaContent" rows="4" placeholder="Ketik ide/catatan cepat Anda di sini..."
                              class="w-full h-full min-h-[100px] text-xs bg-zinc-50/50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 rounded-lg p-3 focus:outline-none focus:ring-1 focus:ring-violet-500 resize-none font-sans text-zinc-700 dark:text-zinc-300"></textarea>
                    
                    <div class="absolute bottom-6 right-6 flex items-center gap-1.5 bg-white/90 dark:bg-zinc-950/90 border px-2 py-1 rounded-md text-[9px] text-zinc-400">
                        <template v-if="isSavingNote">
                            <Loader2 class="h-3 w-3 animate-spin text-violet-500" />
                            <span>Menyimpan...</span>
                        </template>
                        <template v-else-if="isSavedNote">
                            <CheckCircle2 class="h-3 w-3 text-emerald-500" />
                            <span>Tersimpan</span>
                        </template>
                        <template v-else>
                            <Clock class="h-3 w-3" />
                            <span>Autosave</span>
                        </template>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Task Edit/Create Modal (Reused Component) -->
        <TaskModal
            :task="selectedTask"
            :isOpen="isTaskModalOpen"
            :projects="projects"
            @close="isTaskModalOpen = false"
        />

        <!-- Note Modal (Quick Create) -->
        <Dialog v-model:open="isNoteModalOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Tambah Catatan Baru</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submitNoteForm" class="space-y-4 py-2">
                    <div class="space-y-1">
                        <Label for="note-title" class="text-xs">Judul Catatan</Label>
                        <Input id="note-title" v-model="noteForm.title" placeholder="Judul Catatan..." required class="h-9 text-xs" />
                    </div>
                    <div class="space-y-1">
                        <Label for="note-folder" class="text-xs">Folder (Opsional)</Label>
                        <Select v-model="noteForm.folder_id">
                            <SelectTrigger id="note-folder" class="h-9 text-xs">
                                <SelectValue placeholder="Pilih Folder" />
                            </SelectTrigger>
                            <SelectContent class="text-xs">
                                <SelectItem v-for="folder in folders" :key="folder.id" :value="String(folder.id)">
                                    {{ folder.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <DialogFooter class="pt-3">
                        <Button type="button" variant="outline" size="sm" @click="isNoteModalOpen = false">Batal</Button>
                        <Button type="submit" size="sm" class="bg-violet-600 hover:bg-violet-700 text-white" :disabled="noteForm.processing">
                            <span v-if="noteForm.processing">Membuat...</span>
                            <span v-else>Buat Catatan</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Invoice Modal (Quick Create) -->
        <Dialog v-model:open="isInvoiceModalOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Buat Invoice Baru</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submitInvoiceForm" class="space-y-4 py-2">
                    <div class="space-y-1">
                        <Label for="invoice-client" class="text-xs">Client</Label>
                        <Select v-model="invoiceForm.client_id">
                            <SelectTrigger id="invoice-client" required class="h-9 text-xs">
                                <SelectValue placeholder="Pilih Client" />
                            </SelectTrigger>
                            <SelectContent class="text-xs">
                                <SelectItem v-for="client in clients" :key="client.id" :value="String(client.id)">
                                    {{ client.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="space-y-1">
                        <Label for="invoice-project" class="text-xs">Project (Opsional)</Label>
                        <Select v-model="invoiceForm.project_id">
                            <SelectTrigger id="invoice-project" class="h-9 text-xs">
                                <SelectValue placeholder="Pilih Project" />
                            </SelectTrigger>
                            <SelectContent class="text-xs">
                                <SelectItem v-for="proj in projects" :key="proj.id" :value="String(proj.id)">
                                    {{ proj.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="space-y-1">
                        <Label for="invoice-num" class="text-xs">Nomor Invoice</Label>
                        <Input id="invoice-num" v-model="invoiceForm.invoice_number" required class="h-9 text-xs" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <Label for="invoice-issue" class="text-xs">Tanggal Terbit</Label>
                            <Input id="invoice-issue" type="date" v-model="invoiceForm.issue_date" required class="h-9 text-xs" />
                        </div>
                        <div class="space-y-1">
                            <Label for="invoice-due" class="text-xs">Jatuh Tempo</Label>
                            <Input id="invoice-due" type="date" v-model="invoiceForm.due_date" required class="h-9 text-xs" />
                        </div>
                    </div>
                    <DialogFooter class="pt-3">
                        <Button type="button" variant="outline" size="sm" @click="isInvoiceModalOpen = false">Batal</Button>
                        <Button type="submit" size="sm" class="bg-violet-600 hover:bg-violet-700 text-white" :disabled="invoiceForm.processing">
                            <span v-if="invoiceForm.processing">Membuat...</span>
                            <span v-else>Buat Draft & Edit</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>

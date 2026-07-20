<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const { confirm } = useConfirm();
import {
    ChevronLeft,
    ChevronRight,
    Calendar as CalendarIcon,
    Plus,
    Trash2,
    Clock,
    Bell,
    Check,
    Sparkles,
    FileText,
    History,
    SlidersHorizontal,
} from '@lucide/vue';
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
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useConfirm } from '@/composables/useConfirm';
import { index as calendarIndex } from '@/routes/calendar';
import {
    store as storeEvent,
    update as updateEvent,
    destroy as destroyEvent,
} from '@/routes/calendar/events';

// Define Page Options & Layout
defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Calendar',
                href: calendarIndex(),
            },
        ],
    },
});

type CalendarEvent = {
    id?: number;
    title: string;
    description: string | null;
    start_at: string;
    end_at: string;
    is_all_day: boolean;
    color: string | null;
    recurrence_pattern: 'none' | 'daily' | 'weekly' | 'monthly' | null;
    recurrence_end: string | null;
    reminder_lead_time: number | null;
    is_recurring_instance?: boolean;
};

type Task = {
    id: number;
    title: string;
    description: string | null;
    due_date: string;
    status: string;
    priority: string;
    project?: { name: string; color: string | null } | null;
};

type Milestone = {
    id: number;
    title: string;
    description: string | null;
    due_date: string;
    status: string;
    project?: { name: string; color: string | null } | null;
};

type Notification = {
    id: string;
    data: {
        event_id?: number;
        title: string;
        description: string | null;
        start_at: string;
    };
    created_at: string;
};

const props = defineProps<{
    events: CalendarEvent[];
    tasks: Task[];
    milestones: Milestone[];
    notifications: Notification[];
    filters: { start: string; end: string };
}>();

const currentDate = ref(new Date());
const activeView = ref<'month' | 'week' | 'day' | 'agenda'>('month');
const selectedDate = ref(new Date());

const filterCustom = ref(true);
const filterMilestones = ref(true);
const filterTasks = ref(true);

const isDrawerOpen = ref(false);
const editingEvent = ref<CalendarEvent | null>(null);

const form = useForm({
    title: '',
    description: '',
    start_date: '',
    start_time: '09:00',
    end_date: '',
    end_time: '10:00',
    is_all_day: false,
    color: '#16a34a',
    recurrence_pattern: 'none' as 'none' | 'daily' | 'weekly' | 'monthly',
    recurrence_end: '',
    reminder_lead_time: null as number | null,
});

const colorOptions = [
    {
        label: 'Green',
        value: '#16a34a',
        bg: 'bg-[#16a34a]',
        text: 'text-emerald-500',
        lightBg: 'bg-emerald-500/10',
    },
    {
        label: 'Blue',
        value: '#2563eb',
        bg: 'bg-[#2563eb]',
        text: 'text-blue-500',
        lightBg: 'bg-blue-500/10',
    },
    {
        label: 'Purple',
        value: '#7c3aed',
        bg: 'bg-[#7c3aed]',
        text: 'text-purple-500',
        lightBg: 'bg-purple-500/10',
    },
    {
        label: 'Orange',
        value: '#ea580c',
        bg: 'bg-[#ea580c]',
        text: 'text-orange-500',
        lightBg: 'bg-orange-500/10',
    },
    {
        label: 'Red',
        value: '#e11d48',
        bg: 'bg-[#e11d48]',
        text: 'text-rose-500',
        lightBg: 'bg-rose-500/10',
    },
];

const reminderOptions = [
    { label: 'No Reminder', value: 'null' },
    { label: 'At start of event', value: '0' },
    { label: '5 minutes before', value: '5' },
    { label: '15 minutes before', value: '15' },
    { label: '30 minutes before', value: '30' },
    { label: '1 hour before', value: '60' },
    { label: '2 hours before', value: '120' },
    { label: '1 day before', value: '1440' },
];

const months = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const isSameDay = (d1: Date, d2: Date) => {
    return (
        d1.getFullYear() === d2.getFullYear() &&
        d1.getMonth() === d2.getMonth() &&
        d1.getDate() === d2.getDate()
    );
};

const formatDateString = (date: Date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');

    return `${y}-${m}-${d}`;
};

const getFormattedDateTimeString = (dateString: string) => {
    const d = new Date(dateString);

    if (isNaN(d.getTime())) {
return dateString;
}

    const monthsShort = [
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'May',
        'Jun',
        'Jul',
        'Aug',
        'Sep',
        'Oct',
        'Nov',
        'Dec',
    ];
    const day = d.getDate();
    const month = monthsShort[d.getMonth()];
    const year = d.getFullYear();

    let hours = d.getHours();
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'

    return `${month} ${day}, ${year}, ${hours}:${minutes} ${ampm}`;
};

const itemsByDate = computed(() => {
    const grouped: Record<
        string,
        { events: CalendarEvent[]; tasks: Task[]; milestones: Milestone[] }
    > = {};

    props.events.forEach((event) => {
        const dateStr = event.start_at.substring(0, 10);

        if (!grouped[dateStr]) {
grouped[dateStr] = { events: [], tasks: [], milestones: [] };
}

        grouped[dateStr].events.push(event);
    });

    props.tasks.forEach((task) => {
        const dateStr = task.due_date.substring(0, 10);

        if (!grouped[dateStr]) {
grouped[dateStr] = { events: [], tasks: [], milestones: [] };
}

        grouped[dateStr].tasks.push(task);
    });

    props.milestones.forEach((milestone) => {
        const dateStr = milestone.due_date.substring(0, 10);

        if (!grouped[dateStr]) {
grouped[dateStr] = { events: [], tasks: [], milestones: [] };
}

        grouped[dateStr].milestones.push(milestone);
    });

    return grouped;
});

const fetchCalendarRange = (start: Date, end: Date) => {
    router.get(
        calendarIndex().url,
        {
            start: formatDateString(start),
            end: formatDateString(end),
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['events', 'tasks', 'milestones', 'filters'],
        },
    );
};

const navigateMonth = (direction: number) => {
    const nextDate = new Date(
        currentDate.value.getFullYear(),
        currentDate.value.getMonth() + direction,
        1,
    );
    currentDate.value = nextDate;

    const start = new Date(nextDate.getFullYear(), nextDate.getMonth(), 1);
    const end = new Date(nextDate.getFullYear(), nextDate.getMonth() + 1, 0);
    fetchCalendarRange(start, end);
};

const calendarWeeks = computed(() => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();

    const firstDayIndex = new Date(year, month, 1).getDay();
    const totalDays = new Date(year, month + 1, 0).getDate();
    const prevMonthTotalDays = new Date(year, month, 0).getDate();

    const days = [];

    for (let i = firstDayIndex - 1; i >= 0; i--) {
        const date = new Date(year, month - 1, prevMonthTotalDays - i);
        days.push({
            date,
            isCurrentMonth: false,
            isToday: isSameDay(date, new Date()),
            label: date.getDate().toString(),
            dateString: formatDateString(date),
        });
    }

    for (let i = 1; i <= totalDays; i++) {
        const date = new Date(year, month, i);
        days.push({
            date,
            isCurrentMonth: true,
            isToday: isSameDay(date, new Date()),
            label: i.toString(),
            dateString: formatDateString(date),
        });
    }

    const remainingCells = days.length % 7;

    if (remainingCells > 0) {
        const padCount = 7 - remainingCells;

        for (let i = 1; i <= padCount; i++) {
            const date = new Date(year, month + 1, i);
            days.push({
                date,
                isCurrentMonth: false,
                isToday: isSameDay(date, new Date()),
                label: i.toString(),
                dateString: formatDateString(date),
            });
        }
    }

    const weeks = [];

    for (let i = 0; i < days.length; i += 7) {
        weeks.push(days.slice(i, i + 7));
    }

    return weeks;
});

const getFilteredItems = (dateString: string) => {
    const dayData = itemsByDate.value[dateString];

    if (!dayData) {
return [];
}

    const items: any[] = [];

    if (filterCustom.value) {
dayData.events.forEach((e) => items.push({ ...e, itemType: 'event' }));
}

    if (filterMilestones.value) {
dayData.milestones.forEach((m) =>
            items.push({ ...m, itemType: 'milestone' }),
        );
}

    if (filterTasks.value) {
dayData.tasks.forEach((t) => items.push({ ...t, itemType: 'task' }));
}

    return items.sort((a, b) => {
        const timeA = a.start_at ? a.start_at.substring(11, 16) : '00:00';
        const timeB = b.start_at ? b.start_at.substring(11, 16) : '00:00';

        return timeA.localeCompare(timeB);
    });
};

const navigateToday = () => {
    currentDate.value = new Date();
    selectedDate.value = new Date();
    const start = new Date(
        currentDate.value.getFullYear(),
        currentDate.value.getMonth(),
        1,
    );
    const end = new Date(
        currentDate.value.getFullYear(),
        currentDate.value.getMonth() + 1,
        0,
    );
    fetchCalendarRange(start, end);
};

const openNewEventDrawer = (date: Date = new Date()) => {
    editingEvent.value = null;
    const dateStr = formatDateString(date);
    form.reset();
    form.start_date = dateStr;
    form.end_date = dateStr;
    form.start_time = '09:00';
    form.end_time = '10:00';
    form.is_all_day = false;
    form.color = '#16a34a';
    form.recurrence_pattern = 'none';
    form.recurrence_end = '';
    form.reminder_lead_time = null;
    isDrawerOpen.value = true;
};

const openEditEventDrawer = (event: CalendarEvent) => {
    editingEvent.value = event;
    const startDatePart = event.start_at.substring(0, 10);
    const startTimePart = event.start_at.substring(11, 16);
    const endDatePart = event.end_at.substring(0, 10);
    const endTimePart = event.end_at.substring(11, 16);

    form.reset();
    form.title = event.title;
    form.description = event.description || '';
    form.start_date = startDatePart;
    form.start_time = startTimePart;
    form.end_date = endDatePart;
    form.end_time = endTimePart;
    form.is_all_day = event.is_all_day;
    form.color = event.color || '#16a34a';
    form.recurrence_pattern = event.recurrence_pattern || 'none';
    form.recurrence_end = event.recurrence_end
        ? event.recurrence_end.substring(0, 10)
        : '';
    form.reminder_lead_time = event.reminder_lead_time;
    isDrawerOpen.value = true;
};

const saveEvent = () => {
    const startAt = `${form.start_date} ${form.start_time}:00`;
    const endAt = `${form.end_date} ${form.end_time}:00`;
    const recurrenceEnd = form.recurrence_end
        ? `${form.recurrence_end} 23:59:59`
        : null;

    const payload = {
        title: form.title,
        description: form.description,
        start_at: startAt,
        end_at: endAt,
        is_all_day: form.is_all_day,
        color: form.color,
        recurrence_pattern: form.recurrence_pattern,
        recurrence_end: recurrenceEnd,
        reminder_lead_time: form.reminder_lead_time,
    };

    if (editingEvent.value?.id) {
        router.patch(updateEvent(editingEvent.value.id).url, payload, {
            onSuccess: () => (isDrawerOpen.value = false),
        });
    } else {
        router.post(storeEvent().url, payload, {
            onSuccess: () => (isDrawerOpen.value = false),
        });
    }
};

const deleteEvent = async () => {
    if (editingEvent.value?.id) {
        const isConfirmed = await confirm({
            title: 'Delete Event',
            message: 'Are you sure you want to delete this event?',
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'destructive',
        });

        if (isConfirmed) {
            router.delete(destroyEvent(editingEvent.value.id).url, {
                onSuccess: () => (isDrawerOpen.value = false),
            });
        }
    }
};

const selectedDayEvents = computed(() => {
    return getFilteredItems(formatDateString(selectedDate.value));
});

const selectDay = (date: Date) => {
    selectedDate.value = date;
};

const weekDays = computed(() => {
    const startOfWeek = new Date(selectedDate.value);
    const day = startOfWeek.getDay();
    startOfWeek.setDate(startOfWeek.getDate() - day);
    const days = [];

    for (let i = 0; i < 7; i++) {
        const date = new Date(startOfWeek);
        date.setDate(startOfWeek.getDate() + i);
        days.push({
            date,
            isToday: isSameDay(date, new Date()),
            dateString: formatDateString(date),
            label: weekdays[i],
            dayNumber: date.getDate(),
        });
    }

    return days;
});

const agendaItems = computed(() => {
    const items: any[] = [];
    const sortedDates = Object.keys(itemsByDate.value).sort();
    sortedDates.forEach((dateStr) => {
        getFilteredItems(dateStr).forEach((item) => {
            items.push({
                ...item,
                dateString: dateStr,
                parsedDate: new Date(dateStr),
            });
        });
    });

    return items;
});

const getEventTime = (event: CalendarEvent) => {
    if (event.is_all_day) {
return 'All Day';
}

    return event.start_at.substring(11, 16);
};

const handleReminderChange = (val: string) => {
    form.reminder_lead_time = val === 'null' ? null : parseInt(val, 10);
};

const handleColorSelect = (hex: string) => {
    form.color = hex;
};
</script>

<template>
    <Head title="Calendar" />

    <div
        class="flex h-full flex-col gap-6 bg-neutral-50/40 p-6 lg:flex-row dark:bg-neutral-950/20"
    >
        <!-- Sidebar: Filter & Notifications -->
        <div class="flex w-full shrink-0 flex-col gap-6 lg:w-72">
            <!-- Filter Card -->
            <div
                class="rounded-2xl border border-neutral-100 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-neutral-900/60 dark:bg-neutral-900/80"
            >
                <h3
                    class="mb-5 flex items-center gap-2.5 text-sm font-bold tracking-wide text-neutral-900 uppercase opacity-80 dark:text-neutral-100"
                >
                    <SlidersHorizontal class="size-4 text-primary" />
                    Calendar Filters
                </h3>

                <div class="space-y-4">
                    <label
                        class="flex cursor-pointer items-center justify-between rounded-xl border border-neutral-100 bg-neutral-50/50 p-2.5 transition-all hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-800/10 dark:hover:bg-neutral-800/25"
                    >
                        <span
                            class="flex items-center gap-3 text-sm font-semibold text-neutral-700 dark:text-neutral-300"
                        >
                            <span
                                class="size-3 rounded-full bg-emerald-500 ring-4 ring-emerald-500/10 dark:ring-emerald-500/20"
                            ></span>
                            Custom Events
                        </span>
                        <input
                            type="checkbox"
                            v-model="filterCustom"
                            class="size-4.5 cursor-pointer rounded border-neutral-300 text-emerald-600 accent-emerald-500 focus:ring-emerald-500/40 dark:border-neutral-700"
                        />
                    </label>

                    <label
                        class="flex cursor-pointer items-center justify-between rounded-xl border border-neutral-100 bg-neutral-50/50 p-2.5 transition-all hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-800/10 dark:hover:bg-neutral-800/25"
                    >
                        <span
                            class="flex items-center gap-3 text-sm font-semibold text-neutral-700 dark:text-neutral-300"
                        >
                            <span
                                class="size-3 rounded-full bg-blue-500 ring-4 ring-blue-500/10 dark:ring-blue-500/20"
                            ></span>
                            Milestones
                        </span>
                        <input
                            type="checkbox"
                            v-model="filterMilestones"
                            class="size-4.5 cursor-pointer rounded border-neutral-300 text-blue-600 accent-blue-500 focus:ring-blue-500/40 dark:border-neutral-700"
                        />
                    </label>

                    <label
                        class="flex cursor-pointer items-center justify-between rounded-xl border border-neutral-100 bg-neutral-50/50 p-2.5 transition-all hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-800/10 dark:hover:bg-neutral-800/25"
                    >
                        <span
                            class="flex items-center gap-3 text-sm font-semibold text-neutral-700 dark:text-neutral-300"
                        >
                            <span
                                class="size-3 rounded-full bg-purple-500 ring-4 ring-purple-500/10 dark:ring-purple-500/20"
                            ></span>
                            Tasks Due
                        </span>
                        <input
                            type="checkbox"
                            v-model="filterTasks"
                            class="size-4.5 cursor-pointer rounded border-neutral-300 text-purple-600 accent-purple-500 focus:ring-purple-500/40 dark:border-neutral-700"
                        />
                    </label>
                </div>
            </div>

            <!-- Recent Reminders Card (Timeline Layout) -->
            <div
                class="flex min-h-[320px] flex-1 flex-col rounded-2xl border border-neutral-100 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-neutral-900/60 dark:bg-neutral-900/80"
            >
                <h3
                    class="mb-5 flex items-center gap-2.5 text-sm font-bold tracking-wide text-neutral-900 uppercase opacity-80 dark:text-neutral-100"
                >
                    <History class="size-4 text-yellow-500" />
                    Reminder History
                </h3>

                <div
                    v-if="props.notifications && props.notifications.length > 0"
                    class="relative ml-1 max-h-[420px] flex-1 space-y-4 overflow-y-auto border-l border-neutral-100 pr-1 pl-4 dark:border-neutral-800"
                >
                    <div
                        v-for="notification in props.notifications"
                        :key="notification.id"
                        class="group relative flex flex-col gap-1"
                    >
                        <span
                            class="absolute top-1 -left-[21px] size-2 rounded-full bg-yellow-500 ring-4 ring-yellow-500/10 dark:ring-yellow-500/20"
                        ></span>
                        <span
                            class="line-clamp-2 text-xs font-bold text-neutral-800 dark:text-neutral-200"
                            >{{ notification.data.title }}</span
                        >
                        <div
                            class="flex items-center gap-1.5 text-[10px] font-medium text-neutral-400"
                        >
                            <Clock class="size-3" />
                            <span>{{
                                getFormattedDateTimeString(
                                    notification.created_at,
                                )
                            }}</span>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="flex flex-1 flex-col items-center justify-center p-4 text-center"
                >
                    <div
                        class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-neutral-50 dark:bg-neutral-800/40"
                    >
                        <Bell
                            class="size-6 text-neutral-300 opacity-60 dark:text-neutral-600"
                        />
                    </div>
                    <p class="text-xs font-semibold text-neutral-400">
                        No reminders sent yet.
                    </p>
                </div>
            </div>
        </div>

        <!-- Main Content (Calendar & Events View) -->
        <div class="flex flex-1 flex-col gap-4">
            <!-- Calendar Navigation Header -->
            <div
                class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-neutral-100 bg-white/70 p-4 shadow-sm backdrop-blur-md dark:border-neutral-900/60 dark:bg-neutral-900/80"
            >
                <!-- Navigation -->
                <div class="flex items-center gap-2">
                    <Button
                        variant="ghost"
                        size="icon"
                        @click="navigateMonth(-1)"
                        class="size-9 cursor-pointer rounded-xl"
                    >
                        <ChevronLeft class="size-5" />
                    </Button>
                    <h2
                        class="min-w-[160px] text-center text-lg font-black tracking-tight text-neutral-800 dark:text-neutral-100"
                    >
                        {{ months[currentDate.getMonth()] }}
                        {{ currentDate.getFullYear() }}
                    </h2>
                    <Button
                        variant="ghost"
                        size="icon"
                        @click="navigateMonth(1)"
                        class="size-9 cursor-pointer rounded-xl"
                    >
                        <ChevronRight class="size-5" />
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="navigateToday"
                        class="h-8.5 cursor-pointer rounded-xl px-3.5 font-bold"
                    >
                        Today
                    </Button>
                </div>

                <!-- View Switcher -->
                <div
                    class="flex items-center gap-1 rounded-xl bg-neutral-100/80 p-1 dark:bg-neutral-800/60"
                >
                    <button
                        @click="activeView = 'month'"
                        class="cursor-pointer rounded-lg px-3.5 py-1.5 text-xs font-bold transition-all duration-200"
                        :class="
                            activeView === 'month'
                                ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-700 dark:text-neutral-50'
                                : 'text-neutral-500 hover:text-neutral-800 dark:text-neutral-400'
                        "
                    >
                        Month
                    </button>
                    <button
                        @click="activeView = 'week'"
                        class="cursor-pointer rounded-lg px-3.5 py-1.5 text-xs font-bold transition-all duration-200"
                        :class="
                            activeView === 'week'
                                ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-700 dark:text-neutral-50'
                                : 'text-neutral-500 hover:text-neutral-800 dark:text-neutral-400'
                        "
                    >
                        Week
                    </button>
                    <button
                        @click="activeView = 'day'"
                        class="cursor-pointer rounded-lg px-3.5 py-1.5 text-xs font-bold transition-all duration-200"
                        :class="
                            activeView === 'day'
                                ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-700 dark:text-neutral-50'
                                : 'text-neutral-500 hover:text-neutral-800 dark:text-neutral-400'
                        "
                    >
                        Day
                    </button>
                    <button
                        @click="activeView = 'agenda'"
                        class="cursor-pointer rounded-lg px-3.5 py-1.5 text-xs font-bold transition-all duration-200"
                        :class="
                            activeView === 'agenda'
                                ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-700 dark:text-neutral-50'
                                : 'text-neutral-500 hover:text-neutral-800 dark:text-neutral-400'
                        "
                    >
                        Agenda
                    </button>
                </div>

                <Button
                    size="sm"
                    @click="openNewEventDrawer(new Date())"
                    class="flex h-8.5 cursor-pointer items-center gap-2 rounded-xl bg-gradient-to-r from-primary to-violet-600 font-bold text-white shadow-sm shadow-primary/10"
                >
                    <Plus class="size-4" /> New Event
                </Button>
            </div>

            <!-- Views Container -->
            <div
                class="flex min-h-[500px] flex-1 flex-col overflow-hidden rounded-2xl border border-neutral-100 bg-white/70 p-5 shadow-sm backdrop-blur-md dark:border-neutral-900/60 dark:bg-neutral-900/80"
            >
                <!-- MONTH VIEW -->
                <div
                    v-if="activeView === 'month'"
                    class="flex h-full flex-1 flex-col"
                >
                    <!-- Weekday names -->
                    <div
                        class="mb-2 grid grid-cols-7 pb-3 text-center text-xs font-bold tracking-wider text-neutral-400 uppercase"
                    >
                        <div v-for="day in weekdays" :key="day">{{ day }}</div>
                    </div>

                    <!-- Days Grid -->
                    <div
                        class="grid min-h-[460px] flex-1 grid-cols-7 grid-rows-6 gap-2"
                    >
                        <div
                            v-for="day in calendarWeeks.flat()"
                            :key="day.dateString"
                            @click="selectDay(day.date)"
                            class="group relative flex min-h-[85px] cursor-pointer flex-col rounded-xl border border-neutral-100/50 p-2 transition-all duration-300 hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-sm dark:border-neutral-800/30 dark:hover:border-primary/30"
                            :class="[
                                day.isCurrentMonth
                                    ? 'bg-card/50 text-neutral-800 dark:text-neutral-100'
                                    : 'bg-neutral-50/20 text-neutral-400 opacity-60 dark:bg-neutral-800/5',
                                isSameDay(day.date, selectedDate)
                                    ? 'border-primary/20 bg-primary/5 ring-2 ring-primary dark:bg-primary/10'
                                    : '',
                            ]"
                        >
                            <!-- Date Label -->
                            <div
                                class="mb-1.5 flex items-center justify-between"
                            >
                                <span
                                    class="flex size-6.5 items-center justify-center rounded-full text-xs font-black"
                                    :class="[
                                        day.isToday
                                            ? 'bg-primary font-black text-primary-foreground ring-4 ring-primary/15'
                                            : '',
                                    ]"
                                    >{{ day.label }}</span
                                >
                                <button
                                    @click.stop="openNewEventDrawer(day.date)"
                                    class="cursor-pointer rounded-lg bg-primary/10 p-1 text-primary opacity-0 transition-opacity duration-200 group-hover:opacity-100 hover:bg-primary/20"
                                >
                                    <Plus class="size-3.5" />
                                </button>
                            </div>

                            <!-- List of item badges -->
                            <div
                                class="max-h-[85px] flex-1 space-y-1.5 overflow-y-auto pr-1"
                            >
                                <div
                                    v-for="item in getFilteredItems(
                                        day.dateString,
                                    ).slice(0, 3)"
                                    :key="item.id + '-' + item.itemType"
                                    @click.stop="
                                        item.itemType === 'event'
                                            ? openEditEventDrawer(item)
                                            : null
                                    "
                                    class="flex cursor-pointer items-center gap-1.5 truncate rounded-lg border px-2 py-0.5 text-[10px] font-bold transition-all hover:scale-97"
                                    :class="[
                                        item.itemType === 'event'
                                            ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600 dark:bg-emerald-950/20 dark:text-emerald-400'
                                            : item.itemType === 'milestone'
                                              ? 'border-blue-500/20 bg-blue-500/10 text-blue-600 dark:bg-blue-950/20 dark:text-blue-400'
                                              : 'border-purple-500/20 bg-purple-500/10 text-purple-600 dark:bg-purple-950/20 dark:text-purple-400',
                                    ]"
                                >
                                    <span
                                        v-if="item.is_recurring_instance"
                                        class="size-1 shrink-0 rounded-full bg-emerald-500"
                                    ></span>
                                    <span
                                        v-if="
                                            !item.is_all_day &&
                                            item.itemType === 'event'
                                        "
                                        class="shrink-0 text-[9px] opacity-75"
                                        >{{ getEventTime(item) }}</span
                                    >
                                    <span class="truncate">{{
                                        item.title
                                    }}</span>
                                </div>
                                <div
                                    v-if="
                                        getFilteredItems(day.dateString)
                                            .length > 3
                                    "
                                    class="pl-1 text-[9px] font-bold text-neutral-400"
                                >
                                    +
                                    {{
                                        getFilteredItems(day.dateString)
                                            .length - 3
                                    }}
                                    more
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- WEEK VIEW -->
                <div
                    v-else-if="activeView === 'week'"
                    class="max-h-[600px] space-y-4 overflow-y-auto pr-1"
                >
                    <div
                        v-for="day in weekDays"
                        :key="day.dateString"
                        class="flex flex-col gap-4 rounded-2xl border border-neutral-100 bg-neutral-50/30 p-4 md:flex-row dark:border-neutral-900 dark:bg-neutral-800/10"
                        :class="
                            day.isToday
                                ? 'border-primary/45 bg-primary/5 dark:bg-primary/5'
                                : ''
                        "
                    >
                        <div
                            class="flex shrink-0 flex-col md:w-32 md:items-end"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-neutral-400 uppercase"
                                >{{ day.label }}</span
                            >
                            <span
                                class="mt-1 text-3xl font-black text-neutral-800 dark:text-neutral-100"
                                >{{ day.dayNumber }}</span
                            >
                        </div>
                        <div class="flex-1 space-y-3">
                            <div
                                v-if="
                                    getFilteredItems(day.dateString).length ===
                                    0
                                "
                                class="py-3 text-xs font-medium text-neutral-400"
                            >
                                No events, milestones, or tasks.
                            </div>
                            <div
                                v-for="item in getFilteredItems(day.dateString)"
                                :key="item.id + '-' + item.itemType"
                                @click="
                                    item.itemType === 'event'
                                        ? openEditEventDrawer(item)
                                        : null
                                "
                                class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border bg-card p-3.5 shadow-sm transition-all duration-200 hover:border-neutral-300 dark:hover:border-neutral-700"
                                :class="[
                                    item.itemType === 'event'
                                        ? 'border-emerald-100 hover:bg-emerald-50/10 dark:border-emerald-800/40'
                                        : item.itemType === 'milestone'
                                          ? 'border-blue-100 hover:bg-blue-50/10 dark:border-blue-800/40'
                                          : 'border-purple-100 hover:bg-purple-50/10 dark:border-purple-800/40',
                                ]"
                            >
                                <div class="flex items-center gap-3.5">
                                    <span
                                        class="size-3.5 rounded-full ring-4"
                                        :class="[
                                            item.itemType === 'event'
                                                ? 'bg-emerald-500 ring-emerald-500/10'
                                                : item.itemType === 'milestone'
                                                  ? 'bg-blue-500 ring-blue-500/10'
                                                  : 'bg-purple-500 ring-purple-500/10',
                                        ]"
                                    ></span>
                                    <div>
                                        <h4
                                            class="text-sm font-bold text-neutral-800 dark:text-neutral-100"
                                        >
                                            {{ item.title }}
                                        </h4>
                                        <p
                                            v-if="item.description"
                                            class="mt-1 line-clamp-1 text-xs text-neutral-400"
                                        >
                                            {{ item.description }}
                                        </p>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center gap-4 text-xs font-semibold text-neutral-500"
                                >
                                    <div class="flex items-center gap-1.5">
                                        <Clock class="size-3.5" /><span>{{
                                            item.itemType === 'event'
                                                ? getEventTime(item)
                                                : 'Due'
                                        }}</span>
                                    </div>
                                    <Badge
                                        variant="outline"
                                        :class="[
                                            item.itemType === 'event'
                                                ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                                : item.itemType === 'milestone'
                                                  ? 'border-blue-500/20 bg-blue-500/10 text-blue-600 dark:text-blue-400'
                                                  : 'border-purple-500/20 bg-purple-500/10 text-purple-600 dark:text-purple-400',
                                        ]"
                                        >{{ item.itemType }}</Badge
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DAY VIEW -->
                <div v-else-if="activeView === 'day'" class="space-y-4">
                    <div
                        class="mb-4 flex items-center justify-between rounded-xl bg-neutral-50 p-3 dark:bg-neutral-800/40"
                    >
                        <span
                            class="flex items-center gap-2 text-xs font-black tracking-wider text-neutral-500 uppercase"
                            ><Sparkles class="size-4 text-primary" />Agenda for
                            {{
                                selectedDate.toLocaleDateString('en-US', {
                                    weekday: 'long',
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                })
                            }}</span
                        >
                        <Button
                            size="xs"
                            variant="outline"
                            @click="openNewEventDrawer(selectedDate)"
                            class="flex h-7 cursor-pointer items-center gap-1.5 rounded-lg text-xs font-bold"
                            ><Plus class="size-3.5" /> Add to this day</Button
                        >
                    </div>
                    <div
                        v-if="selectedDayEvents.length === 0"
                        class="flex flex-col items-center justify-center py-20 text-center text-neutral-400"
                    >
                        <div
                            class="mb-3 flex size-14 items-center justify-center rounded-2xl bg-neutral-50 dark:bg-neutral-800/20"
                        >
                            <CalendarIcon
                                class="size-7 text-neutral-300 opacity-60 dark:text-neutral-600"
                            />
                        </div>
                        <p class="text-sm font-semibold">
                            Nothing scheduled for this day.
                        </p>
                    </div>
                    <div
                        v-else
                        class="max-h-[500px] space-y-3 overflow-y-auto pr-1"
                    >
                        <div
                            v-for="item in selectedDayEvents"
                            :key="item.id + '-' + item.itemType"
                            @click="
                                item.itemType === 'event'
                                    ? openEditEventDrawer(item)
                                    : null
                            "
                            class="flex cursor-pointer flex-col justify-between gap-4 rounded-xl border bg-card p-4 transition-all hover:border-neutral-300 md:flex-row md:items-center dark:hover:border-neutral-700"
                            :class="[
                                item.itemType === 'event'
                                    ? 'border-emerald-100 hover:bg-emerald-50/10 dark:border-emerald-800/40'
                                    : item.itemType === 'milestone'
                                      ? 'border-blue-100 hover:bg-blue-50/10 dark:border-blue-800/40'
                                      : 'border-purple-100 hover:bg-purple-50/10 dark:border-purple-800/40',
                            ]"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2.5">
                                    <span
                                        class="size-3 rounded-full"
                                        :class="[
                                            item.itemType === 'event'
                                                ? 'bg-emerald-500'
                                                : item.itemType === 'milestone'
                                                  ? 'bg-blue-500'
                                                  : 'bg-purple-500',
                                        ]"
                                    ></span>
                                    <h4
                                        class="text-base font-bold text-neutral-800 dark:text-neutral-100"
                                    >
                                        {{ item.title }}
                                    </h4>
                                </div>
                                <p
                                    v-if="item.description"
                                    class="pl-5.5 text-xs text-neutral-400"
                                >
                                    {{ item.description }}
                                </p>
                            </div>
                            <div
                                class="flex items-center gap-3 pl-5.5 text-xs font-bold text-neutral-500 md:pl-0"
                            >
                                <Clock class="size-4 text-neutral-400" />
                                <span>{{
                                    item.itemType === 'event'
                                        ? getEventTime(item)
                                        : 'All Day'
                                }}</span>
                                <Badge
                                    class="ml-2 rounded-lg capitalize"
                                    :class="[
                                        item.itemType === 'event'
                                            ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600'
                                            : item.itemType === 'milestone'
                                              ? 'border-blue-500/20 bg-blue-500/10 text-blue-600'
                                              : 'border-purple-500/20 bg-purple-500/10 text-purple-600',
                                    ]"
                                    >{{ item.itemType }}</Badge
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AGENDA VIEW -->
                <div
                    v-else-if="activeView === 'agenda'"
                    class="flex h-full flex-col"
                >
                    <div
                        v-if="agendaItems.length === 0"
                        class="flex flex-col items-center justify-center py-24 text-center text-neutral-400"
                    >
                        <div
                            class="mb-3 flex size-16 items-center justify-center rounded-2xl bg-neutral-50 dark:bg-neutral-800/20"
                        >
                            <CalendarIcon
                                class="size-8 text-neutral-300 opacity-60 dark:text-neutral-600"
                            />
                        </div>
                        <p class="text-sm font-semibold">
                            No schedule items in the active date range.
                        </p>
                    </div>
                    <div
                        v-else
                        class="max-h-[600px] space-y-4 overflow-y-auto pr-1"
                    >
                        <div
                            v-for="item in agendaItems"
                            :key="
                                item.id +
                                '-' +
                                item.itemType +
                                '-' +
                                item.dateString
                            "
                            @click="
                                item.itemType === 'event'
                                    ? openEditEventDrawer(item)
                                    : null
                            "
                            class="flex cursor-pointer flex-col justify-between gap-4 rounded-xl border bg-card p-4 shadow-sm transition-all hover:border-neutral-300 md:flex-row md:items-center dark:hover:border-neutral-700"
                            :class="[
                                item.itemType === 'event'
                                    ? 'border-emerald-100 hover:bg-emerald-50/10 dark:border-emerald-800/40'
                                    : item.itemType === 'milestone'
                                      ? 'border-blue-100 hover:bg-blue-50/10 dark:border-blue-800/40'
                                      : 'border-purple-100 hover:bg-purple-50/10 dark:border-purple-800/40',
                            ]"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-24 shrink-0 text-xs font-bold tracking-wider text-neutral-400"
                                >
                                    {{
                                        item.parsedDate.toLocaleDateString(
                                            'en-US',
                                            {
                                                month: 'short',
                                                day: 'numeric',
                                                weekday: 'short',
                                            },
                                        )
                                    }}
                                </div>
                                <div class="flex items-center gap-3">
                                    <span
                                        class="size-2.5 rounded-full"
                                        :class="[
                                            item.itemType === 'event'
                                                ? 'bg-emerald-500'
                                                : item.itemType === 'milestone'
                                                  ? 'bg-blue-500'
                                                  : 'bg-purple-500',
                                        ]"
                                    ></span>
                                    <div>
                                        <h4
                                            class="text-sm font-bold text-neutral-800 dark:text-neutral-100"
                                        >
                                            {{ item.title }}
                                        </h4>
                                        <p
                                            v-if="item.description"
                                            class="mt-1 line-clamp-1 text-xs text-neutral-400"
                                        >
                                            {{ item.description }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="flex items-center gap-3 pl-28 text-xs font-bold text-neutral-500 md:pl-0"
                            >
                                <Clock class="size-3.5" />
                                <span>{{
                                    item.itemType === 'event'
                                        ? getEventTime(item)
                                        : 'All Day'
                                }}</span>
                                <Badge
                                    class="rounded-lg capitalize"
                                    :class="[
                                        item.itemType === 'event'
                                            ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600'
                                            : item.itemType === 'milestone'
                                              ? 'border-blue-500/20 bg-blue-500/10 text-blue-600'
                                              : 'border-purple-500/20 bg-purple-500/10 text-purple-600',
                                    ]"
                                    >{{ item.itemType }}</Badge
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Form Drawer / Slide-Over Sheet -->
    <Sheet :open="isDrawerOpen" @update:open="isDrawerOpen = $event">
        <SheetContent
            class="w-full overflow-y-auto bg-white/95 p-6 backdrop-blur-md sm:max-w-md dark:bg-neutral-900/95"
        >
            <SheetHeader
                class="mb-6 flex flex-row items-center gap-3 space-y-0"
            >
                <div class="rounded-2xl bg-primary/10 p-2.5 text-primary">
                    <Sparkles class="size-5" />
                </div>
                <div>
                    <SheetTitle
                        class="text-lg font-black tracking-tight text-neutral-900 dark:text-neutral-100"
                    >
                        {{
                            editingEvent
                                ? 'Edit Calendar Event'
                                : 'Create New Event'
                        }}
                    </SheetTitle>
                    <p class="mt-0.5 text-xs font-semibold text-neutral-400">
                        Define your schedule details & reminders
                    </p>
                </div>
            </SheetHeader>

            <form @submit.prevent="saveEvent" class="space-y-6 pb-6">
                <!-- Basic Info Card Section -->
                <div
                    class="space-y-4 rounded-2xl border border-neutral-100 bg-neutral-50/40 p-4 dark:border-neutral-800 dark:bg-neutral-800/10"
                >
                    <h4
                        class="flex items-center gap-1.5 text-xs font-black tracking-wider text-neutral-400 uppercase"
                    >
                        <FileText class="size-3.5 text-neutral-400" />
                        Basic Information
                    </h4>

                    <!-- Title -->
                    <div class="space-y-2">
                        <Label
                            for="title"
                            class="font-bold text-neutral-800 dark:text-neutral-200"
                            >Event Title</Label
                        >
                        <Input
                            id="title"
                            v-model="form.title"
                            placeholder="e.g. Project Demo Session"
                            required
                            class="rounded-xl border-neutral-200 bg-white focus:border-primary/80 dark:border-neutral-800 dark:bg-neutral-900"
                        />
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <Label
                            for="description"
                            class="font-bold text-neutral-800 dark:text-neutral-200"
                            >Description</Label
                        >
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="flex w-full rounded-xl border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-neutral-400 focus-visible:ring-1 focus-visible:ring-primary focus-visible:outline-none dark:border-neutral-800 dark:bg-neutral-900 dark:placeholder:text-neutral-500"
                            placeholder="Add dynamic meeting links, notes, etc..."
                        ></textarea>
                    </div>

                    <!-- Color Selector -->
                    <div class="space-y-2.5">
                        <Label
                            class="font-bold text-neutral-800 dark:text-neutral-200"
                            >Event Label Color</Label
                        >
                        <div class="flex gap-3">
                            <button
                                v-for="color in colorOptions"
                                :key="color.value"
                                type="button"
                                @click="handleColorSelect(color.value)"
                                class="animate-transition flex size-8 cursor-pointer items-center justify-center rounded-full border border-neutral-100 shadow-sm transition-all duration-200 hover:scale-110 active:scale-95 dark:border-neutral-800"
                                :class="[
                                    color.bg,
                                    form.color === color.value
                                        ? 'scale-105 ring-2 ring-primary ring-offset-2 dark:ring-offset-neutral-900'
                                        : '',
                                ]"
                            >
                                <Check
                                    v-if="form.color === color.value"
                                    class="size-4 text-white"
                                />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Date & Time Card Section -->
                <div
                    class="space-y-4 rounded-2xl border border-neutral-100 bg-neutral-50/40 p-4 dark:border-neutral-800 dark:bg-neutral-800/10"
                >
                    <h4
                        class="flex items-center gap-1.5 text-xs font-black tracking-wider text-neutral-400 uppercase"
                    >
                        <Clock class="size-3.5 text-neutral-400" />
                        Schedule Configuration
                    </h4>

                    <!-- Start Date & Time -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label
                                for="start_date"
                                class="font-bold text-neutral-800 dark:text-neutral-200"
                                >Start Date</Label
                            >
                            <Input
                                type="date"
                                id="start_date"
                                v-model="form.start_date"
                                required
                                class="rounded-xl border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900"
                            />
                        </div>
                        <div class="space-y-2" v-if="!form.is_all_day">
                            <Label
                                for="start_time"
                                class="font-bold text-neutral-800 dark:text-neutral-200"
                                >Start Time</Label
                            >
                            <Input
                                type="time"
                                id="start_time"
                                v-model="form.start_time"
                                required
                                class="rounded-xl border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900"
                            />
                        </div>
                    </div>

                    <!-- End Date & Time -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label
                                for="end_date"
                                class="font-bold text-neutral-800 dark:text-neutral-200"
                                >End Date</Label
                            >
                            <Input
                                type="date"
                                id="end_date"
                                v-model="form.end_date"
                                required
                                class="rounded-xl border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900"
                            />
                        </div>
                        <div class="space-y-2" v-if="!form.is_all_day">
                            <Label
                                for="end_time"
                                class="font-bold text-neutral-800 dark:text-neutral-200"
                                >End Time</Label
                            >
                            <Input
                                type="time"
                                id="end_time"
                                v-model="form.end_time"
                                required
                                class="rounded-xl border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900"
                            />
                        </div>
                    </div>

                    <!-- All Day Checkbox Switch layout -->
                    <label
                        class="flex cursor-pointer items-center gap-3 p-1 text-sm font-semibold"
                    >
                        <input
                            type="checkbox"
                            v-model="form.is_all_day"
                            class="size-4.5 cursor-pointer rounded border-neutral-300 text-primary accent-primary focus:ring-primary/45 dark:border-neutral-700"
                        />
                        <span class="text-neutral-700 dark:text-neutral-300"
                            >All day event</span
                        >
                    </label>
                </div>

                <!-- Recurrence & Reminders Section -->
                <div
                    class="space-y-4 rounded-2xl border border-neutral-100 bg-neutral-50/40 p-4 dark:border-neutral-800 dark:bg-neutral-800/10"
                >
                    <h4
                        class="flex items-center gap-1.5 text-xs font-black tracking-wider text-neutral-400 uppercase"
                    >
                        <Bell class="size-3.5 text-neutral-400" />
                        Recurrence & Notifications
                    </h4>

                    <!-- Recurrence settings -->
                    <div class="space-y-2">
                        <Label
                            for="recurrence_pattern"
                            class="font-bold text-neutral-800 dark:text-neutral-200"
                            >Repeat Event</Label
                        >
                        <Select
                            :model-value="form.recurrence_pattern"
                            @update:model-value="
                                form.recurrence_pattern = $event as any
                            "
                        >
                            <SelectTrigger
                                class="rounded-xl border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900"
                            >
                                <SelectValue placeholder="Does not repeat" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl">
                                <SelectItem value="none"
                                    >Does not repeat</SelectItem
                                >
                                <SelectItem value="daily">Daily</SelectItem>
                                <SelectItem value="weekly">Weekly</SelectItem>
                                <SelectItem value="monthly">Monthly</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div
                        class="space-y-2"
                        v-if="form.recurrence_pattern !== 'none'"
                    >
                        <Label
                            for="recurrence_end"
                            class="font-bold text-neutral-800 dark:text-neutral-200"
                            >Repeat Until</Label
                        >
                        <Input
                            type="date"
                            id="recurrence_end"
                            v-model="form.recurrence_end"
                            required
                            class="rounded-xl border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900"
                        />
                    </div>

                    <!-- Reminder Settings -->
                    <div class="space-y-2">
                        <Label
                            for="reminder"
                            class="font-bold text-neutral-800 dark:text-neutral-200"
                            >Telegram & Database Reminder</Label
                        >
                        <Select
                            :model-value="
                                form.reminder_lead_time === null
                                    ? 'null'
                                    : form.reminder_lead_time.toString()
                            "
                            @update:model-value="handleReminderChange"
                        >
                            <SelectTrigger
                                class="rounded-xl border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900"
                            >
                                <SelectValue placeholder="No Reminder" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl">
                                <SelectItem
                                    v-for="opt in reminderOptions"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Actions Button Panel -->
                <div class="flex gap-3 pt-3">
                    <Button
                        type="submit"
                        class="h-10.5 flex-1 cursor-pointer rounded-xl bg-gradient-to-r from-primary to-violet-600 font-bold text-white shadow-md shadow-primary/10 transition-all duration-200 hover:from-primary/95 hover:to-violet-600/95 active:scale-97"
                        :disabled="form.processing"
                    >
                        {{ editingEvent ? 'Update Event' : 'Create Event' }}
                    </Button>
                    <Button
                        v-if="editingEvent"
                        type="button"
                        variant="ghost"
                        @click="deleteEvent"
                        :disabled="form.processing"
                        class="size-10.5 shrink-0 cursor-pointer rounded-xl border border-rose-200/50 text-rose-500 hover:bg-rose-500/10 hover:text-rose-600 dark:border-rose-950/30"
                    >
                        <Trash2 class="size-4.5" />
                    </Button>
                </div>
            </form>
        </SheetContent>
    </Sheet>
</template>

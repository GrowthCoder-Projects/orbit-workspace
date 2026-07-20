<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    CheckSquare,
    Flame,
    Award,
    Plus,
    Trash2,
    Archive,
    RotateCcw,
    Calendar as CalendarIcon,
    AlertCircle,
    Info,
    Check,
    Edit2,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { index, store, update, destroy, toggle, archive } from '@/routes/habits';

interface HabitLog {
    id: number;
    habit_id: number;
    completed_date: string;
    notes?: string;
}

interface Habit {
    id: number;
    name: string;
    description?: string;
    frequency_type: 'daily' | 'weekly' | 'custom_days';
    frequency_days?: string[];
    frequency_count: number;
    color_accent: 'emerald' | 'indigo' | 'amber' | 'violet' | 'rose';
    reminder_time?: string;
    streak_current: number;
    streak_longest: number;
    is_active: boolean;
    archived_at?: string;
    logs: HabitLog[];
}

const props = defineProps<{
    habits: Habit[];
    archived_habits: Habit[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Habit Tracker',
                href: '/app/habits',
            },
        ],
    },
});

// Selected habit logic
const selectedHabitId = ref<number | null>(props.habits.length > 0 ? props.habits[0].id : null);
const selectedHabit = computed(() => {
    if (!selectedHabitId.value) {
return null;
}

    return props.habits.find(h => h.id === selectedHabitId.value) || 
           props.archived_habits.find(h => h.id === selectedHabitId.value) || 
           null;
});

// Modals and dialogs states
const isCreateDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const isArchiveDialogOpen = ref(false);

// Collapsible archive list
const isArchiveExpanded = ref(false);

// Form handling
const createForm = useForm({
    name: '',
    description: '',
    frequency_type: 'daily' as 'daily' | 'weekly' | 'custom_days',
    frequency_days: [] as string[],
    frequency_count: 1,
    color_accent: 'emerald' as 'emerald' | 'indigo' | 'amber' | 'violet' | 'rose',
    reminder_time: '',
});

const editForm = useForm({
    id: 0,
    name: '',
    description: '',
    frequency_type: 'daily' as 'daily' | 'weekly' | 'custom_days',
    frequency_days: [] as string[],
    frequency_count: 1,
    color_accent: 'emerald' as 'emerald' | 'indigo' | 'amber' | 'violet' | 'rose',
    reminder_time: '',
});

// Color classes mapper
const colorClasses = {
    emerald: {
        text: 'text-emerald-500',
        textMuted: 'text-emerald-600',
        bg: 'bg-emerald-500',
        bgLight: 'bg-emerald-500/10 hover:bg-emerald-500/20',
        border: 'border-emerald-500/30',
        borderActive: 'border-emerald-500',
        ring: 'ring-emerald-500',
        fill: 'bg-emerald-500/20 text-emerald-400',
        heatmapCell: 'bg-emerald-500',
    },
    indigo: {
        text: 'text-indigo-500',
        textMuted: 'text-indigo-600',
        bg: 'bg-indigo-500',
        bgLight: 'bg-indigo-500/10 hover:bg-indigo-500/20',
        border: 'border-indigo-500/30',
        borderActive: 'border-indigo-500',
        ring: 'ring-indigo-500',
        fill: 'bg-indigo-500/20 text-indigo-400',
        heatmapCell: 'bg-indigo-500',
    },
    amber: {
        text: 'text-amber-500',
        textMuted: 'text-amber-600',
        bg: 'bg-amber-500',
        bgLight: 'bg-amber-500/10 hover:bg-amber-500/20',
        border: 'border-amber-500/30',
        borderActive: 'border-amber-500',
        ring: 'ring-amber-500',
        fill: 'bg-amber-500/20 text-amber-400',
        heatmapCell: 'bg-amber-500',
    },
    violet: {
        text: 'text-violet-500',
        textMuted: 'text-violet-600',
        bg: 'bg-violet-500',
        bgLight: 'bg-violet-500/10 hover:bg-violet-500/20',
        border: 'border-violet-500/30',
        borderActive: 'border-violet-500',
        ring: 'ring-violet-500',
        fill: 'bg-violet-500/20 text-violet-400',
        heatmapCell: 'bg-violet-500',
    },
    rose: {
        text: 'text-rose-500',
        textMuted: 'text-rose-600',
        bg: 'bg-rose-500',
        bgLight: 'bg-rose-500/10 hover:bg-rose-500/20',
        border: 'border-rose-500/30',
        borderActive: 'border-rose-500',
        ring: 'ring-rose-500',
        fill: 'bg-rose-500/20 text-rose-400',
        heatmapCell: 'bg-rose-500',
    },
};

// Days of the week list
const daysOfWeek = [
    { value: 'mon', label: 'Mon' },
    { value: 'tue', label: 'Tue' },
    { value: 'wed', label: 'Wed' },
    { value: 'thu', label: 'Thu' },
    { value: 'fri', label: 'Fri' },
    { value: 'sat', label: 'Sat' },
    { value: 'sun', label: 'Sun' },
];

// Helper to determine last 7 days for the quick list
const last7Days = computed(() => {
    const days = [];

    for (let i = 6; i >= 0; i--) {
        const d = new Date();
        d.setDate(d.getDate() - i);
        
        // Format day narrow label in Indonesian (S, S, R, K, J, S, M)
        const dayLabel = d.toLocaleDateString('en-US', { weekday: 'narrow' });
        const shortLabel = d.toLocaleDateString('en-US', { weekday: 'short' });
        
        // Extract offset local date
        const offset = d.getTimezoneOffset();
        const localDate = new Date(d.getTime() - (offset * 60 * 1000));
        const dateString = localDate.toISOString().substring(0, 10);

        days.push({
            date: d,
            dateString,
            dayLabel,
            shortLabel,
            isToday: i === 0,
            formattedDate: d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
        });
    }

    return days;
});

// Check if a habit is logged on a specific date
const isHabitCompletedOn = (habit: Habit, dateString: string) => {
    return habit.logs.some(log => {
        const logDate = typeof log.completed_date === 'string'
            ? log.completed_date.substring(0, 10)
            : new Date(log.completed_date).toISOString().substring(0, 10);

        return logDate === dateString;
    });
};

// Heatmap grid calculations (last 365 days aligned Sunday-Saturday)
const heatmapWeeks = computed(() => {
    if (!selectedHabit.value) {
return [];
}

    const loggedDates = new Set(
        selectedHabit.value.logs.map(log => {
            return typeof log.completed_date === 'string'
                ? log.completed_date.substring(0, 10)
                : new Date(log.completed_date).toISOString().substring(0, 10);
        })
    );

    const dates = [];
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(endDate.getDate() - 364); // Exactly 365 days

    // Align startDate to Sunday of its week
    const startDay = startDate.getDay(); // 0 is Sunday
    const paddedStartDate = new Date(startDate);
    paddedStartDate.setDate(startDate.getDate() - startDay);

    const curr = new Date(paddedStartDate);
    // Align endDate to Saturday to complete the grid nicely
    const endDay = endDate.getDay();
    const paddedEndDate = new Date(endDate);
    paddedEndDate.setDate(endDate.getDate() + (6 - endDay));

    while (curr <= paddedEndDate) {
        // Adjust for local date offset to match YYYY-MM-DD
        const offset = curr.getTimezoneOffset();
        const localDate = new Date(curr.getTime() - (offset * 60 * 1000));
        const dateStr = localDate.toISOString().substring(0, 10);

        const isPadded = curr < startDate || curr > endDate;

        dates.push({
            date: new Date(curr),
            dateString: dateStr,
            isCompleted: loggedDates.has(dateStr),
            isPadded,
            dayOfWeek: curr.getDay(),
            label: curr.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
        });
        curr.setDate(curr.getDate() + 1);
    }

    // Group into weeks (columns of 7 days)
    const weeks = [];

    for (let i = 0; i < dates.length; i += 7) {
        weeks.push(dates.slice(i, i + 7));
    }

    return weeks;
});

// Calculate completion statistics
const stats = computed(() => {
    if (!selectedHabit.value) {
return { total: 0, current: 0, longest: 0, rate: 0 };
}
    
    const totalCompletions = selectedHabit.value.logs.length;
    const currentStreak = selectedHabit.value.streak_current;
    const longestStreak = selectedHabit.value.streak_longest;
    
    // Rate is percentage of scheduled days completed over the last 90 days (or last 365)
    let rate = 0;

    if (selectedHabit.value.logs.length > 0) {
        const daysTracked = 90;
        const scheduledDaysCount = calculateScheduledDaysCount(selectedHabit.value, daysTracked);
        const completionsCount = selectedHabit.value.logs.filter(log => {
            const date = new Date(log.completed_date);
            const cutoff = new Date();
            cutoff.setDate(cutoff.getDate() - daysTracked);

            return date >= cutoff;
        }).length;
        rate = scheduledDaysCount > 0 ? Math.round((completionsCount / scheduledDaysCount) * 100) : 0;
        rate = Math.min(rate, 100);
    }

    return {
        total: totalCompletions,
        current: currentStreak,
        longest: longestStreak,
        rate
    };
});

// Helper to calculate scheduled days count in a time range
const calculateScheduledDaysCount = (habit: Habit, daysBack: number) => {
    let count = 0;
    const scheduledDays = habit.frequency_days?.map(d => d.toLowerCase()) || [];
    const date = new Date();
    
    for (let i = 0; i < daysBack; i++) {
        if (habit.frequency_type === 'daily') {
            count++;
        } else if (habit.frequency_type === 'custom_days') {
            const dayName = date.toLocaleDateString('en-US', { weekday: 'short' }).toLowerCase();

            if (scheduledDays.includes(dayName)) {
                count++;
            }
        } else if (habit.frequency_type === 'weekly') {
            // Roughly habit.frequency_count per week
            if (i % 7 === 0) {
                count += habit.frequency_count;
            }
        }

        date.setDate(date.getDate() - 1);
    }

    return count;
};

// Check if habit is scheduled for today
const isScheduledToday = (habit: Habit) => {
    const today = new Date();

    if (habit.frequency_type === 'daily') {
return true;
}

    if (habit.frequency_type === 'weekly') {
return true;
} // Show weekly target tracker

    if (habit.frequency_type === 'custom_days') {
        const todayName = today.toLocaleDateString('en-US', { weekday: 'short' }).toLowerCase();

        return habit.frequency_days?.map(d => d.toLowerCase()).includes(todayName);
    }

    return false;
};

// Toggle habit completion on a specific date
const handleToggleHabit = (habitId: number, dateString: string) => {
    // Avoid double toggle locks
    router.post(toggle.url({ habit: habitId }), {
        date: dateString
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Habit completion toggled.');
        },
        onError: (err) => {
            if (err.date) {
                toast.error(err.date);
            }
        }
    });
};

// Dialog openers with form preset
const openCreateDialog = () => {
    createForm.reset();
    isCreateDialogOpen.value = true;
};

const submitCreateHabit = () => {
    createForm.post(store.url(), {
        onSuccess: () => {
            isCreateDialogOpen.value = false;
            toast.success('New habit created!');
        }
    });
};

const openEditDialog = (habit: Habit) => {
    editForm.id = habit.id;
    editForm.name = habit.name;
    editForm.description = habit.description || '';
    editForm.frequency_type = habit.frequency_type;
    editForm.frequency_days = habit.frequency_days || [];
    editForm.frequency_count = habit.frequency_count;
    editForm.color_accent = habit.color_accent;
    editForm.reminder_time = habit.reminder_time || '';
    isEditDialogOpen.value = true;
};

const submitEditHabit = () => {
    editForm.put(update.url({ habit: editForm.id }), {
        onSuccess: () => {
            isEditDialogOpen.value = false;
            toast.success('Habit updated successfully.');
        }
    });
};

const openArchiveDialog = (habit: Habit) => {
    isArchiveDialogOpen.value = true;
};

const toggleArchiveHabit = () => {
    if (!selectedHabit.value) {
return;
}

    router.post(archive.url({ habit: selectedHabit.value.id }), {}, {
        onSuccess: () => {
            isArchiveDialogOpen.value = false;

            // Switch selected habit to another active one if archived
            if (selectedHabit.value?.is_active) {
                selectedHabitId.value = props.habits.length > 0 ? props.habits[0].id : null;
            }

            toast.success('Habit archive state updated.');
        }
    });
};

const openDeleteDialog = (habit: Habit) => {
    isDeleteDialogOpen.value = true;
};

const deleteHabit = () => {
    if (!selectedHabit.value) {
return;
}

    router.delete(destroy.url({ habit: selectedHabit.value.id }), {
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            selectedHabitId.value = props.habits.length > 0 ? props.habits[0].id : null;
            toast.success('Habit deleted.');
        }
    });
};
</script>

<template>
    <div class="flex min-h-[calc(100vh-6rem)] flex-col gap-6 p-6 lg:flex-row">
        <Head title="Habit Tracker" />

        <!-- LEFT PANE: Habit List & 7-Day Checklist -->
        <div class="flex w-full shrink-0 flex-col gap-6 lg:w-[480px]">
            <Card class="border-sidebar-border/60 bg-sidebar flex-1 flex flex-col min-h-[500px]">
                <CardHeader class="flex flex-row items-center justify-between px-6 pt-6 pb-4 border-b border-sidebar-border/40">
                    <div>
                        <h2 class="text-lg font-bold tracking-tight text-foreground">Habits</h2>
                        <p class="text-xs text-muted-foreground">Log your routines & streaks</p>
                    </div>
                    <Button size="sm" class="h-8 gap-1.5" @click="openCreateDialog">
                        <Plus class="h-3.5 w-3.5" />
                        <span>Add Habit</span>
                    </Button>
                </CardHeader>

                <CardContent class="flex-1 overflow-y-auto px-4 py-4 flex flex-col gap-3">
                    <!-- Empty State -->
                    <div v-if="habits.length === 0" class="flex flex-col items-center justify-center text-center py-12 px-4 border border-dashed border-sidebar-border/80 rounded-xl">
                        <CheckSquare class="h-10 w-10 text-muted-foreground/60 mb-3" />
                        <h4 class="text-sm font-semibold text-foreground">No habits tracked</h4>
                        <p class="text-xs text-muted-foreground mt-1 max-w-[200px]">Create habits to track your daily goals & streaks.</p>
                    </div>

                    <!-- Habits List -->
                    <div 
                        v-for="habit in habits" 
                        :key="habit.id"
                        @click="selectedHabitId = habit.id"
                        class="group flex flex-col gap-3 rounded-lg border p-4 transition-all duration-200 cursor-pointer"
                        :class="[
                            selectedHabitId === habit.id 
                                ? 'bg-sidebar-accent/50 border-sidebar-border' 
                                : 'border-sidebar-border/40 hover:border-sidebar-border/80 hover:bg-sidebar-accent/20'
                        ]"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex flex-col">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full" :class="colorClasses[habit.color_accent].bg"></span>
                                    <span class="text-sm font-semibold text-foreground tracking-tight group-hover:text-primary transition-colors">{{ habit.name }}</span>
                                    <Badge variant="outline" class="text-[10px] px-1.5 py-0">
                                        {{ habit.frequency_type === 'daily' ? 'Daily' : habit.frequency_type === 'weekly' ? `Weekly (${habit.frequency_count}x)` : 'Custom' }}
                                    </Badge>
                                </div>
                                <span v-if="habit.description" class="text-xs text-muted-foreground mt-0.5 line-clamp-1 max-w-[240px]">{{ habit.description }}</span>
                            </div>
                            
                            <!-- Streak Indicator -->
                            <div v-if="habit.streak_current > 0" class="flex items-center gap-1.5 bg-orange-500/10 text-orange-400 rounded-md px-2 py-0.5 border border-orange-500/20">
                                <Flame class="h-3.5 w-3.5 fill-orange-500 text-orange-500 animate-pulse" />
                                <span class="text-xs font-bold font-mono">{{ habit.streak_current }}</span>
                            </div>
                        </div>

                        <!-- 7-Day Completion Bar -->
                        <div class="flex items-center justify-between border-t border-sidebar-border/30 pt-3 mt-1" @click.stop>
                            <div class="flex items-center gap-1.5 flex-1 justify-between">
                                <div 
                                    v-for="day in last7Days" 
                                    :key="day.dateString"
                                    class="flex flex-col items-center gap-1"
                                >
                                    <button 
                                        @click="handleToggleHabit(habit.id, day.dateString)"
                                        class="h-7 w-7 rounded-full border flex items-center justify-center transition-all duration-200 relative"
                                        :class="[
                                            isHabitCompletedOn(habit, day.dateString)
                                                ? `${colorClasses[habit.color_accent].bg} border-transparent text-white font-bold`
                                                : day.isToday
                                                    ? 'border-foreground/40 hover:bg-muted text-foreground'
                                                    : 'border-sidebar-border/80 hover:bg-muted/50 text-muted-foreground/80',
                                        ]"
                                        :title="`${day.formattedDate}: ${isHabitCompletedOn(habit, day.dateString) ? 'Completed' : 'Pending'}`"
                                    >
                                        <Check v-if="isHabitCompletedOn(habit, day.dateString)" class="h-3.5 w-3.5 stroke-[3]" />
                                        <span v-else class="text-[9px] font-semibold">{{ day.dayLabel }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Collapsible Archived Habits -->
                    <div v-if="archived_habits.length > 0" class="border-t border-sidebar-border/40 pt-4 mt-2">
                        <button 
                            @click="isArchiveExpanded = !isArchiveExpanded"
                            class="flex w-full items-center justify-between text-xs font-semibold text-muted-foreground/80 hover:text-foreground transition-colors px-2"
                        >
                            <span>Archived Habits ({{ archived_habits.length }})</span>
                            <span class="transform transition-transform" :class="isArchiveExpanded ? 'rotate-180' : ''">▼</span>
                        </button>

                        <div v-if="isArchiveExpanded" class="flex flex-col gap-2 mt-3">
                            <div 
                                v-for="habit in archived_habits" 
                                :key="habit.id"
                                @click="selectedHabitId = habit.id"
                                class="flex items-center justify-between p-3 rounded-lg border border-dashed border-sidebar-border bg-sidebar-accent/10 opacity-70 hover:opacity-100 transition-opacity cursor-pointer"
                                :class="[selectedHabitId === habit.id ? 'bg-sidebar-accent/30 border-sidebar-border/80' : '']"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-zinc-600"></span>
                                    <span class="text-xs font-medium text-foreground line-clamp-1 max-w-[150px] line-through">{{ habit.name }}</span>
                                </div>
                                <span class="text-[10px] text-muted-foreground">Archived on {{ habit.archived_at ? new Date(habit.archived_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : '' }}</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- RIGHT PANE: Details, Heatmap Calendar, Stats & Logs -->
        <div class="flex-1 flex flex-col gap-6">
            <Card v-if="selectedHabit" class="border-sidebar-border/60 bg-sidebar flex-1 flex flex-col min-h-[500px]">
                <CardHeader class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-6 pt-6 pb-4 border-b border-sidebar-border/40">
                    <div class="flex items-start gap-3">
                        <div class="p-2 rounded-lg" :class="colorClasses[selectedHabit.color_accent].bgLight">
                            <CheckSquare class="h-6 w-6" :class="colorClasses[selectedHabit.color_accent].text" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-md font-bold text-foreground">{{ selectedHabit.name }}</h3>
                                <Badge 
                                    :variant="selectedHabit.is_active ? 'outline' : 'secondary'"
                                    :class="selectedHabit.is_active ? colorClasses[selectedHabit.color_accent].text : ''"
                                >
                                    {{ selectedHabit.is_active ? 'Active' : 'Archived' }}
                                </Badge>
                            </div>
                            <p v-if="selectedHabit.description" class="text-xs text-muted-foreground mt-1 max-w-[500px]">{{ selectedHabit.description }}</p>
                        </div>
                    </div>

                    <!-- Manage Actions -->
                    <div class="flex items-center gap-2 self-end sm:self-center">
                        <Button 
                            v-if="selectedHabit.is_active" 
                            variant="outline" 
                            size="sm" 
                            class="h-8 gap-1.5"
                            @click="openEditDialog(selectedHabit)"
                        >
                            <Edit2 class="h-3.5 w-3.5" />
                            <span>Edit</span>
                        </Button>
                        <Button 
                            variant="outline" 
                            size="sm" 
                            class="h-8 gap-1.5 border-amber-500/20 text-amber-500 hover:bg-amber-500/10 hover:text-amber-400"
                            @click="isArchiveDialogOpen = true"
                        >
                            <Archive class="h-3.5 w-3.5" />
                            <span>{{ selectedHabit.is_active ? 'Archive' : 'Restore' }}</span>
                        </Button>
                        <Button 
                            variant="outline" 
                            size="sm" 
                            class="h-8 gap-1.5 border-rose-500/20 text-rose-500 hover:bg-rose-500/10 hover:text-rose-400"
                            @click="isDeleteDialogOpen = true"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                            <span>Delete</span>
                        </Button>
                    </div>
                </CardHeader>

                <CardContent class="px-6 py-6 flex flex-col gap-6 flex-1 overflow-y-auto">
                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <Card class="border-sidebar-border bg-sidebar-accent/10 p-4 flex flex-col">
                            <span class="text-xs text-muted-foreground">Current Streak</span>
                            <div class="flex items-baseline gap-1 mt-2">
                                <span class="text-2xl font-bold font-mono text-foreground">{{ stats.current }}</span>
                                <span class="text-xs text-muted-foreground">days</span>
                            </div>
                            <div class="flex items-center gap-1 text-[10px] text-orange-400 mt-2">
                                <Flame class="h-3.5 w-3.5 fill-orange-500 text-orange-500" />
                                <span>Streak active</span>
                            </div>
                        </Card>

                        <Card class="border-sidebar-border bg-sidebar-accent/10 p-4 flex flex-col">
                            <span class="text-xs text-muted-foreground">Best Streak</span>
                            <div class="flex items-baseline gap-1 mt-2">
                                <span class="text-2xl font-bold font-mono text-foreground">{{ stats.longest }}</span>
                                <span class="text-xs text-muted-foreground">days</span>
                            </div>
                            <div class="flex items-center gap-1 text-[10px] text-yellow-500 mt-2">
                                <Award class="h-3.5 w-3.5 text-yellow-500" />
                                <span>All-time record</span>
                            </div>
                        </Card>

                        <Card class="border-sidebar-border bg-sidebar-accent/10 p-4 flex flex-col">
                            <span class="text-xs text-muted-foreground">Total Logs</span>
                            <div class="flex items-baseline gap-1 mt-2">
                                <span class="text-2xl font-bold font-mono text-foreground">{{ stats.total }}</span>
                                <span class="text-xs text-muted-foreground">completions</span>
                            </div>
                            <div class="flex items-center gap-1 text-[10px] text-muted-foreground mt-2">
                                <CheckSquare class="h-3.5 w-3.5" />
                                <span>Lifetime completions</span>
                            </div>
                        </Card>

                        <Card class="border-sidebar-border bg-sidebar-accent/10 p-4 flex flex-col">
                            <span class="text-xs text-muted-foreground">Completion Rate (90d)</span>
                            <div class="flex items-baseline gap-1 mt-2">
                                <span class="text-2xl font-bold font-mono text-foreground">{{ stats.rate }}%</span>
                            </div>
                            <!-- Simple custom progress bar -->
                            <div class="w-full bg-sidebar-border rounded-full h-1.5 mt-3 overflow-hidden">
                                <div class="h-1.5 rounded-full" :class="colorClasses[selectedHabit.color_accent].bg" :style="{ width: `${stats.rate}%` }"></div>
                            </div>
                        </Card>
                    </div>

                    <!-- Configuration Details Summary -->
                    <div class="border border-sidebar-border/40 rounded-lg p-4 bg-sidebar-accent/5 flex flex-col gap-2.5">
                        <h4 class="text-xs font-bold text-foreground uppercase tracking-wider">Habit Schedule Settings</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                            <div class="flex flex-col gap-1">
                                <span class="text-muted-foreground">Frequency Strategy</span>
                                <span class="font-medium text-foreground">
                                    {{ selectedHabit.frequency_type === 'daily' ? 'Daily Check-in' : selectedHabit.frequency_type === 'weekly' ? `Weekly Target (${selectedHabit.frequency_count} times/week)` : 'Custom Days' }}
                                </span>
                            </div>
                            <div v-if="selectedHabit.frequency_type === 'custom_days'" class="flex flex-col gap-1">
                                <span class="text-muted-foreground">Scheduled Days</span>
                                <span class="font-medium text-foreground capitalize">{{ selectedHabit.frequency_days?.join(', ') }}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-muted-foreground">Telegram Notification</span>
                                <span class="font-medium text-foreground">
                                    {{ selectedHabit.reminder_time ? `Pushed at ${selectedHabit.reminder_time} daily` : 'Disabled' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- GitHub Heatmap 365 Days -->
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-bold text-foreground flex items-center gap-1.5">
                                <CalendarIcon class="h-4 w-4" />
                                <span>365-Day Completion Heatmap</span>
                            </h4>
                            <div class="flex items-center gap-1 text-[10px] text-muted-foreground">
                                <span>Less</span>
                                <div class="h-2.5 w-2.5 rounded bg-zinc-800 dark:bg-zinc-900 border border-sidebar-border/30"></div>
                                <div class="h-2.5 w-2.5 rounded opacity-50" :class="colorClasses[selectedHabit.color_accent].bg"></div>
                                <div class="h-2.5 w-2.5 rounded" :class="colorClasses[selectedHabit.color_accent].bg"></div>
                                <span>More</span>
                            </div>
                        </div>

                        <!-- Heatmap Scrolling Container -->
                        <div class="border border-sidebar-border/40 bg-sidebar-accent/5 p-4 rounded-xl flex flex-col overflow-x-auto select-none scrollbar-thin">
                            <div class="flex gap-[3px] min-w-max">
                                <!-- Day labels column (aligned with grid) -->
                                <div class="flex flex-col gap-[3px] pr-2 text-[9px] text-muted-foreground/60 font-semibold justify-between h-[88px] pt-1 select-none">
                                    <span>Sun</span>
                                    <span>Tue</span>
                                    <span>Thu</span>
                                    <span>Sat</span>
                                </div>

                                <!-- Columns (Weeks) -->
                                <div 
                                    v-for="(week, weekIndex) in heatmapWeeks" 
                                    :key="weekIndex" 
                                    class="flex flex-col gap-[3px]"
                                >
                                    <!-- Cells -->
                                    <button
                                        v-for="day in week"
                                        :key="day.dateString"
                                        @click="handleToggleHabit(selectedHabit.id, day.dateString)"
                                        class="h-2.5 w-2.5 rounded-[2px] transition-all duration-150 border cursor-pointer flex justify-center items-center"
                                        :class="[
                                            day.isPadded 
                                                ? 'bg-transparent border-transparent opacity-0 pointer-events-none'
                                                : day.isCompleted
                                                    ? `${colorClasses[selectedHabit.color_accent].bg} border-transparent text-white scale-105`
                                                    : 'bg-zinc-150 dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800/80 hover:border-zinc-400 dark:hover:border-zinc-600',
                                        ]"
                                        :title="`${day.label}: ${day.isCompleted ? 'Completed' : 'No completions recorded'}`"
                                    ></button>
                                </div>
                            </div>
                            <div class="flex justify-between text-[9px] text-muted-foreground/60 mt-2 px-6">
                                <span>12 months ago</span>
                                <span>Today</span>
                            </div>
                        </div>
                        <p class="text-[10px] text-muted-foreground flex items-center gap-1.5 px-2 mt-0.5">
                            <Info class="h-3.5 w-3.5" />
                            <span>Click on any cell in the heatmap grid to log/unlog completions retroactively up to 30 days back.</span>
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card v-else class="border-sidebar-border/60 bg-sidebar flex-1 flex flex-col justify-center items-center text-center p-12 min-h-[500px]">
                <CheckSquare class="h-12 w-12 text-muted-foreground/40 mb-3" />
                <h4 class="text-md font-semibold text-foreground">Select a Habit</h4>
                <p class="text-sm text-muted-foreground mt-1 max-w-[280px]">Choose a habit from the list on the left to view completions history, streaks, and analytics.</p>
            </Card>
        </div>
    </div>

    <!-- CREATE HABIT DIALOG -->
    <Dialog v-model:open="isCreateDialogOpen">
        <DialogContent class="sm:max-w-[480px]">
            <DialogHeader>
                <DialogTitle>Track New Habit</DialogTitle>
                <DialogDescription>Setup your new routine habit parameters.</DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submitCreateHabit" class="space-y-4 py-2">
                <div class="space-y-1.5">
                    <Label for="name">Habit Name</Label>
                    <Input id="name" v-model="createForm.name" placeholder="e.g. Read Codebases, Workout, Meditate" required />
                </div>

                <div class="space-y-1.5">
                    <Label for="description">Description (Optional)</Label>
                    <Input id="description" v-model="createForm.description" placeholder="e.g. 1 hour daily focus session" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label for="frequency_type">Frequency</Label>
                        <Select v-model="createForm.frequency_type">
                            <SelectTrigger id="frequency_type">
                                <SelectValue placeholder="Select Frequency" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="daily">Everyday</SelectItem>
                                <SelectItem value="custom_days">Specific Days</SelectItem>
                                <SelectItem value="weekly">Weekly Target</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="color_accent">Accent Color</Label>
                        <Select v-model="createForm.color_accent">
                            <SelectTrigger id="color_accent">
                                <SelectValue placeholder="Select Color" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="emerald">💚 Emerald Green</SelectItem>
                                <SelectItem value="indigo">💙 Indigo Blue</SelectItem>
                                <SelectItem value="amber">💛 Amber Yellow</SelectItem>
                                <SelectItem value="violet">💜 Violet Purple</SelectItem>
                                <SelectItem value="rose">❤️ Rose Red</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Custom Days Checkboxes -->
                <div v-if="createForm.frequency_type === 'custom_days'" class="space-y-2 pt-1">
                    <Label>Scheduled Days</Label>
                    <div class="flex flex-wrap gap-2">
                        <div 
                            v-for="day in daysOfWeek" 
                            :key="day.value"
                            @click="
                                createForm.frequency_days.includes(day.value)
                                    ? createForm.frequency_days = createForm.frequency_days.filter(d => d !== day.value)
                                    : createForm.frequency_days.push(day.value)
                            "
                            class="border px-3 py-1.5 rounded-md text-xs font-semibold select-none cursor-pointer transition-colors"
                            :class="[
                                createForm.frequency_days.includes(day.value)
                                    ? 'bg-primary text-primary-foreground border-transparent'
                                    : 'border-sidebar-border bg-sidebar-accent/10 text-muted-foreground'
                            ]"
                        >
                            {{ day.label }}
                        </div>
                    </div>
                </div>

                <!-- Weekly Count Select -->
                <div v-if="createForm.frequency_type === 'weekly'" class="space-y-1.5">
                    <Label for="frequency_count">Target Times Per Week</Label>
                    <Select v-model="createForm.frequency_count">
                        <SelectTrigger id="frequency_count">
                            <SelectValue placeholder="Select Target Count" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="n in 7" :key="n" :value="n">{{ n }} times a week</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-1.5">
                    <Label for="reminder_time">Telegram Reminder Cutoff (Optional)</Label>
                    <Input id="reminder_time" type="time" v-model="createForm.reminder_time" />
                    <p class="text-[10px] text-muted-foreground">Leave empty to disable bot reminder alerts.</p>
                </div>

                <DialogFooter class="pt-4 border-t border-sidebar-border/40">
                    <Button type="button" variant="outline" @click="isCreateDialogOpen = false">Cancel</Button>
                    <Button type="submit" :disabled="createForm.processing">Create Habit</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- EDIT HABIT DIALOG -->
    <Dialog v-model:open="isEditDialogOpen">
        <DialogContent class="sm:max-w-[480px]">
            <DialogHeader>
                <DialogTitle>Edit Habit</DialogTitle>
                <DialogDescription>Modify habit tracker target configuration parameters.</DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submitEditHabit" class="space-y-4 py-2">
                <div class="space-y-1.5">
                    <Label for="edit_name">Habit Name</Label>
                    <Input id="edit_name" v-model="editForm.name" required />
                </div>

                <div class="space-y-1.5">
                    <Label for="edit_description">Description (Optional)</Label>
                    <Input id="edit_description" v-model="editForm.description" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label for="edit_frequency_type">Frequency</Label>
                        <Select v-model="editForm.frequency_type">
                            <SelectTrigger id="edit_frequency_type">
                                <SelectValue placeholder="Select Frequency" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="daily">Everyday</SelectItem>
                                <SelectItem value="custom_days">Specific Days</SelectItem>
                                <SelectItem value="weekly">Weekly Target</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="edit_color_accent">Accent Color</Label>
                        <Select v-model="editForm.color_accent">
                            <SelectTrigger id="edit_color_accent">
                                <SelectValue placeholder="Select Color" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="emerald">💚 Emerald Green</SelectItem>
                                <SelectItem value="indigo">💙 Indigo Blue</SelectItem>
                                <SelectItem value="amber">💛 Amber Yellow</SelectItem>
                                <SelectItem value="violet">💜 Violet Purple</SelectItem>
                                <SelectItem value="rose">❤️ Rose Red</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Custom Days Checkboxes -->
                <div v-if="editForm.frequency_type === 'custom_days'" class="space-y-2 pt-1">
                    <Label>Scheduled Days</Label>
                    <div class="flex flex-wrap gap-2">
                        <div 
                            v-for="day in daysOfWeek" 
                            :key="day.value"
                            @click="
                                editForm.frequency_days.includes(day.value)
                                    ? editForm.frequency_days = editForm.frequency_days.filter(d => d !== day.value)
                                    : editForm.frequency_days.push(day.value)
                            "
                            class="border px-3 py-1.5 rounded-md text-xs font-semibold select-none cursor-pointer transition-colors"
                            :class="[
                                editForm.frequency_days.includes(day.value)
                                    ? 'bg-primary text-primary-foreground border-transparent'
                                    : 'border-sidebar-border bg-sidebar-accent/10 text-muted-foreground'
                            ]"
                        >
                            {{ day.label }}
                        </div>
                    </div>
                </div>

                <!-- Weekly Count Select -->
                <div v-if="editForm.frequency_type === 'weekly'" class="space-y-1.5">
                    <Label for="edit_frequency_count">Target Times Per Week</Label>
                    <Select v-model="editForm.frequency_count">
                        <SelectTrigger id="edit_frequency_count">
                            <SelectValue placeholder="Select Target Count" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="n in 7" :key="n" :value="n">{{ n }} times a week</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-1.5">
                    <Label for="edit_reminder_time">Telegram Reminder Cutoff (Optional)</Label>
                    <Input id="edit_reminder_time" type="time" v-model="editForm.reminder_time" />
                    <p class="text-[10px] text-muted-foreground">Leave empty to disable bot reminder alerts.</p>
                </div>

                <DialogFooter class="pt-4 border-t border-sidebar-border/40">
                    <Button type="button" variant="outline" @click="isEditDialogOpen = false">Cancel</Button>
                    <Button type="submit" :disabled="editForm.processing">Save Changes</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- ARCHIVE CONFIRM DIALOG -->
    <Dialog v-model:open="isArchiveDialogOpen">
        <DialogContent class="sm:max-w-[420px]">
            <DialogHeader>
                <DialogTitle>{{ selectedHabit?.is_active ? 'Archive Habit' : 'Restore Habit' }}</DialogTitle>
                <DialogDescription>
                    {{ selectedHabit?.is_active 
                        ? 'Are you sure you want to archive this habit? It will be removed from your active checklist but your streaks history will be preserved.'
                        : 'Are you sure you want to restore this habit to your active checklist?' }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2 sm:gap-0">
                <Button variant="outline" @click="isArchiveDialogOpen = false">Cancel</Button>
                <Button 
                    :class="selectedHabit?.is_active ? 'bg-amber-600 hover:bg-amber-500' : 'bg-primary hover:bg-primary/90'"
                    @click="toggleArchiveHabit"
                >
                    {{ selectedHabit?.is_active ? 'Archive' : 'Restore' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- DELETE CONFIRM DIALOG -->
    <Dialog v-model:open="isDeleteDialogOpen">
        <DialogContent class="sm:max-w-[420px]">
            <DialogHeader>
                <DialogTitle class="text-rose-500 flex items-center gap-1.5">
                    <AlertCircle class="h-5 w-5" />
                    <span>Delete Habit</span>
                </DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete this habit? This will permanently delete the habit and all of its historical logs. This action cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2 sm:gap-0">
                <Button variant="outline" @click="isDeleteDialogOpen = false">Cancel</Button>
                <Button variant="destructive" @click="deleteHabit">Delete Permanently</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
/* Thin scrollbar style */
.scrollbar-thin::-webkit-scrollbar {
    height: 6px;
}
.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background: rgba(100, 116, 139, 0.2);
    border-radius: 9px;
}
.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: rgba(100, 116, 139, 0.4);
}
</style>

<script setup lang="ts">
import { usePage, router } from '@inertiajs/vue3';
import {
    Sun,
    SunMedium,
    Sunset,
    Moon,
    Quote,
    Copy,
    RefreshCw,
    CheckCircle2,
    Circle,
    Calendar,
    CheckSquare,
    Sparkles,
    Flame,
    Clock,
    X,
    Check,
} from '@lucide/vue';
import { ref, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent } from '@/components/ui/dialog';

interface QuoteData {
    quote: string;
    author: string;
    category: string;
    source: string;
}

interface HabitItem {
    id: number;
    name: string;
    description?: string;
    color_accent: string;
    streak_current: number;
    is_completed: boolean;
}

interface TaskItem {
    id: number;
    title: string;
    priority: string;
    status: string;
    due_date_formatted?: string;
    is_overdue: boolean;
    project_name?: string;
    project_color?: string;
}

interface EventItem {
    id: number;
    title: string;
    start_time: string;
    end_time: string;
    color: string;
}

interface WelcomeData {
    greeting: string;
    greeting_icon: string;
    submessage: string;
    date_formatted: string;
    user_name: string;
    quote: QuoteData;
    summary: {
        habits_completed_count: number;
        total_habits_today: number;
        pending_tasks_count: number;
        events_today_count: number;
    };
    habits: HabitItem[];
    tasks: TaskItem[];
    events: EventItem[];
}

const page = usePage();
const isOpen = ref(false);
const isLoading = ref(true);
const isShufflingQuote = ref(false);
const copiedQuote = ref(false);
const dontShowToday = ref(false);
const welcomeData = ref<WelcomeData | null>(null);

const getTodayKey = () => {
    const d = new Date();
    return `daily_welcome_dismissed_${d.getFullYear()}-${d.getMonth() + 1}-${d.getDate()}`;
};

const checkAndOpenModal = async () => {
    const authProp = page.props.auth as { showDailyWelcome?: boolean } | undefined;
    const showDailyWelcome = authProp?.showDailyWelcome ?? false;
    const dismissed = localStorage.getItem(getTodayKey()) === 'true';

    if (showDailyWelcome && !dismissed) {
        isOpen.value = true;
        await fetchDailyData();
    }
};

const fetchDailyData = async () => {
    isLoading.value = true;
    try {
        const response = await fetch('/app/daily-welcome', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (response.ok) {
            welcomeData.value = await response.json();
        }
    } catch (e) {
        console.error('Failed to fetch daily welcome summary:', e);
    } finally {
        isLoading.value = false;
    }
};

const shuffleQuote = async () => {
    if (isShufflingQuote.value) return;
    isShufflingQuote.value = true;
    try {
        const response = await fetch('/app/daily-welcome/quote?try_external=1', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (response.ok) {
            const data = await response.json();
            if (welcomeData.value && data.quote) {
                welcomeData.value.quote = data.quote;
                toast.success('Quote berhasil diperbarui');
            }
        }
    } catch (e) {
        toast.error('Gagal mengambil quote baru.');
    } finally {
        isShufflingQuote.value = false;
    }
};

const copyQuoteToClipboard = () => {
    if (!welcomeData.value?.quote) return;
    const text = `"${welcomeData.value.quote.quote}" - ${welcomeData.value.quote.author}`;
    navigator.clipboard.writeText(text);
    copiedQuote.value = true;
    toast.success('Quote telah disalin ke clipboard!');
    setTimeout(() => {
        copiedQuote.value = false;
    }, 2000);
};

const toggleHabit = async (habit: HabitItem) => {
    habit.is_completed = !habit.is_completed;
    if (habit.is_completed) {
        habit.streak_current += 1;
        if (welcomeData.value) {
            welcomeData.value.summary.habits_completed_count += 1;
        }
    } else {
        habit.streak_current = Math.max(0, habit.streak_current - 1);
        if (welcomeData.value) {
            welcomeData.value.summary.habits_completed_count = Math.max(
                0,
                welcomeData.value.summary.habits_completed_count - 1
            );
        }
    }

    const now = new Date();
    const offset = now.getTimezoneOffset();
    const localDate = new Date(now.getTime() - offset * 60 * 1000);
    const dateStr = localDate.toISOString().split('T')[0];

    router.post(
        `/app/habits/${habit.id}/toggle`,
        { date: dateStr },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.success(
                    habit.is_completed
                        ? `Habit "${habit.name}" diselesaikan!`
                        : `Status habit "${habit.name}" diperbarui.`
                );
            },
            onError: (err) => {
                habit.is_completed = !habit.is_completed;
                toast.error(err.date || 'Gagal memperbarui status habit.');
            },
        }
    );
};

const closeModal = () => {
    if (dontShowToday.value) {
        localStorage.setItem(getTodayKey(), 'true');
    }
    isOpen.value = false;
};

onMounted(() => {
    checkAndOpenModal();
});
</script>

<template>
    <Dialog :open="isOpen" @update:open="closeModal">
        <DialogContent show-close-button class="p-0 overflow-hidden sm:max-w-2xl max-h-[90vh] flex flex-col gap-0 border-none shadow-2xl rounded-2xl bg-background">
            <!-- Loading Skeleton -->
            <div v-if="isLoading" class="p-8 space-y-4 animate-pulse">
                <div class="h-24 bg-muted/60 rounded-xl"></div>
                <div class="h-32 bg-muted/40 rounded-xl"></div>
                <div class="h-40 bg-muted/30 rounded-xl"></div>
            </div>

            <template v-else-if="welcomeData">
                <!-- Header Banner -->
                <div class="relative px-6 pt-6 pb-5 overflow-hidden bg-gradient-to-br from-indigo-600 via-purple-600 to-blue-700 text-white">
                    <!-- Decorative subtle glow elements -->
                    <div class="absolute -right-10 -top-10 w-44 h-44 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                    <div class="absolute right-20 -bottom-10 w-32 h-32 bg-purple-400/20 rounded-full blur-xl pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="inline-flex items-center gap-2 px-3 py-1 text-xs font-medium bg-white/15 backdrop-blur-md rounded-full text-white/90 border border-white/20">
                                <Sun v-if="welcomeData.greeting_icon === 'sun'" class="w-3.5 h-3.5 text-amber-300" />
                                <SunMedium v-else-if="welcomeData.greeting_icon === 'sun-medium'" class="w-3.5 h-3.5 text-amber-300" />
                                <Sunset v-else-if="welcomeData.greeting_icon === 'sunset'" class="w-3.5 h-3.5 text-orange-300" />
                                <Moon v-else class="w-3.5 h-3.5 text-blue-200" />
                                <span>{{ welcomeData.date_formatted }}</span>
                            </div>
                            <h2 class="text-2xl font-extrabold tracking-tight">
                                {{ welcomeData.greeting }}, {{ welcomeData.user_name }}!
                            </h2>
                            <p class="text-sm text-indigo-100/90 font-normal">
                                {{ welcomeData.submessage }}
                            </p>
                        </div>

                        <!-- Mini Stats Badge Pill -->
                        <div class="flex sm:flex-col items-center sm:items-end gap-2 bg-white/10 backdrop-blur-md p-3 rounded-xl border border-white/15 shrink-0">
                            <div class="flex items-center gap-1.5 text-xs text-indigo-100 font-medium">
                                <Sparkles class="w-3.5 h-3.5 text-amber-300 shrink-0" />
                                <span>Target Hari Ini</span>
                            </div>
                            <div class="flex items-center gap-3 text-xs font-bold">
                                <span class="bg-white/20 px-2 py-0.5 rounded-md text-white">
                                    {{ welcomeData.summary.habits_completed_count }}/{{ welcomeData.summary.total_habits_today }} Habit
                                </span>
                                <span class="bg-white/20 px-2 py-0.5 rounded-md text-white">
                                    {{ welcomeData.summary.pending_tasks_count }} Task
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scrollable Body Content -->
                <div class="p-6 overflow-y-auto space-y-5 flex-1 max-h-[calc(90vh-200px)]">
                    <!-- Daily Quote Card -->
                    <div class="relative group p-4 rounded-xl bg-gradient-to-r from-amber-500/10 via-purple-500/10 to-indigo-500/10 border border-amber-500/20 dark:border-amber-400/10 backdrop-blur-sm transition-all duration-200">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <div class="flex items-center gap-2">
                                <span class="p-1.5 rounded-lg bg-amber-500/15 text-amber-600 dark:text-amber-400">
                                    <Quote class="w-4 h-4" />
                                </span>
                                <span class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">
                                    Quote Hari Ini
                                </span>
                                <Badge variant="secondary" class="text-[10px] px-2 py-0 font-medium bg-background/80">
                                    {{ welcomeData.quote.category }}
                                </Badge>
                            </div>

                            <div class="flex items-center gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-7 w-7 text-muted-foreground hover:text-foreground"
                                    title="Copy Quote"
                                    @click="copyQuoteToClipboard"
                                >
                                    <Check v-if="copiedQuote" class="w-3.5 h-3.5 text-emerald-500" />
                                    <Copy v-else class="w-3.5 h-3.5" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-7 w-7 text-muted-foreground hover:text-foreground"
                                    title="Acak Quote Lain"
                                    :disabled="isShufflingQuote"
                                    @click="shuffleQuote"
                                >
                                    <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isShufflingQuote }" />
                                </Button>
                            </div>
                        </div>

                        <p class="text-sm font-medium italic text-foreground leading-relaxed pl-1 border-l-2 border-amber-500/40 my-2">
                            "{{ welcomeData.quote.quote }}"
                        </p>
                        <div class="flex items-center justify-between text-xs text-muted-foreground mt-2">
                            <span class="font-semibold text-foreground/80">— {{ welcomeData.quote.author }}</span>
                            <span class="text-[11px] opacity-75">{{ welcomeData.quote.source }}</span>
                        </div>
                    </div>

                    <!-- Quick Habit Tracker Widget -->
                    <div v-if="welcomeData.habits.length > 0" class="space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <CheckSquare class="w-4 h-4 text-emerald-500" />
                                <h3 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                    Quick Habit Check-in Hari Ini
                                </h3>
                            </div>
                            <span class="text-xs font-semibold text-muted-foreground">
                                {{ welcomeData.summary.habits_completed_count }} selesai
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div
                                v-for="habit in welcomeData.habits"
                                :key="habit.id"
                                class="flex items-center justify-between p-2.5 rounded-lg border bg-card hover:bg-accent/40 transition-colors cursor-pointer"
                                @click="toggleHabit(habit)"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <button
                                        type="button"
                                        class="shrink-0 transition-transform active:scale-90"
                                    >
                                        <CheckCircle2
                                            v-if="habit.is_completed"
                                            class="w-5 h-5 text-emerald-500 fill-emerald-500/20"
                                        />
                                        <Circle
                                            v-else
                                            class="w-5 h-5 text-muted-foreground/60 hover:text-emerald-500"
                                        />
                                    </button>

                                    <div class="truncate">
                                        <p
                                            class="text-xs font-medium truncate"
                                            :class="{ 'line-through text-muted-foreground': habit.is_completed }"
                                        >
                                            {{ habit.name }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] font-semibold text-amber-600 bg-amber-500/10 shrink-0">
                                    <Flame class="w-3 h-3 text-amber-500 fill-amber-500" />
                                    <span>{{ habit.streak_current }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Schedule & Tasks Overview -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Tasks Column -->
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <Clock class="w-4 h-4 text-blue-500" />
                                <h3 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                    Tugas Hari Ini ({{ welcomeData.tasks.length }})
                                </h3>
                            </div>                            <div v-if="welcomeData.tasks.length === 0" class="p-3 text-center border border-dashed rounded-lg text-xs text-muted-foreground">
                                Tidak ada tugas tenggat hari ini
                            </div>
                            <div v-else class="space-y-1.5">
                                <div
                                    v-for="task in welcomeData.tasks"
                                    :key="task.id"
                                    class="p-2.5 rounded-lg border bg-card text-xs flex items-center justify-between gap-2"
                                >
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span
                                            class="w-2 h-2 rounded-full shrink-0"
                                            :style="{ backgroundColor: task.project_color }"
                                        ></span>
                                        <span class="font-medium truncate">{{ task.title }}</span>
                                    </div>

                                    <div class="flex items-center gap-1 shrink-0">
                                        <Badge
                                            v-if="task.is_overdue"
                                            variant="destructive"
                                            class="text-[10px] px-1.5 py-0 font-medium"
                                        >
                                            Terlewat
                                        </Badge>
                                        <Badge
                                            v-else-if="task.priority === 'urgent'"
                                            variant="destructive"
                                            class="text-[10px] px-1.5 py-0 font-medium"
                                        >
                                            Urgent
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Calendar Events Column -->
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <Calendar class="w-4 h-4 text-purple-500" />
                                <h3 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                    Agenda Kalender ({{ welcomeData.events.length }})
                                </h3>
                            </div>

                            <div v-if="welcomeData.events.length === 0" class="p-3 text-center border border-dashed rounded-lg text-xs text-muted-foreground">
                                Agenda hari ini masih kosong
                            </div>
                            <div v-else class="space-y-1.5">
                                <div
                                    v-for="event in welcomeData.events"
                                    :key="event.id"
                                    class="p-2.5 rounded-lg border bg-card text-xs flex items-center justify-between gap-2"
                                >
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span
                                            class="w-2 h-2 rounded-full shrink-0"
                                            :style="{ backgroundColor: event.color }"
                                        ></span>
                                        <span class="font-medium truncate">{{ event.title }}</span>
                                    </div>
                                    <span class="text-[11px] text-muted-foreground font-mono shrink-0">
                                        {{ event.start_time }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Bar -->
                <div class="p-4 px-6 border-t bg-muted/30 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <label class="flex items-center gap-2 text-xs text-muted-foreground cursor-pointer select-none">
                        <Checkbox v-model:checked="dontShowToday" />
                        <span>Jangan tampilkan lagi hari ini</span>
                    </label>

                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <Button
                            variant="outline"
                            size="sm"
                            class="w-full sm:w-auto text-xs"
                            @click="closeModal"
                        >
                            Tutup
                        </Button>
                        <Button
                            variant="default"
                            size="sm"
                            class="w-full sm:w-auto text-xs gap-1.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white shadow-md"
                            @click="closeModal"
                        >
                            <Sparkles class="w-3.5 h-3.5" />
                            <span>Mulai Hari Ini</span>
                        </Button>
                    </div>
                </div>
            </template>
        </DialogContent>
    </Dialog>
</template>

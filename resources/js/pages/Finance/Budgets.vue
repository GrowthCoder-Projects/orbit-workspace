<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import * as LucideIcons from '@lucide/vue';
import {
    Plus,
    Pencil,
    Trash2,
    Calendar,
    ChevronLeft,
    ChevronRight,
    HelpCircle,
    Info,
    TrendingUp,
} from '@lucide/vue';
import { ref, computed } from 'vue';
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
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
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
import { useConfirm } from '@/composables/useConfirm';
import {
    index as budgetsIndex,
    store as storeBudget,
    destroy as destroyBudget,
} from '@/routes/budgets';

const { confirm } = useConfirm();

const props = defineProps<{
    budgets: any[];
    selectedYear: number;
    selectedMonth: number;
    categories: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Finance Dashboard',
                href: '/app/finance',
            },
            {
                title: 'Anggaran',
                href: budgetsIndex().url,
            },
        ],
    },
});

const isModalOpen = ref(false);
const activeMonth = ref(props.selectedMonth);
const activeYear = ref(props.selectedYear);

const form = useForm({
    category_id: '',
    amount: 0,
    year: props.selectedYear,
    month: props.selectedMonth,
});

// Month Name Array
const monthNames = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

// Form conversions & options for Month & Year Selects
const selectedMonthString = computed({
    get: () => form.month.toString(),
    set: (val) => {
        form.month = parseInt(val, 10);
    },
});

const selectedYearString = computed({
    get: () => form.year.toString(),
    set: (val) => {
        form.year = parseInt(val, 10);
    },
});

const availableYears = computed(() => {
    const currentYear = new Date().getFullYear();
    const years = [];

    for (let y = currentYear - 2; y <= currentYear + 5; y++) {
        years.push(y);
    }

    return years;
});

// Category validation: disable categories that already have budgets for the selected month/year
const budgetedCategoryIds = computed(() => {
    if (form.month === activeMonth.value && form.year === activeYear.value) {
        return props.budgets
            .filter((b) => b.budget_id !== null)
            .map((b) => b.category_id.toString());
    }

    return [];
});

const isCategoryDisabled = (catId: number) => {
    const idStr = catId.toString();

    if (form.category_id === idStr) {
        return false;
    }

    return budgetedCategoryIds.value.includes(idStr);
};

const openBudgetModal = (budgetData?: any) => {
    form.reset();
    form.year = activeYear.value;
    form.month = activeMonth.value;

    if (budgetData) {
        form.category_id = budgetData.category_id.toString();
        form.amount = budgetData.budget_amount;
    }

    isModalOpen.value = true;
};

const handleSave = () => {
    form.post(storeBudget().url, {
        onSuccess: () => {
            isModalOpen.value = false;
            form.reset();
        },
    });
};

const handleDelete = async (budgetId: number) => {
    const isConfirmed = await confirm({
        title: 'Hapus Anggaran',
        message: 'Apakah Anda yakin ingin menghapus anggaran kategori ini?',
        confirmText: 'Hapus',
        cancelText: 'Batal',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(destroyBudget(budgetId).url);
    }
};

const changeMonth = (direction: 'next' | 'prev') => {
    if (direction === 'next') {
        if (activeMonth.value === 12) {
            activeMonth.value = 1;
            activeYear.value += 1;
        } else {
            activeMonth.value += 1;
        }
    } else {
        if (activeMonth.value === 1) {
            activeMonth.value = 12;
            activeYear.value -= 1;
        } else {
            activeMonth.value -= 1;
        }
    }

    router.get(
        budgetsIndex().url,
        {
            month: activeMonth.value,
            year: activeYear.value,
        },
        { preserveState: true },
    );
};

// Formats
const formatIDR = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

const getIconComponent = (iconName: string) => {
    return (LucideIcons as any)[iconName] || HelpCircle;
};

// Helper algorithms for warning color
const getProgressPercent = (spent: number, limit: number) => {
    if (limit <= 0) {
return 0;
}

    return Math.round((spent / limit) * 100);
};

const getProgressColorClass = (percent: number) => {
    if (percent >= 100) {
return 'bg-rose-500';
}

    if (percent >= 80) {
return 'bg-amber-500';
}

    return 'bg-emerald-500';
};

const getProgressTextClass = (percent: number) => {
    if (percent >= 100) {
return 'text-rose-500';
}

    if (percent >= 80) {
return 'text-amber-500';
}

    return 'text-emerald-500';
};
</script>

<template>
    <Head title="Anggaran Pengeluaran" />

    <div class="space-y-6 px-6 py-6">
        <!-- Header -->
        <div
            class="flex flex-col justify-between gap-4 border-b border-border pb-5 md:flex-row md:items-center"
        >
            <div class="space-y-1">
                <h2
                    class="font-outfit flex items-center gap-2 text-2xl font-bold tracking-tight"
                >
                    <TrendingUp class="size-6 text-emerald-500" />
                    Anggaran Bulanan
                </h2>
                <p class="text-sm text-muted-foreground">
                    Rencanakan batas maksimal pengeluaran per kategori setiap
                    bulannya untuk mengontrol cash flow.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <!-- Date Navigation -->
                <div class="flex items-center rounded-lg border p-0.5">
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-7 rounded hover:bg-muted"
                        @click="changeMonth('prev')"
                    >
                        <ChevronLeft class="size-4" />
                    </Button>
                    <span
                        class="min-w-32 px-3 text-center font-mono text-xs font-semibold whitespace-nowrap text-foreground"
                    >
                        {{ monthNames[activeMonth - 1] }} {{ activeYear }}
                    </span>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-7 rounded hover:bg-muted"
                        @click="changeMonth('next')"
                    >
                        <ChevronRight class="size-4" />
                    </Button>
                </div>

                <Button
                    size="sm"
                    @click="openBudgetModal()"
                    class="flex items-center gap-1.5"
                >
                    <Plus class="size-4" />
                    Atur Anggaran
                </Button>
            </div>
        </div>

        <!-- Budget Cards List -->
        <div class="grid gap-6 md:grid-cols-2">
            <Card
                v-for="b in budgets"
                :key="b.category_id"
                class="group relative overflow-hidden shadow-sm transition duration-200 hover:border-primary/30"
            >
                <CardHeader class="pb-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="rounded-lg p-2"
                                :style="{
                                    backgroundColor: b.category_color + '15',
                                    color: b.category_color,
                                }"
                            >
                                <component
                                    :is="getIconComponent(b.category_icon)"
                                    class="size-5"
                                />
                            </div>
                            <div>
                                <CardTitle
                                    class="font-outfit text-base font-bold text-foreground"
                                    >{{ b.category_name }}</CardTitle
                                >
                                <CardDescription
                                    class="text-xs text-muted-foreground"
                                    >Anggaran Kategori</CardDescription
                                >
                            </div>
                        </div>

                        <!-- Actions -->
                        <div
                            class="flex items-center gap-1 opacity-0 transition duration-150 group-hover:opacity-100"
                        >
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-7 hover:bg-muted"
                                @click="openBudgetModal(b)"
                            >
                                <Pencil
                                    class="size-3.5 text-muted-foreground"
                                />
                            </Button>
                            <Button
                                v-if="b.budget_id"
                                variant="ghost"
                                size="icon"
                                class="size-7 hover:bg-muted"
                                @click="handleDelete(b.budget_id)"
                            >
                                <Trash2 class="size-3.5 text-rose-400" />
                            </Button>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="space-y-4">
                    <!-- Progress Bar & Amounts -->
                    <div class="space-y-2">
                        <div class="flex items-end justify-between text-xs">
                            <div class="font-mono text-muted-foreground">
                                <span class="font-bold text-foreground">{{
                                    formatIDR(b.actual_spent)
                                }}</span>
                                <span class="text-muted-foreground"> / </span>
                                <span>{{
                                    b.budget_amount > 0
                                        ? formatIDR(b.budget_amount)
                                        : 'Belum diatur'
                                }}</span>
                            </div>
                            <div
                                class="font-mono font-bold"
                                :class="
                                    getProgressTextClass(
                                        getProgressPercent(
                                            b.actual_spent,
                                            b.budget_amount,
                                        ),
                                    )
                                "
                            >
                                {{
                                    getProgressPercent(
                                        b.actual_spent,
                                        b.budget_amount,
                                    )
                                }}%
                            </div>
                        </div>

                        <!-- Progress Line -->
                        <div
                            class="h-2 w-full overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full rounded-full transition-all duration-300"
                                :class="
                                    getProgressColorClass(
                                        getProgressPercent(
                                            b.actual_spent,
                                            b.budget_amount,
                                        ),
                                    )
                                "
                                :style="{
                                    width:
                                        Math.min(
                                            getProgressPercent(
                                                b.actual_spent,
                                                b.budget_amount,
                                            ),
                                            100,
                                        ) + '%',
                                }"
                            ></div>
                        </div>
                    </div>

                    <!-- Warnings -->
                    <div
                        v-if="
                            Number(b.budget_amount) > 0 &&
                            Number(b.actual_spent) > Number(b.budget_amount)
                        "
                        class="flex items-center gap-1.5 rounded-lg bg-rose-500/10 p-2 text-xs text-rose-500"
                    >
                        <Info class="size-4 shrink-0" />
                        <span
                            >Pengeluaran Anda telah melebihi anggaran yang
                            direncanakan!</span
                        >
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- SET BUDGET MODAL -->
        <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="font-outfit"
                        >Tentukan Anggaran Kategori</DialogTitle
                    >
                    <DialogDescription class="font-inter text-muted-foreground">
                        Rencanakan nominal anggaran pengeluaran untuk bulan
                        {{ monthNames[activeMonth - 1] }} {{ activeYear }}.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="handleSave" class="space-y-4 py-2">
                    <div class="space-y-2">
                        <Label class="text-foreground/80"
                            >Kategori Pengeluaran</Label
                        >
                        <Select v-model="form.category_id" required>
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Pilih Kategori" />
                            </SelectTrigger>
                            <SelectContent class="">
                                <SelectItem
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    :value="cat.id.toString()"
                                    :disabled="isCategoryDisabled(cat.id)"
                                >
                                    {{ cat.name }}
                                    <span
                                        v-if="isCategoryDisabled(cat.id)"
                                        class="text-xs text-muted-foreground"
                                    >
                                        (Sudah diatur)
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <Label for="budget-amount" class="text-foreground/80"
                            >Nominal Anggaran (IDR)</Label
                        >
                        <Input
                            id="budget-amount"
                            type="number"
                            step="1"
                            v-model="form.amount"
                            class="font-mono text-foreground"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label class="text-xs text-muted-foreground"
                                >Bulan</Label
                            >
                            <Select v-model="selectedMonthString">
                                <SelectTrigger class="h-9 w-full">
                                    <SelectValue placeholder="Pilih Bulan" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="(name, index) in monthNames"
                                        :key="index"
                                        :value="(index + 1).toString()"
                                    >
                                        {{ name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label class="text-xs text-muted-foreground"
                                >Tahun</Label
                            >
                            <Select v-model="selectedYearString">
                                <SelectTrigger class="h-9 w-full">
                                    <SelectValue placeholder="Pilih Tahun" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="yr in availableYears"
                                        :key="yr"
                                        :value="yr.toString()"
                                    >
                                        {{ yr }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <DialogFooter class="pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isModalOpen = false"
                            class="hover:bg-muted"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            class="bg-emerald-600 text-foreground hover:bg-emerald-700"
                            :disabled="form.processing"
                            >Simpan Anggaran</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    BarChart3,
    TrendingUp,
    TrendingDown,
    PiggyBank,
    ChevronLeft,
    ChevronRight,
    PieChart,
} from '@lucide/vue';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    ArcElement,
} from 'chart.js';
import { ref } from 'vue';
import { Bar, Doughnut } from 'vue-chartjs';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
} from '@/components/ui/card';
import { index as reportsIndex } from '@/routes/finance/reports';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    ArcElement,
);

const props = defineProps<{
    cashFlowChartData: any;
    categoryChartData: any;
    totalYearIncome: number;
    totalYearExpense: number;
    totalYearSavings: number;
    selectedYear: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Finance Dashboard',
                href: '/app/finance',
            },
            {
                title: 'Laporan & Analisis',
                href: reportsIndex().url,
            },
        ],
    },
});

const activeYear = ref(props.selectedYear);

const changeYear = (direction: 'next' | 'prev') => {
    activeYear.value =
        direction === 'next' ? activeYear.value + 1 : activeYear.value - 1;
    router.get(
        reportsIndex().url,
        { year: activeYear.value },
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

// Chart.js Options — soft grid lines
const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top' as const,
            labels: {
                color: '#71717a',
                font: { family: 'Inter, sans-serif', size: 11 },
                boxWidth: 10,
                boxHeight: 10,
                borderRadius: 3,
                useBorderRadius: true,
                padding: 16,
            },
        },
        tooltip: {
            backgroundColor: 'rgba(15,15,20,0.85)',
            borderColor: 'rgba(255,255,255,0.08)',
            borderWidth: 1,
            padding: 10,
            titleFont: { family: 'Outfit, sans-serif', size: 12 },
            bodyFont: { family: 'Inter, sans-serif', size: 11 },
            titleColor: '#e4e4e7',
            bodyColor: '#a1a1aa',
            cornerRadius: 8,
        },
    },
    scales: {
        x: {
            grid: {
                color: 'rgba(113,113,122,0.10)',
                lineWidth: 1,
            },
            border: { dash: [4, 4], color: 'transparent' },
            ticks: {
                color: '#71717a',
                font: { family: 'Inter, sans-serif', size: 11 },
            },
        },
        y: {
            grid: {
                color: 'rgba(113,113,122,0.10)',
                lineWidth: 1,
            },
            border: { dash: [4, 4], color: 'transparent' },
            ticks: {
                color: '#71717a',
                font: { family: 'Inter, sans-serif', size: 11 },
            },
        },
    },
};

const doughnutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '68%',
    plugins: {
        legend: {
            position: 'right' as const,
            labels: {
                color: '#71717a',
                font: { family: 'Inter, sans-serif', size: 11 },
                boxWidth: 10,
                boxHeight: 10,
                borderRadius: 3,
                useBorderRadius: true,
                padding: 12,
            },
        },
        tooltip: {
            backgroundColor: 'rgba(15,15,20,0.85)',
            borderColor: 'rgba(255,255,255,0.08)',
            borderWidth: 1,
            padding: 10,
            titleFont: { family: 'Outfit, sans-serif', size: 12 },
            bodyFont: { family: 'Inter, sans-serif', size: 11 },
            titleColor: '#e4e4e7',
            bodyColor: '#a1a1aa',
            cornerRadius: 8,
        },
    },
};
</script>

<template>
    <Head title="Laporan & Analisis Finansial" />

    <div class="space-y-8 px-6 py-6">
        <!-- Page Header -->
        <div
            class="flex flex-col justify-between gap-4 border-b border-border/50 pb-6 md:flex-row md:items-center"
        >
            <div class="space-y-1">
                <h2
                    class="font-outfit flex items-center gap-2.5 text-2xl font-bold tracking-tight"
                >
                    <span
                        class="flex size-9 items-center justify-center rounded-xl bg-indigo-500/10"
                    >
                        <BarChart3 class="size-5 text-indigo-400" />
                    </span>
                    Laporan & Analitik Finansial
                </h2>
                <p class="font-inter text-sm text-muted-foreground">
                    Analisis data cash flow tahunan dan struktur pengeluaran
                    bulanan Anda untuk pengambilan keputusan finansial yang
                    tepat.
                </p>
            </div>

            <!-- Year Selector -->
            <div
                class="flex items-center gap-1 rounded-xl border border-border/50 bg-muted/20 p-1 shadow-2xs"
            >
                <Button
                    variant="ghost"
                    size="icon"
                    class="size-7 rounded-lg hover:bg-muted"
                    @click="changeYear('prev')"
                >
                    <ChevronLeft class="size-4" />
                </Button>
                <span
                    class="px-4 font-mono text-xs font-semibold whitespace-nowrap text-foreground"
                >
                    Tahun {{ activeYear }}
                </span>
                <Button
                    variant="ghost"
                    size="icon"
                    class="size-7 rounded-lg hover:bg-muted"
                    @click="changeYear('next')"
                >
                    <ChevronRight class="size-4" />
                </Button>
            </div>
        </div>

        <!-- Annual Summary Cards -->
        <div class="grid gap-4 md:grid-cols-3">
            <!-- Income Card -->
            <div
                class="rounded-2xl border border-border/50 bg-card/30 p-5 shadow-2xs backdrop-blur-xs"
            >
                <div class="mb-4 flex items-center gap-2.5">
                    <div
                        class="flex size-8 items-center justify-center rounded-lg bg-emerald-500/10"
                    >
                        <TrendingUp class="size-4 text-emerald-400" />
                    </div>
                    <span
                        class="font-inter text-xs font-medium tracking-wider text-muted-foreground uppercase"
                        >Total Pemasukan Tahunan</span
                    >
                </div>
                <div
                    class="font-mono text-2xl font-extrabold tracking-tight text-foreground"
                >
                    {{ formatIDR(totalYearIncome) }}
                </div>
                <p class="font-inter mt-1.5 text-[11px] text-muted-foreground">
                    Akumulasi seluruh kas masuk tahun ini.
                </p>
            </div>

            <!-- Expense Card -->
            <div
                class="rounded-2xl border border-border/50 bg-card/30 p-5 shadow-2xs backdrop-blur-xs"
            >
                <div class="mb-4 flex items-center gap-2.5">
                    <div
                        class="flex size-8 items-center justify-center rounded-lg bg-rose-500/10"
                    >
                        <TrendingDown class="size-4 text-rose-400" />
                    </div>
                    <span
                        class="font-inter text-xs font-medium tracking-wider text-muted-foreground uppercase"
                        >Total Pengeluaran Tahunan</span
                    >
                </div>
                <div
                    class="font-mono text-2xl font-extrabold tracking-tight text-foreground"
                >
                    {{ formatIDR(totalYearExpense) }}
                </div>
                <p class="font-inter mt-1.5 text-[11px] text-muted-foreground">
                    Akumulasi seluruh kas keluar tahun ini.
                </p>
            </div>

            <!-- Savings Card -->
            <div
                class="rounded-2xl border p-5 shadow-2xs backdrop-blur-xs"
                :class="
                    totalYearSavings >= 0
                        ? 'border-indigo-500/20 bg-indigo-500/5'
                        : 'border-rose-500/20 bg-rose-500/5'
                "
            >
                <div class="mb-4 flex items-center gap-2.5">
                    <div
                        class="flex size-8 items-center justify-center rounded-lg"
                        :class="
                            totalYearSavings >= 0
                                ? 'bg-indigo-500/10'
                                : 'bg-rose-500/10'
                        "
                    >
                        <PiggyBank
                            class="size-4"
                            :class="
                                totalYearSavings >= 0
                                    ? 'text-indigo-400'
                                    : 'text-rose-400'
                            "
                        />
                    </div>
                    <span
                        class="font-inter text-xs font-medium tracking-wider text-muted-foreground uppercase"
                        >Total Selisih / Tabungan</span
                    >
                </div>
                <div
                    class="font-mono text-2xl font-extrabold tracking-tight"
                    :class="
                        totalYearSavings >= 0
                            ? 'text-indigo-400'
                            : 'text-rose-400'
                    "
                >
                    {{ totalYearSavings >= 0 ? '+' : ''
                    }}{{ formatIDR(totalYearSavings) }}
                </div>
                <p class="font-inter mt-1.5 text-[11px] text-muted-foreground">
                    Sisa cash flow bersih (Pemasukan − Pengeluaran).
                </p>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="grid gap-6 md:grid-cols-3">
            <!-- Bar Chart — Cash Flow Bulanan -->
            <div
                class="rounded-2xl border border-border/50 bg-card/30 p-6 shadow-2xs backdrop-blur-xs md:col-span-2"
            >
                <div class="mb-1 flex items-center gap-2">
                    <div
                        class="flex size-7 items-center justify-center rounded-lg bg-indigo-500/10"
                    >
                        <BarChart3 class="size-3.5 text-indigo-400" />
                    </div>
                    <h3 class="font-outfit text-sm font-bold text-foreground">
                        Perbandingan Cash Flow Bulanan
                    </h3>
                </div>
                <p class="font-inter mb-5 text-[11px] text-muted-foreground">
                    Perbandingan antara pemasukan dan pengeluaran setiap
                    bulannya.
                </p>
                <div class="h-72">
                    <Bar :data="cashFlowChartData" :options="barChartOptions" />
                </div>
            </div>

            <!-- Doughnut Chart — Pengeluaran per Kategori -->
            <div
                class="rounded-2xl border border-border/50 bg-card/30 p-6 shadow-2xs backdrop-blur-xs"
            >
                <div class="mb-1 flex items-center gap-2">
                    <div
                        class="flex size-7 items-center justify-center rounded-lg bg-violet-500/10"
                    >
                        <PieChart class="size-3.5 text-violet-400" />
                    </div>
                    <h3 class="font-outfit text-sm font-bold text-foreground">
                        Pengeluaran per Kategori
                    </h3>
                </div>
                <p class="font-inter mb-5 text-[11px] text-muted-foreground">
                    Struktur pengeluaran berdasarkan kategori dalam 30 hari
                    terakhir.
                </p>
                <div class="flex h-72 items-center justify-center">
                    <div
                        v-if="categoryChartData.labels.length === 0"
                        class="flex flex-col items-center gap-3 text-center"
                    >
                        <div
                            class="flex size-12 items-center justify-center rounded-xl bg-muted/30"
                        >
                            <PieChart class="size-5 text-muted-foreground/50" />
                        </div>
                        <p class="font-inter text-xs text-muted-foreground">
                            Tidak ada data pengeluaran<br />dalam 30 hari
                            terakhir.
                        </p>
                    </div>
                    <Doughnut
                        v-else
                        :data="categoryChartData"
                        :options="doughnutChartOptions"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

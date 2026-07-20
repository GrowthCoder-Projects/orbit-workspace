<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    PiggyBank,
    TrendingUp,
    TrendingDown,
    Activity,
    ArrowUpRight,
    ArrowDownRight,
    Wallet,
    Plus,
    History,
    Settings,
    ArrowRightLeft,
    Tag,
} from '@lucide/vue';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
} from 'chart.js';
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
} from '@/components/ui/card';
import { index as accountsIndex } from '@/routes/accounts';
import { index as categoriesIndex } from '@/routes/categories';
import { index as financeIndex } from '@/routes/finance';
import { index as transactionsIndex } from '@/routes/transactions';

// Register Chart.js components
ChartJS.register(
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
);

const props = defineProps<{
    accounts: any[];
    netWorthIDR: number;
    netWorthBreakdown: Record<string, number>;
    monthlyIncomeIDR: number;
    monthlyExpenseIDR: number;
    healthScore: number;
    chartData: {
        labels: string[];
        datasets: any[];
    };
    recentTransactions: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Finance Dashboard',
                href: financeIndex().url,
            },
        ],
    },
});

// Format currency
const formatIDR = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

const formatCurrency = (value: number, currency: string = 'IDR') => {
    const code = currency && typeof currency === 'string' && currency.trim() ? currency.trim() : 'IDR';
    try {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: code,
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        }).format(value || 0);
    } catch {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(value || 0);
    }
};

// Cash flow net savings
const netSavingsIDR = computed(() => {
    return props.monthlyIncomeIDR - props.monthlyExpenseIDR;
});

// Health score details
const healthText = computed(() => {
    if (props.healthScore >= 90) {
return 'Sangat Sehat';
}

    if (props.healthScore >= 80) {
return 'Sehat';
}

    if (props.healthScore >= 70) {
return 'Cukup Sehat';
}

    return 'Perlu Perhatian';
});

const healthColor = computed(() => {
    if (props.healthScore >= 80) {
return 'text-emerald-500';
}

    if (props.healthScore >= 70) {
return 'text-amber-500';
}

    return 'text-rose-500';
});

// Check if dark mode is active
const isDark = () => document.documentElement.classList.contains('dark');

// Chart config options (dynamic based on theme)
const getChartOptions = () => {
    const dark = isDark();
    const gridColor = dark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.08)';
    const tickColor = dark ? '#94a3b8' : '#64748b';
    const tooltipBg = dark ? '#1e293b' : '#ffffff';
    const tooltipTitle = dark ? '#f8fafc' : '#0f172a';
    const tooltipBody = dark ? '#cbd5e1' : '#475569';
    const tooltipBorder = dark ? '#334155' : '#e2e8f0';

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top' as const,
                labels: {
                    color: tickColor,
                    font: { family: 'Inter' },
                },
            },
            tooltip: {
                backgroundColor: tooltipBg,
                titleColor: tooltipTitle,
                bodyColor: tooltipBody,
                borderColor: tooltipBorder,
                borderWidth: 1,
                padding: 12,
                cornerRadius: 8,
            },
        },
        scales: {
            x: {
                grid: { color: gridColor },
                ticks: {
                    color: tickColor,
                    font: { family: 'Inter' },
                },
            },
            y: {
                grid: { color: gridColor },
                ticks: {
                    color: tickColor,
                    font: { family: 'Inter' },
                    callback: function (value: any) {
                        if (value >= 1e6) {
return value / 1e6 + 'M';
}

                        return value;
                    },
                },
            },
        },
    };
};
const chartOptions = getChartOptions();
</script>

<template>
    <Head title="Keuangan" />

    <div class="space-y-6 px-6 py-6">
        <!-- Header -->
        <div
            class="flex flex-col justify-between gap-4 border-b pb-5 md:flex-row md:items-center"
        >
            <div class="space-y-1">
                <h2
                    class="font-outfit flex items-center gap-2 text-2xl font-bold tracking-tight"
                >
                    <PiggyBank class="size-6 text-emerald-500" />
                    Manajemen Keuangan
                </h2>
                <p class="text-sm text-muted-foreground">
                    Pantau pemasukan, pengeluaran, transfer rekening, dan
                    kesehatan finansial Anda.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <Button as-child variant="outline" size="sm">
                    <Link
                        :href="accountsIndex().url"
                        class="flex items-center gap-1.5"
                    >
                        <Wallet class="size-4" />
                        Rekening
                    </Link>
                </Button>
                <Button as-child variant="outline" size="sm">
                    <Link
                        :href="categoriesIndex().url"
                        class="flex items-center gap-1.5"
                    >
                        <Tag class="size-4" />
                        Kategori
                    </Link>
                </Button>
                <Button as-child size="sm">
                    <Link
                        :href="transactionsIndex().url"
                        class="flex items-center gap-1.5"
                    >
                        <ArrowRightLeft class="size-4" />
                        Transaksi Ledger
                    </Link>
                </Button>
            </div>
        </div>

        <!-- Net Worth & Cash Flow Summary Grid -->
        <div class="grid gap-4 md:grid-cols-4">
            <!-- Net Worth -->
            <Card class="relative overflow-hidden shadow-sm">
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle class="text-sm font-medium text-muted-foreground"
                        >Total Kekayaan Bersih (Net Worth)</CardTitle
                    >
                    <PiggyBank class="size-4 text-emerald-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold tracking-tight">
                        {{ formatIDR(netWorthIDR) }}
                    </div>
                    <div class="mt-2 space-y-0.5 text-xs text-muted-foreground">
                        <p v-for="(val, curr) in netWorthBreakdown" :key="curr">
                            {{ curr }}:
                            <span class="font-semibold text-foreground">{{
                                formatCurrency(val, curr)
                            }}</span>
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Monthly Income -->
            <Card class="shadow-sm">
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle class="text-sm font-medium text-muted-foreground"
                        >Pemasukan Bulan Ini</CardTitle
                    >
                    <TrendingUp class="size-4 text-emerald-500" />
                </CardHeader>
                <CardContent>
                    <div
                        class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400"
                    >
                        {{ formatIDR(monthlyIncomeIDR) }}
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Akumulasi kas masuk dari semua rekening
                    </p>
                </CardContent>
            </Card>

            <!-- Monthly Expense -->
            <Card class="shadow-sm">
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle class="text-sm font-medium text-muted-foreground"
                        >Pengeluaran Bulan Ini</CardTitle
                    >
                    <TrendingDown class="size-4 text-rose-500" />
                </CardHeader>
                <CardContent>
                    <div
                        class="text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400"
                    >
                        {{ formatIDR(monthlyExpenseIDR) }}
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Akumulasi kas keluar dari semua rekening
                    </p>
                </CardContent>
            </Card>

            <!-- Health Score -->
            <Card class="shadow-sm">
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle class="text-sm font-medium text-muted-foreground"
                        >Skor Kesehatan Keuangan</CardTitle
                    >
                    <Activity class="size-4" :class="healthColor" />
                </CardHeader>
                <CardContent class="flex items-center justify-between">
                    <div>
                        <div
                            class="font-outfit text-2xl font-bold tracking-tight"
                            :class="healthColor"
                        >
                            {{ healthScore
                            }}<span class="text-xs text-muted-foreground"
                                >/100</span
                            >
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ healthText }}
                        </p>
                    </div>
                    <!-- Circular Progress Gauge -->
                    <div class="relative size-12">
                        <svg class="size-full -rotate-90" viewBox="0 0 36 36">
                            <path
                                class="text-muted/40 dark:text-border"
                                stroke-width="3"
                                stroke="currentColor"
                                fill="none"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                            />
                            <path
                                :class="
                                    healthScore >= 80
                                        ? 'text-emerald-500'
                                        : healthScore >= 70
                                          ? 'text-amber-500'
                                          : 'text-rose-500'
                                "
                                stroke-width="3"
                                :stroke-dasharray="`${healthScore}, 100`"
                                stroke-linecap="round"
                                stroke="currentColor"
                                fill="none"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                            />
                        </svg>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Cash Flow Chart & Accounts List -->
        <div class="grid gap-6 md:grid-cols-3">
            <!-- Chart.js (2/3 width) -->
            <Card class="shadow-sm md:col-span-2">
                <CardHeader>
                    <CardTitle class="text-base font-semibold"
                        >Analisis Arus Kas (6 Bulan Terakhir)</CardTitle
                    >
                    <CardDescription
                        >Pemasukan vs pengeluaran terkonversi ke
                        rupiah</CardDescription
                    >
                </CardHeader>
                <CardContent class="h-[300px]">
                    <Bar :data="chartData" :options="chartOptions" />
                </CardContent>
            </Card>

            <!-- Accounts Breakdown (1/3 width) -->
            <Card class="shadow-sm">
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle class="text-base font-semibold"
                            >Rekening Saya</CardTitle
                        >
                        <CardDescription>Daftar saldo aktif</CardDescription>
                    </div>
                    <Button as-child variant="ghost" size="icon" class="size-8">
                        <Link :href="accountsIndex().url">
                            <Plus class="size-4 text-muted-foreground" />
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent class="max-h-[300px] space-y-4 overflow-y-auto">
                    <div
                        v-if="accounts.length === 0"
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        Belum ada rekening aktif.
                    </div>
                    <div
                        v-for="acc in accounts"
                        :key="acc.id"
                        class="flex items-center justify-between rounded-lg border bg-muted/30 p-3"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="size-3.5 rounded-full"
                                :style="{
                                    backgroundColor: acc.color || '#3b82f6',
                                }"
                            ></div>
                            <div>
                                <h4 class="text-sm font-semibold">
                                    {{ acc.name }}
                                </h4>
                                <span
                                    class="font-mono text-xs text-muted-foreground uppercase"
                                    >{{ acc.type }}</span
                                >
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-mono text-sm font-bold">
                                {{ formatCurrency(acc.balance, acc.currency) }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Recent Transactions -->
        <Card class="shadow-sm">
            <CardHeader class="flex flex-row items-center justify-between">
                <div>
                    <CardTitle class="text-base font-semibold"
                        >10 Transaksi Terakhir</CardTitle
                    >
                    <CardDescription
                        >Riwayat pengeluaran, pemasukan, dan transfer
                        teranyar</CardDescription
                    >
                </div>
                <Button as-child variant="ghost" size="sm">
                    <Link
                        :href="transactionsIndex().url"
                        class="flex items-center gap-1"
                    >
                        Lihat Semua
                        <History class="size-4" />
                    </Link>
                </Button>
            </CardHeader>
            <CardContent>
                <div
                    v-if="recentTransactions.length === 0"
                    class="py-10 text-center text-sm text-muted-foreground"
                >
                    Belum ada transaksi tercatat.
                </div>
                <div v-else class="divide-y">
                    <div
                        v-for="tx in recentTransactions"
                        :key="tx.id"
                        class="flex flex-col justify-between gap-2 py-3 sm:flex-row sm:items-center"
                    >
                        <!-- Left Info -->
                        <div class="flex items-center gap-3">
                            <div
                                class="rounded-full p-2"
                                :class="{
                                    'bg-emerald-500/10 text-emerald-500':
                                        tx.type === 'income',
                                    'bg-rose-500/10 text-rose-500':
                                        tx.type === 'expense',
                                    'bg-blue-500/10 text-blue-500':
                                        tx.type === 'transfer',
                                }"
                            >
                                <ArrowUpRight
                                    v-if="tx.type === 'income'"
                                    class="size-4"
                                />
                                <ArrowDownRight
                                    v-else-if="tx.type === 'expense'"
                                    class="size-4"
                                />
                                <ArrowRightLeft v-else class="size-4" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold">
                                    {{
                                        tx.description ||
                                        (tx.type === 'transfer'
                                            ? 'Transfer Dana'
                                            : 'Transaksi Tanpa Deskripsi')
                                    }}
                                </p>
                                <div
                                    class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
                                >
                                    <span>{{
                                        new Date(
                                            tx.transaction_date,
                                        ).toLocaleDateString('id-ID', {
                                            day: '2-digit',
                                            month: 'short',
                                            year: 'numeric',
                                        })
                                    }}</span>
                                    <span>•</span>
                                    <span
                                        class="font-medium text-foreground/70"
                                        >{{ tx.account?.name }}</span
                                    >
                                    <span
                                        v-if="tx.destination_account"
                                        class="flex items-center gap-1"
                                    >
                                        ➔
                                        <span
                                            class="font-medium text-foreground/70"
                                            >{{
                                                tx.destination_account?.name
                                            }}</span
                                        >
                                    </span>
                                    <span
                                        v-if="tx.category"
                                        class="flex items-center gap-1"
                                    >
                                        •
                                        <span
                                            class="py-0.2 rounded px-1.5 text-[10px]"
                                            :style="{
                                                backgroundColor:
                                                    tx.category.color + '15',
                                                color: tx.category.color,
                                            }"
                                        >
                                            {{ tx.category.name }}
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Right Values -->
                        <div class="text-left font-mono sm:text-right">
                            <span
                                class="text-sm font-bold"
                                :class="{
                                    'text-emerald-500': tx.type === 'income',
                                    'text-rose-500': tx.type === 'expense',
                                    'text-blue-400': tx.type === 'transfer',
                                }"
                            >
                                {{
                                    tx.type === 'income'
                                        ? '+'
                                        : tx.type === 'expense'
                                          ? '-'
                                          : ''
                                }}
                                {{
                                    formatCurrency(
                                        tx.amount,
                                        tx.account?.currency || 'IDR',
                                    )
                                }}
                            </span>
                            <p
                                v-if="
                                    tx.type === 'transfer' &&
                                    tx.account?.currency !==
                                        tx.destination_account?.currency
                                "
                                class="text-[10px] text-muted-foreground"
                            >
                                (Kurs: {{ tx.exchange_rate }} ➔
                                {{
                                    formatCurrency(
                                        tx.converted_amount,
                                        tx.destination_account?.currency,
                                    )
                                }})
                            </p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

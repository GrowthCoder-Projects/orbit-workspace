<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    Plus,
    Pencil,
    Trash2,
    Calendar,
    CreditCard,
    CheckCircle2,
    XCircle,
    RefreshCw,
    History,
    Wallet,
    TrendingDown,
    AlertCircle,
    ArrowDownLeft,
} from '@lucide/vue';
import { ref } from 'vue';
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
    index as billsIndex,
    store as storeBill,
    update as updateBill,
    destroy as destroyBill,
    pay as payBill,
    history as billHistory,
} from '@/routes/bills';

const { confirm } = useConfirm();

const props = defineProps<{
    bills: any[];
    accounts: any[];
    categories: any[];
    summary: {
        total_monthly: number;
        total_annual: number;
        unpaid_count: number;
        total_active: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Finance Dashboard',
                href: '/app/finance',
            },
            {
                title: 'Tagihan & Langganan',
                href: billsIndex().url,
            },
        ],
    },
});

const isModalOpen = ref(false);
const editingBill = ref<any>(null);

const form = useForm({
    name: '',
    type: 'subscription',
    amount: 0.0,
    currency: 'IDR',
    due_day: 1,
    recurrence_period: 'monthly',
    category_id: '',
    account_id: '',
    is_active: true,
});

// Pay Modal
const isPayModalOpen = ref(false);
const payingBill = ref<any>(null);
const payForm = useForm({
    account_id: '',
});

// History Modal
const isHistoryModalOpen = ref(false);
const historyBill = ref<any>(null);
const historyLoading = ref(false);
const historyItems = ref<any[]>([]);

// Formats
const formatCurrency = (amount: number, currency: string = 'IDR') => {
    const code = currency && typeof currency === 'string' && currency.trim() ? currency.trim() : 'IDR';
    try {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: code,
            minimumFractionDigits: 0,
            maximumFractionDigits: code === 'USD' ? 2 : 0,
        }).format(amount || 0);
    } catch {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount || 0);
    }
};

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const isPaidThisMonth = (bill: any) => {
    if (!bill.last_paid_at) {
return false;
}

    const lastPaid = new Date(bill.last_paid_at);
    const today = new Date();

    return (
        lastPaid.getMonth() === today.getMonth() &&
        lastPaid.getFullYear() === today.getFullYear()
    );
};

const openBillModal = (bill?: any) => {
    if (bill) {
        editingBill.value = bill;
        form.name = bill.name;
        form.type = bill.type;
        form.amount = parseFloat(bill.amount);
        form.currency = bill.currency;
        form.due_day = bill.due_day;
        form.recurrence_period = bill.recurrence_period;
        form.category_id = bill.category_id ? bill.category_id.toString() : '';
        form.account_id = bill.account_id ? bill.account_id.toString() : '';
        form.is_active = bill.is_active;
    } else {
        editingBill.value = null;
        form.reset();
        form.type = 'subscription';
        form.currency = 'IDR';
        form.recurrence_period = 'monthly';
        form.is_active = true;

        if (props.categories.length > 0) {
            form.category_id = props.categories[0].id.toString();
        }

        if (props.accounts.length > 0) {
            form.account_id = props.accounts[0].id.toString();
        }
    }

    isModalOpen.value = true;
};

const handleSave = () => {
    if (editingBill.value) {
        form.put(updateBill(editingBill.value.id).url, {
            onSuccess: () => {
                isModalOpen.value = false;
                editingBill.value = null;
            },
        });
    } else {
        form.post(storeBill().url, {
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    }
};

const handleDelete = async (id: number) => {
    const isConfirmed = await confirm({
        title: 'Hapus Tagihan',
        message: 'Apakah Anda yakin ingin menghapus tagihan/langganan ini?',
        confirmText: 'Hapus',
        cancelText: 'Batal',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(destroyBill(id).url);
    }
};

const openPayModal = (bill: any) => {
    payingBill.value = bill;
    payForm.account_id = bill.account_id ? bill.account_id.toString() : '';

    if (!payForm.account_id && props.accounts.length > 0) {
        payForm.account_id = props.accounts[0].id.toString();
    }

    isPayModalOpen.value = true;
};

const handlePay = () => {
    if (!payingBill.value) {
return;
}

    payForm.post(payBill(payingBill.value.id).url, {
        onSuccess: () => {
            isPayModalOpen.value = false;
            payingBill.value = null;
        },
    });
};

const openHistoryModal = async (bill: any) => {
    historyBill.value = bill;
    historyItems.value = [];
    historyLoading.value = true;
    isHistoryModalOpen.value = true;

    try {
        const res = await fetch(billHistory({ bill: bill.id }).url, {
            headers: { Accept: 'application/json' },
        });
        historyItems.value = await res.json();
    } catch {
        historyItems.value = [];
    } finally {
        historyLoading.value = false;
    }
};
</script>

<template>
    <Head title="Tagihan & Langganan SaaS" />

    <div class="space-y-8 px-6 py-6">
        <!-- Page Header -->
        <div
            class="flex flex-col justify-between gap-4 border-b border-border/60 pb-6 md:flex-row md:items-center"
        >
            <div class="space-y-1">
                <h2
                    class="font-outfit flex items-center gap-2.5 text-2xl font-bold tracking-tight"
                >
                    <span
                        class="flex size-9 items-center justify-center rounded-xl bg-indigo-500/15"
                    >
                        <RefreshCw class="size-5 text-indigo-400" />
                    </span>
                    Tagihan & Langganan Rutin
                </h2>
                <p class="font-inter text-sm text-muted-foreground">
                    Pantau tagihan bulanan (Listrik, Internet) atau biaya
                    langganan software SaaS (Netflix, ChatGPT, Github) Anda.
                </p>
            </div>

            <Button
                size="sm"
                @click="openBillModal()"
                class="bg-indigo-600 font-medium text-white shadow-xs hover:bg-indigo-700"
            >
                <Plus class="mr-1.5 size-4" />
                Tambah Tagihan
            </Button>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div
                class="rounded-xl border border-border/60 bg-card/40 p-4 shadow-2xs backdrop-blur-xs"
            >
                <div class="mb-3 flex items-center gap-2">
                    <div
                        class="flex size-7 items-center justify-center rounded-lg bg-indigo-500/10"
                    >
                        <CreditCard class="size-4 text-indigo-400" />
                    </div>
                    <span class="font-inter text-xs text-muted-foreground"
                        >Tagihan Aktif</span
                    >
                </div>
                <p class="font-outfit text-2xl font-bold text-foreground">
                    {{ summary.total_active }}
                </p>
                <p class="font-inter mt-0.5 text-[11px] text-muted-foreground">
                    layanan terdaftar
                </p>
            </div>

            <div
                class="rounded-xl border border-border/60 bg-card/40 p-4 shadow-2xs backdrop-blur-xs"
            >
                <div class="mb-3 flex items-center gap-2">
                    <div
                        class="flex size-7 items-center justify-center rounded-lg bg-violet-500/10"
                    >
                        <TrendingDown class="size-4 text-violet-400" />
                    </div>
                    <span class="font-inter text-xs text-muted-foreground"
                        >Total / Bulan</span
                    >
                </div>
                <p class="font-outfit text-lg font-bold text-foreground">
                    {{ formatCurrency(summary.total_monthly, 'IDR') }}
                </p>
                <p class="font-inter mt-0.5 text-[11px] text-muted-foreground">
                    tagihan bulanan
                </p>
            </div>

            <div
                class="rounded-xl border border-border/60 bg-card/40 p-4 shadow-2xs backdrop-blur-xs"
            >
                <div class="mb-3 flex items-center gap-2">
                    <div
                        class="flex size-7 items-center justify-center rounded-lg bg-amber-500/10"
                    >
                        <Wallet class="size-4 text-amber-400" />
                    </div>
                    <span class="font-inter text-xs text-muted-foreground"
                        >Total / Tahun</span
                    >
                </div>
                <p class="font-outfit text-lg font-bold text-foreground">
                    {{ formatCurrency(summary.total_annual, 'IDR') }}
                </p>
                <p class="font-inter mt-0.5 text-[11px] text-muted-foreground">
                    langganan tahunan
                </p>
            </div>

            <div
                class="rounded-xl border border-border/60 p-4 shadow-2xs backdrop-blur-xs"
                :class="
                    summary.unpaid_count > 0
                        ? 'border-rose-500/30 bg-rose-500/5'
                        : 'border-emerald-500/20 bg-emerald-500/5'
                "
            >
                <div class="mb-3 flex items-center gap-2">
                    <div
                        class="flex size-7 items-center justify-center rounded-lg"
                        :class="
                            summary.unpaid_count > 0
                                ? 'bg-rose-500/10'
                                : 'bg-emerald-500/10'
                        "
                    >
                        <AlertCircle
                            class="size-4"
                            :class="
                                summary.unpaid_count > 0
                                    ? 'text-rose-400'
                                    : 'text-emerald-400'
                            "
                        />
                    </div>
                    <span class="font-inter text-xs text-muted-foreground"
                        >Belum Dibayar</span
                    >
                </div>
                <p
                    class="font-outfit text-2xl font-bold"
                    :class="
                        summary.unpaid_count > 0
                            ? 'text-rose-400'
                            : 'text-emerald-400'
                    "
                >
                    {{ summary.unpaid_count }}
                </p>
                <p class="font-inter mt-0.5 text-[11px] text-muted-foreground">
                    {{
                        summary.unpaid_count > 0
                            ? 'tagihan menunggu'
                            : 'semua lunas bulan ini'
                    }}
                </p>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-if="bills.length === 0"
            class="rounded-2xl border border-dashed border-border/70 bg-muted/10 py-20 text-center"
        >
            <div
                class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-indigo-500/10"
            >
                <RefreshCw class="size-7 text-indigo-400" />
            </div>
            <p class="font-outfit text-base font-semibold text-foreground/80">
                Belum ada tagihan terdaftar
            </p>
            <p class="font-inter mt-1.5 text-sm text-muted-foreground">
                Klik tombol "+ Tambah Tagihan" untuk mendaftarkan tagihan rutin
                pertama Anda.
            </p>
            <Button
                class="mt-5 bg-indigo-600 font-medium text-white hover:bg-indigo-700"
                size="sm"
                @click="openBillModal()"
            >
                <Plus class="mr-1.5 size-4" />
                Tambah Tagihan Pertama
            </Button>
        </div>

        <!-- Listing Cards -->
        <div v-else class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            <Card
                v-for="b in bills"
                :key="b.id"
                class="group relative overflow-hidden shadow-xs transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md"
                :class="[
                    !b.is_active ? 'opacity-50' : '',
                    isPaidThisMonth(b)
                        ? 'hover:border-emerald-500/30'
                        : 'hover:border-rose-500/20',
                ]"
            >
                <!-- Top accent stripe -->
                <div
                    class="absolute inset-x-0 top-0 h-0.5 rounded-t-xl"
                    :class="
                        isPaidThisMonth(b)
                            ? 'bg-gradient-to-r from-emerald-500/60 to-emerald-400/20'
                            : 'bg-gradient-to-r from-rose-500/60 to-amber-500/20'
                    "
                />

                <CardHeader class="pt-5 pb-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl transition-transform duration-200 group-hover:scale-105"
                                :class="
                                    b.type === 'subscription'
                                        ? 'bg-indigo-500/10 text-indigo-400'
                                        : 'bg-violet-500/10 text-violet-400'
                                "
                            >
                                <RefreshCw
                                    v-if="b.type === 'subscription'"
                                    class="size-5"
                                />
                                <CreditCard v-else class="size-5" />
                            </div>
                            <div>
                                <CardTitle
                                    class="font-outfit text-base font-bold text-foreground"
                                    >{{ b.name }}</CardTitle
                                >
                                <CardDescription
                                    class="font-inter mt-0.5 text-xs text-muted-foreground"
                                >
                                    {{
                                        b.type === 'subscription'
                                            ? 'Langganan SaaS'
                                            : 'Tagihan Bulanan'
                                    }}
                                    •
                                    {{ b.category?.name ?? 'Tanpa Kategori' }}
                                </CardDescription>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div
                            class="flex items-center gap-0.5 opacity-0 transition duration-150 group-hover:opacity-100"
                        >
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-7 hover:bg-muted"
                                @click="openHistoryModal(b)"
                                title="Riwayat Pembayaran"
                            >
                                <History
                                    class="size-3.5 text-muted-foreground"
                                />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-7 hover:bg-muted"
                                @click="openBillModal(b)"
                            >
                                <Pencil
                                    class="size-3.5 text-muted-foreground"
                                />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-7 hover:bg-muted"
                                @click="handleDelete(b.id)"
                            >
                                <Trash2 class="size-3.5 text-rose-400" />
                            </Button>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="space-y-4">
                    <!-- Amount & Period -->
                    <div class="flex items-baseline justify-between">
                        <div
                            class="font-mono text-2xl font-bold text-foreground"
                        >
                            {{ formatCurrency(b.amount, b.currency) }}
                        </div>
                        <span
                            class="font-inter rounded-full px-2 py-0.5 text-[10px] font-semibold tracking-wider uppercase"
                            :class="
                                b.recurrence_period === 'monthly'
                                    ? 'bg-indigo-500/10 text-indigo-400'
                                    : 'bg-amber-500/10 text-amber-400'
                            "
                        >
                            {{
                                b.recurrence_period === 'monthly'
                                    ? 'Bulanan'
                                    : 'Tahunan'
                            }}
                        </span>
                    </div>

                    <!-- Due Date & Status -->
                    <div
                        class="flex items-center justify-between border-t border-border/50 pt-3 text-xs"
                    >
                        <span
                            class="font-inter flex items-center gap-1.5 text-muted-foreground"
                        >
                            <Calendar class="size-3.5" />
                            Jatuh Tempo: Hari ke-{{ b.due_day }}
                        </span>

                        <span
                            v-if="isPaidThisMonth(b)"
                            class="font-inter inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-400"
                        >
                            <CheckCircle2 class="size-3" />
                            Sudah Dibayar
                        </span>
                        <span
                            v-else
                            class="font-inter inline-flex items-center gap-1 rounded-full bg-amber-500/10 px-2 py-0.5 text-[10px] font-semibold text-amber-400"
                        >
                            <XCircle class="size-3" />
                            Belum Dibayar
                        </span>
                    </div>

                    <!-- Action Buttons Row -->
                    <div
                        class="grid gap-2"
                        :class="
                            b.is_active && !isPaidThisMonth(b)
                                ? 'grid-cols-2'
                                : 'grid-cols-1'
                        "
                    >
                        <!-- Pay Button -->
                        <Button
                            v-if="b.is_active && !isPaidThisMonth(b)"
                            class="flex h-8 w-full items-center justify-center gap-1.5 bg-indigo-600 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700"
                            @click="openPayModal(b)"
                        >
                            <CheckCircle2 class="size-3.5" />
                            Bayar Sekarang
                        </Button>

                        <!-- History Button -->
                        <Button
                            variant="outline"
                            class="flex h-8 w-full items-center justify-center gap-1.5 py-1.5 text-xs font-medium text-muted-foreground hover:bg-muted"
                            @click="openHistoryModal(b)"
                        >
                            <History class="size-3.5" />
                            Riwayat
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- ADD/EDIT BILL MODAL -->
        <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
            <DialogContent class="max-w-lg">
                <DialogHeader class="border-b border-border/50 pb-4">
                    <DialogTitle
                        class="font-outfit flex items-center gap-2 text-lg font-bold"
                    >
                        <span
                            class="flex size-8 items-center justify-center rounded-lg bg-indigo-500/10"
                        >
                            <RefreshCw class="size-4 text-indigo-400" />
                        </span>
                        {{ editingBill ? 'Ubah' : 'Daftarkan' }}
                        Tagihan / Langganan
                    </DialogTitle>
                    <DialogDescription
                        class="font-inter text-xs text-muted-foreground"
                    >
                        Konfigurasi tagihan berkala Anda agar dapat dilacak dan
                        dibayar dengan satu-klik di masa mendatang.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="handleSave" class="space-y-5 py-2">
                    <!-- Nama Layanan -->
                    <div class="space-y-1.5">
                        <Label
                            for="bill-name"
                            class="text-xs font-medium text-foreground/80"
                            >Nama Tagihan / Layanan</Label
                        >
                        <Input
                            id="bill-name"
                            v-model="form.name"
                            placeholder="Misal: Netflix, Listrik Pascabayar, Github Copilot"
                            required
                        />
                    </div>

                    <!-- Jenis & Periode -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label
                                class="text-xs font-medium text-foreground/80"
                                >Jenis Tagihan</Label
                            >
                            <Select v-model="form.type" required>
                                <SelectTrigger class="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="subscription"
                                        >Langganan Rutin (SaaS)</SelectItem
                                    >
                                    <SelectItem value="bill"
                                        >Tagihan Umum</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1.5">
                            <Label
                                class="text-xs font-medium text-foreground/80"
                                >Periode Penagihan</Label
                            >
                            <Select v-model="form.recurrence_period" required>
                                <SelectTrigger class="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="monthly"
                                        >Bulanan</SelectItem
                                    >
                                    <SelectItem value="annual"
                                        >Tahunan</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Nominal & Mata Uang -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2 space-y-1.5">
                            <Label
                                for="bill-amount"
                                class="text-xs font-medium text-foreground/80"
                                >Nominal Tagihan</Label
                            >
                            <Input
                                id="bill-amount"
                                type="number"
                                step="0.01"
                                v-model="form.amount"
                                class="font-mono"
                                required
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label
                                class="text-xs font-medium text-foreground/80"
                                >Mata Uang</Label
                            >
                            <Select v-model="form.currency">
                                <SelectTrigger class="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="IDR">IDR</SelectItem>
                                    <SelectItem value="USD">USD</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Jatuh Tempo & Status -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label
                                for="bill-due-day"
                                class="text-xs font-medium text-foreground/80"
                                >Tanggal Jatuh Tempo (Hari ke-)</Label
                            >
                            <Input
                                id="bill-due-day"
                                type="number"
                                min="1"
                                max="31"
                                v-model="form.due_day"
                                class="font-mono"
                                required
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label
                                class="text-xs font-medium text-foreground/80"
                                >Status Aktif</Label
                            >
                            <Select
                                v-model="form.is_active"
                                :value="form.is_active"
                            >
                                <SelectTrigger class="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="true">Aktif</SelectItem>
                                    <SelectItem :value="false"
                                        >Tidak Aktif</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Kategori & Rekening -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label
                                class="text-xs font-medium text-foreground/80"
                                >Kategori Anggaran</Label
                            >
                            <Select v-model="form.category_id">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Pilih Kategori" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="cat in categories"
                                        :key="cat.id"
                                        :value="cat.id.toString()"
                                    >
                                        {{ cat.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1.5">
                            <Label
                                class="text-xs font-medium text-foreground/80"
                                >Default Rekening Bayar</Label
                            >
                            <Select v-model="form.account_id">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Pilih Rekening" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="acc in accounts"
                                        :key="acc.id"
                                        :value="acc.id.toString()"
                                    >
                                        {{ acc.name }} ({{ acc.currency }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <DialogFooter class="gap-2 border-t border-border/50 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isModalOpen = false"
                            class="hover:bg-muted"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            class="bg-indigo-600 font-medium text-white shadow-xs hover:bg-indigo-700"
                            :disabled="form.processing"
                            >Simpan Tagihan</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- CONFIRM PAYMENT MODAL -->
        <Dialog :open="isPayModalOpen" @update:open="isPayModalOpen = $event">
            <DialogContent class="max-w-sm">
                <DialogHeader class="border-b border-border/50 pb-4">
                    <DialogTitle
                        class="font-outfit flex items-center gap-2 text-lg font-bold"
                    >
                        <span
                            class="flex size-8 items-center justify-center rounded-lg bg-emerald-500/10"
                        >
                            <CheckCircle2 class="size-4 text-emerald-400" />
                        </span>
                        Catat Pembayaran
                    </DialogTitle>
                    <DialogDescription
                        class="font-inter text-xs text-muted-foreground"
                    >
                        Pilih rekening asal pembayaran untuk memotong saldo dan
                        mencatatnya di ledger transaksi.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="handlePay" class="space-y-4 py-2">
                    <div
                        v-if="payingBill"
                        class="space-y-2 rounded-xl border border-border/60 bg-muted/20 p-4"
                    >
                        <div
                            class="font-outfit text-[10px] font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            Detail Tagihan
                        </div>
                        <div
                            class="font-outfit text-sm font-bold text-foreground"
                        >
                            {{ payingBill.name }}
                        </div>
                        <div
                            class="font-mono text-xl font-bold text-foreground"
                        >
                            {{
                                formatCurrency(
                                    payingBill.amount,
                                    payingBill.currency,
                                )
                            }}
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-medium text-foreground/80"
                            >Pilih Rekening Sumber</Label
                        >
                        <Select v-model="payForm.account_id" required>
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Pilih Rekening" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="acc in accounts"
                                    :key="acc.id"
                                    :value="acc.id.toString()"
                                >
                                    {{ acc.name }} — Saldo:
                                    {{
                                        formatCurrency(
                                            acc.balance,
                                            acc.currency,
                                        )
                                    }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <DialogFooter class="gap-2 border-t border-border/50 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isPayModalOpen = false"
                            class="hover:bg-muted"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            class="bg-emerald-600 font-medium text-white shadow-xs hover:bg-emerald-700"
                            :disabled="payForm.processing"
                            >Konfirmasi Bayar</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- HISTORY MODAL -->
        <Dialog
            :open="isHistoryModalOpen"
            @update:open="isHistoryModalOpen = $event"
        >
            <DialogContent class="max-w-md">
                <DialogHeader class="border-b border-border/50 pb-4">
                    <DialogTitle
                        class="font-outfit flex items-center gap-2 text-lg font-bold"
                    >
                        <span
                            class="flex size-8 items-center justify-center rounded-lg bg-violet-500/10"
                        >
                            <History class="size-4 text-violet-400" />
                        </span>
                        Riwayat Pembayaran
                    </DialogTitle>
                    <DialogDescription
                        class="font-inter text-xs text-muted-foreground"
                    >
                        <span v-if="historyBill"
                            >Semua pembayaran yang tercatat untuk
                            <strong class="text-foreground/80">{{
                                historyBill.name
                            }}</strong></span
                        >
                    </DialogDescription>
                </DialogHeader>

                <div class="py-2">
                    <!-- Loading -->
                    <div
                        v-if="historyLoading"
                        class="flex flex-col items-center gap-3 py-10"
                    >
                        <RefreshCw
                            class="size-6 animate-spin text-muted-foreground"
                        />
                        <p class="font-inter text-sm text-muted-foreground">
                            Memuat riwayat...
                        </p>
                    </div>

                    <!-- Empty -->
                    <div
                        v-else-if="historyItems.length === 0"
                        class="flex flex-col items-center gap-3 py-10 text-center"
                    >
                        <div
                            class="flex size-12 items-center justify-center rounded-xl bg-muted/30"
                        >
                            <History class="size-6 text-muted-foreground/60" />
                        </div>
                        <p
                            class="font-outfit text-sm font-semibold text-foreground/80"
                        >
                            Belum ada riwayat pembayaran
                        </p>
                        <p class="font-inter text-xs text-muted-foreground">
                            Pembayaran akan muncul di sini setelah Anda mencatat
                            pembayaran pertama.
                        </p>
                    </div>

                    <!-- History List -->
                    <div v-else class="max-h-80 space-y-2 overflow-y-auto pr-1">
                        <div
                            v-for="item in historyItems"
                            :key="item.id"
                            class="flex items-center gap-3 rounded-xl border border-border/50 bg-card/30 p-3 transition-colors hover:bg-muted/20"
                        >
                            <div
                                class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10"
                            >
                                <ArrowDownLeft
                                    class="size-4 text-emerald-400"
                                />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p
                                    class="font-inter truncate text-xs font-medium text-foreground"
                                >
                                    {{ item.account_name }}
                                </p>
                                <p
                                    class="font-inter text-[11px] text-muted-foreground"
                                >
                                    {{ formatDate(item.date) }}
                                </p>
                            </div>
                            <span
                                class="shrink-0 font-mono text-sm font-bold text-emerald-400"
                            >
                                {{ formatCurrency(item.amount, item.currency) }}
                            </span>
                        </div>
                    </div>
                </div>

                <DialogFooter class="border-t border-border/50 pt-4">
                    <Button
                        variant="outline"
                        @click="isHistoryModalOpen = false"
                        class="hover:bg-muted"
                        >Tutup</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>

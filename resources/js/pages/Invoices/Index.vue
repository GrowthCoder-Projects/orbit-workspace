<script setup lang="ts">
import { Head, Link, useForm, router, setLayoutProps } from '@inertiajs/vue3';
import {
    Plus,
    Pencil,
    Trash2,
    Search,
    Download,
    Eye,
    CheckCircle2,
    Clock,
    FileEdit,
    AlertCircle,
    X,
} from '@lucide/vue';
import { ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
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
    index as invoicesIndex,
    destroy as destroyInvoice,
    pay as payInvoice,
    show as showInvoice,
    edit as editInvoice,
    create as createInvoice,
} from '@/routes/invoices';

const props = defineProps<{
    invoices: any[];
    clients: any[];
    projects: any[];
    accounts: any[];
    filters: any;
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Invoices',
            href: '/app/invoices',
        },
    ],
});

// Pay Modal States
const isPayOpen = ref(false);
const selectedInvoice = ref<any>(null);
const payForm = useForm({
    finance_account_id: '',
});

// Search & Filter State
const searchInput = ref(props.filters.search || '');
const filterStatus = ref(props.filters.status || 'all');
const filterClient = ref(props.filters.client_id || 'all');

const triggerFilter = () => {
    router.get(
        invoicesIndex().url,
        {
            search: searchInput.value || null,
            status: filterStatus.value === 'all' ? null : filterStatus.value,
            client_id: filterClient.value === 'all' ? null : filterClient.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    searchInput.value = '';
    filterStatus.value = 'all';
    filterClient.value = 'all';
    triggerFilter();
};

// Auto-calculate stats reactively from props.invoices
const stats = computed(() => {
    let paid = 0;
    let unpaid = 0;
    let overdue = 0;
    let draft = 0;

    props.invoices.forEach((inv) => {
        const amt = parseFloat(inv.total);

        if (inv.status === 'paid') {
            paid += amt;
        } else if (inv.status === 'sent') {
            unpaid += amt;
        } else if (inv.status === 'overdue') {
            overdue += amt;
        } else if (inv.status === 'draft') {
            draft += amt;
        }
    });

    return { paid, unpaid, overdue, draft };
});

const formatCurrency = (amount: number, currency: string = 'IDR') => {
    const code = currency && typeof currency === 'string' && currency.trim() ? currency.trim() : 'IDR';
    try {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: code,
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
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

const getStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'paid':
            return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20';
        case 'sent':
            return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20';
        case 'overdue':
            return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20';
        default:
            return 'bg-zinc-500/10 text-zinc-600 dark:text-zinc-400 border-zinc-500/20';
    }
};

const openPayModal = (invoice: any) => {
    selectedInvoice.value = invoice;
    payForm.finance_account_id = props.accounts[0]?.id?.toString() || '';
    isPayOpen.value = true;
};

const submitPay = () => {
    if (!selectedInvoice.value) {
return;
}

    payForm.post(payInvoice(selectedInvoice.value.id).url, {
        onSuccess: () => {
            isPayOpen.value = false;
            toast.success('Invoice berhasil ditandai Paid dan dicatat di Ledger Keuangan.');
        },
        onError: () => {
            toast.error('Gagal mencatat pembayaran invoice.');
        },
    });
};

const { confirm } = useConfirm();

const handleDelete = async (id: number) => {
    const isConfirmed = await confirm({
        title: 'Hapus Invoice',
        message: 'Apakah Anda yakin ingin menghapus invoice ini secara permanen?',
        confirmText: 'Hapus',
        cancelText: 'Batal',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(destroyInvoice(id).url, {
            onSuccess: () => {
                toast.success('Invoice berhasil dihapus.');
            },
            onError: () => {
                toast.error('Gagal menghapus invoice.');
            },
        });
    }
};
</script>

<template>
    <Head title="Invoices" />

    <div class="flex flex-1 flex-col gap-6 p-6">
        <!-- Header -->
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Invoices</h1>
                <p class="text-sm text-muted-foreground">
                    Manajemen penagihan klien, kustomisasi template, dan pencatatan pendapatan otomatis.
                </p>
            </div>
            <div>
                <Link :href="createInvoice().url">
                    <Button class="gap-2">
                        <Plus class="size-4" />
                        Buat Invoice Baru
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Summary Stats Grid -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card class="border-emerald-500/20 bg-emerald-500/[0.02]">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Total Terbayar</CardTitle>
                    <CheckCircle2 class="size-4 text-emerald-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400">
                        {{ formatCurrency(stats.paid) }}
                    </div>
                </CardContent>
            </Card>

            <Card class="border-amber-500/20 bg-amber-500/[0.02]">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-xs font-medium text-amber-600 dark:text-amber-400">Tertunggak (Sent)</CardTitle>
                    <Clock class="size-4 text-amber-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-xl font-bold text-amber-600 dark:text-amber-400">
                        {{ formatCurrency(stats.unpaid) }}
                    </div>
                </CardContent>
            </Card>

            <Card class="border-rose-500/20 bg-rose-500/[0.02]">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-xs font-medium text-rose-600 dark:text-rose-400">Jatuh Tempo</CardTitle>
                    <AlertCircle class="size-4 text-rose-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-xl font-bold text-rose-600 dark:text-rose-400">
                        {{ formatCurrency(stats.overdue) }}
                    </div>
                </CardContent>
            </Card>

            <Card class="border-zinc-500/20 bg-zinc-500/[0.02]">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-xs font-medium text-zinc-500">Draft</CardTitle>
                    <FileEdit class="size-4 text-zinc-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-xl font-bold text-zinc-700 dark:text-zinc-300">
                        {{ formatCurrency(stats.draft) }}
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Filter Controls -->
        <Card class="border-sidebar-border/60">
            <CardContent class="flex flex-col gap-4 p-4 md:flex-row md:items-end">
                <div class="flex-1 space-y-1.5">
                    <Label for="search">Cari Nomor Invoice</Label>
                    <div class="relative">
                        <Search class="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                        <Input
                            id="search"
                            v-model="searchInput"
                            placeholder="INV-2026-..."
                            class="pl-9"
                            @keyup.enter="triggerFilter"
                        />
                    </div>
                </div>

                <div class="w-full space-y-1.5 md:w-48">
                    <Label>Status</Label>
                    <Select v-model="filterStatus">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Semua Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Status</SelectItem>
                            <SelectItem value="draft">Draft</SelectItem>
                            <SelectItem value="sent">Sent (Tertunggak)</SelectItem>
                            <SelectItem value="overdue">Overdue (Jatuh Tempo)</SelectItem>
                            <SelectItem value="paid">Paid (Lunas)</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="w-full space-y-1.5 md:w-56">
                    <Label>Klien</Label>
                    <Select v-model="filterClient">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Semua Klien" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Klien</SelectItem>
                            <SelectItem
                                v-for="client in clients"
                                :key="client.id"
                                :value="client.id.toString()"
                            >
                                {{ client.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="flex gap-2 w-full md:w-auto">
                    <Button variant="outline" class="flex-1 md:flex-none" @click="clearFilters">
                        <X class="mr-1.5 size-4" />
                        Reset
                    </Button>
                    <Button class="flex-1 md:flex-none" @click="triggerFilter">
                        Filter
                    </Button>
                </div>
            </CardContent>
        </Card>

        <!-- Invoice Table -->
        <Card class="border-sidebar-border/60">
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead>
                            <tr class="border-b border-sidebar-border/70 bg-sidebar-accent/30 text-xs font-semibold uppercase text-muted-foreground">
                                <th class="p-4">Nomor</th>
                                <th class="p-4">Klien / Proyek</th>
                                <th class="p-4">Tanggal Terbit</th>
                                <th class="p-4">Tenggat Waktu</th>
                                <th class="p-4">Total</th>
                                <th class="p-4 text-center">Status</th>
                                <th class="p-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sidebar-border/50">
                            <tr v-if="invoices.length === 0">
                                <td colspan="7" class="p-8 text-center text-muted-foreground">
                                    Tidak ada invoice yang ditemukan.
                                </td>
                            </tr>
                            <tr
                                v-for="invoice in invoices"
                                :key="invoice.id"
                                class="hover:bg-sidebar-accent/15 transition-colors"
                            >
                                <td class="p-4 font-mono font-medium">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="size-2 rounded-full"
                                            :style="{ backgroundColor: invoice.color_accent || '#3b82f6' }"
                                            title="Warna Aksen"
                                        ></div>
                                        <Link
                                            :href="showInvoice(invoice.id).url"
                                            class="hover:underline text-primary"
                                        >
                                            {{ invoice.invoice_number }}
                                        </Link>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div class="font-medium">{{ invoice.client?.name }}</div>
                                    <div v-if="invoice.project" class="text-xs text-muted-foreground">
                                        Proyek: {{ invoice.project.name }}
                                    </div>
                                </td>
                                <td class="p-4 text-muted-foreground">
                                    {{ invoice.issue_date }}
                                </td>
                                <td class="p-4 text-muted-foreground">
                                    {{ invoice.due_date }}
                                </td>
                                <td class="p-4 font-semibold">
                                    {{ formatCurrency(parseFloat(invoice.total), invoice.currency) }}
                                </td>
                                <td class="p-4 text-center">
                                    <span
                                        class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold capitalize"
                                        :class="getStatusBadgeClass(invoice.status)"
                                    >
                                        {{ invoice.status }}
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Actions -->
                                        <Link :href="showInvoice(invoice.id).url" title="Detail">
                                            <Button variant="ghost" size="icon" class="size-8">
                                                <Eye class="size-4" />
                                            </Button>
                                        </Link>

                                        <a
                                            :href="`/app/invoices/${invoice.id}/pdf`"
                                            target="_blank"
                                            title="Unduh/Preview PDF"
                                        >
                                            <Button variant="ghost" size="icon" class="size-8 text-primary">
                                                <Download class="size-4" />
                                            </Button>
                                        </a>

                                        <Link :href="editInvoice(invoice.id).url" title="Edit">
                                            <Button variant="ghost" size="icon" class="size-8 text-amber-500">
                                                <Pencil class="size-4" />
                                            </Button>
                                        </Link>

                                        <Button
                                            v-if="invoice.status !== 'paid'"
                                            variant="ghost"
                                            size="icon"
                                            class="size-8 text-emerald-600 hover:text-emerald-500"
                                            title="Tandai Paid"
                                            @click="openPayModal(invoice)"
                                        >
                                            <CheckCircle2 class="size-4" />
                                        </Button>

                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="size-8 text-rose-500 hover:text-rose-600"
                                            title="Hapus"
                                            @click="handleDelete(invoice.id)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- Pay Invoice Modal Dialog -->
        <Dialog v-model:open="isPayOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Tandai Pembayaran Invoice</DialogTitle>
                    <DialogDescription>
                        Invoice <strong class="font-mono">{{ selectedInvoice?.invoice_number }}</strong> sebesar
                        <strong>{{ selectedInvoice ? formatCurrency(parseFloat(selectedInvoice.total), selectedInvoice.currency) : '' }}</strong>
                        akan ditandai lunas (Paid). Pilih akun penerima di modul Keuangan.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="pay-account">Rekening Keuangan Tujuan</Label>
                        <Select v-model="payForm.finance_account_id">
                            <SelectTrigger id="pay-account" class="w-full">
                                <SelectValue placeholder="Pilih Rekening" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="account in accounts"
                                    :key="account.id"
                                    :value="account.id.toString()"
                                >
                                    {{ account.name }} ({{ formatCurrency(account.balance, account.currency) }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p class="text-[11px] text-muted-foreground">
                            Transaksi pendapatan baru bertipe "income" dengan kategori otomatis "Invoice Payment" akan dicatatkan pada akun ini.
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="isPayOpen = false">Batal</Button>
                    <Button class="bg-emerald-600 hover:bg-emerald-500 text-white" @click="submitPay" :disabled="payForm.processing">
                        Konfirmasi Lunas
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>

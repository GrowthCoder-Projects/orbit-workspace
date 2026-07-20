<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    Wallet,
    Plus,
    Pencil,
    Trash2,
    ArrowRightLeft,
    Coins,
    Banknote,
    CreditCard,
    TrendingUp,
    Info,
    CalendarIcon,
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
    index as accountsIndex,
    store as storeAccount,
    update as updateAccount,
    destroy as destroyAccount,
} from '@/routes/accounts';
import { store as storeTransaction } from '@/routes/transactions';

const { confirm } = useConfirm();

const props = defineProps<{
    accounts: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Finance Dashboard',
                href: '/app/finance',
            },
            {
                title: 'Rekening',
                href: accountsIndex().url,
            },
        ],
    },
});

// Modal Dialog States
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isTransferOpen = ref(false);
const selectedAccount = ref<any>(null);

// Forms
const createForm = useForm({
    name: '',
    type: 'bank',
    account_number: '',
    account_holder: '',
    logo: null as File | null,
    balance: 0,
    currency: 'IDR',
    color: '#3b82f6',
    notes: '',
});

const editForm = useForm({
    name: '',
    type: 'bank',
    account_number: '',
    account_holder: '',
    logo: null as File | null,
    balance: 0,
    currency: 'IDR',
    color: '#3b82f6',
    notes: '',
    _method: 'PATCH',
});

const transferForm = useForm({
    account_id: '',
    destination_account_id: '',
    amount: 0,
    converted_amount: 0,
    exchange_rate: 1,
    transaction_date: new Date().toISOString().slice(0, 10),
    description: '',
    type: 'transfer',
    category_id: null,
});

// Color palette for account markers
const colors = [
    '#3b82f6', // blue
    '#10b981', // emerald
    '#f59e0b', // amber
    '#ef4444', // red
    '#8b5cf6', // violet
    '#06b6d4', // cyan
    '#f97316', // orange
    '#84cc16', // lime
    '#ec4899', // pink
    '#6b7280', // gray
];

// Format currency
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

// Account type icon
const accountTypeIcon = (type: string) => {
    const icons: Record<string, any> = {
        cash: Coins,
        bank: Banknote,
        'e-wallet': Wallet,
        credit_card: CreditCard,
        investment: TrendingUp,
    };

    return icons[type] || Wallet;
};

const accountTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
        cash: 'Kas Tunai',
        bank: 'Rekening Bank',
        'e-wallet': 'E-Wallet',
        credit_card: 'Kartu Kredit',
        investment: 'Investasi',
    };

    return labels[type] || type;
};

// Computed for transfer
const sourceAccount = computed(() =>
    props.accounts.find((a) => a.id.toString() === transferForm.account_id),
);
const destAccount = computed(() =>
    props.accounts.find(
        (a) => a.id.toString() === transferForm.destination_account_id,
    ),
);
const isMultiCurrency = computed(
    () =>
        sourceAccount.value &&
        destAccount.value &&
        sourceAccount.value.currency !== destAccount.value.currency,
);

// Handlers
const handleCreate = () => {
    createForm.post(storeAccount().url, {
        onSuccess: () => {
            isCreateOpen.value = false;
            createForm.reset();
        },
    });
};

const handleEdit = () => {
    if (!selectedAccount.value) {
return;
}

    editForm.post(updateAccount(selectedAccount.value).url, {
        onSuccess: () => {
            isEditOpen.value = false;
        },
    });
};

const openEdit = (acc: any) => {
    selectedAccount.value = acc;
    editForm.name = acc.name;
    editForm.type = acc.type;
    editForm.account_number = acc.account_number || '';
    editForm.account_holder = acc.account_holder || '';
    editForm.logo = null;
    editForm.balance = acc.balance;
    editForm.currency = acc.currency;
    editForm.color = acc.color || '#3b82f6';
    editForm.notes = acc.notes || '';
    isEditOpen.value = true;
};

const handleDelete = async (acc: any) => {
    const isConfirmed = await confirm({
        title: 'Hapus Rekening',
        message: `Hapus rekening "${acc.name}"? Semua data terkait akan hilang.`,
        confirmText: 'Hapus',
        cancelText: 'Batal',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(destroyAccount(acc).url);
    }
};

const openTransfer = (acc: any) => {
    transferForm.account_id = acc.id.toString();
    transferForm.destination_account_id = '';
    transferForm.amount = 0;
    transferForm.converted_amount = 0;
    transferForm.exchange_rate = 1;
    transferForm.transaction_date = new Date().toISOString().slice(0, 10);
    transferForm.description = '';
    isTransferOpen.value = true;
};

const handleTransferAmountChange = () => {
    if (isMultiCurrency.value && transferForm.exchange_rate) {
        transferForm.converted_amount =
            transferForm.amount * transferForm.exchange_rate;
    } else {
        transferForm.converted_amount = transferForm.amount;
    }
};

const handleTransferRateChange = () => {
    if (isMultiCurrency.value && transferForm.amount) {
        transferForm.converted_amount =
            transferForm.amount * transferForm.exchange_rate;
    }
};

const handleTransferConvertedAmountChange = () => {
    if (isMultiCurrency.value && transferForm.amount) {
        transferForm.exchange_rate =
            transferForm.converted_amount / transferForm.amount;
    }
};

const handleTransfer = () => {
    const payload = useForm({
        account_id: parseInt(transferForm.account_id),
        destination_account_id: parseInt(transferForm.destination_account_id),
        amount: transferForm.amount,
        converted_amount: isMultiCurrency.value
            ? transferForm.converted_amount
            : transferForm.amount,
        exchange_rate: isMultiCurrency.value ? transferForm.exchange_rate : 1,
        transaction_date: transferForm.transaction_date,
        description: transferForm.description,
        type: 'transfer',
        category_id: null,
    });
    payload.post(storeTransaction().url, {
        onSuccess: () => {
            isTransferOpen.value = false;
        },
    });
};
</script>

<template>
    <Head title="Rekening" />

    <div class="space-y-6 px-6 py-6">
        <!-- Header -->
        <div
            class="flex flex-col justify-between gap-4 border-b pb-5 md:flex-row md:items-center"
        >
            <div class="space-y-1">
                <h2
                    class="flex items-center gap-2 text-2xl font-bold tracking-tight"
                >
                    <Wallet class="size-6 text-primary" />
                    Rekening Saya
                </h2>
                <p class="text-sm text-muted-foreground">
                    Kelola semua akun keuangan Anda — bank, e-wallet, kartu
                    kredit, dan kas tunai.
                </p>
            </div>

            <Button
                @click="isCreateOpen = true"
                class="h-9 gap-1.5 text-xs font-medium"
            >
                <Plus class="size-4" />
                Tambah Rekening
            </Button>
        </div>

        <!-- Empty State -->
        <div
            v-if="accounts.length === 0"
            class="flex flex-col items-center justify-center rounded-xl border border-dashed py-20 text-center"
        >
            <Wallet class="mb-3 size-10 text-muted-foreground/40" />
            <h3 class="text-sm font-semibold">Belum ada rekening</h3>
            <p class="mt-1 text-xs text-muted-foreground">
                Tambahkan rekening pertama Anda untuk mulai mencatat keuangan.
            </p>
            <Button @click="isCreateOpen = true" size="sm" class="mt-4 gap-1.5">
                <Plus class="size-4" />
                Tambah Rekening
            </Button>
        </div>

        <!-- Accounts Grid -->
        <div
            v-else
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <Card
                v-for="acc in accounts"
                :key="acc.id"
                class="group relative overflow-hidden shadow-sm transition duration-200 hover:border-primary/30 hover:shadow-md"
            >
                <!-- Color accent bar on left -->
                <div
                    class="absolute top-0 left-0 h-full w-1"
                    :style="{ backgroundColor: acc.color || '#3b82f6' }"
                ></div>

                <CardHeader class="pb-2 pl-5">
                    <div class="flex items-center justify-between">
                        <!-- Account type badge -->
                        <span
                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold tracking-wide uppercase"
                            :style="{
                                backgroundColor:
                                    (acc.color || '#3b82f6') + '18',
                                color: acc.color || '#3b82f6',
                            }"
                        >
                            <component
                                :is="accountTypeIcon(acc.type)"
                                class="size-2.5"
                            />
                            {{ accountTypeLabel(acc.type) }}
                        </span>

                        <!-- Actions -->
                        <div
                            class="flex items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100"
                        >
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-7 hover:bg-muted"
                                @click="openEdit(acc)"
                            >
                                <Pencil class="size-3 text-muted-foreground" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-7 hover:bg-muted"
                                @click="handleDelete(acc)"
                            >
                                <Trash2 class="size-3 text-rose-500" />
                            </Button>
                        </div>
                    </div>
                    
                    <!-- Logo & Rincian Rekening -->
                    <div class="flex items-center gap-3 mt-3">
                        <div v-if="acc.logo_path" class="size-10 rounded border border-zinc-200 dark:border-zinc-800 bg-white flex items-center justify-center p-1 shrink-0 shadow-xs">
                            <img :src="`/storage/${acc.logo_path}`" class="size-full object-contain" />
                        </div>
                        <div v-else class="size-10 rounded border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 flex items-center justify-center shrink-0 shadow-xs">
                            <component :is="accountTypeIcon(acc.type)" class="size-5 text-muted-foreground" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <CardTitle class="text-sm font-bold truncate leading-tight">{{ acc.name }}</CardTitle>
                            <div v-if="acc.account_number" class="text-[10px] text-muted-foreground font-mono truncate mt-0.5">
                                {{ acc.account_number }}
                            </div>
                            <div v-if="acc.account_holder" class="text-[9px] text-muted-foreground/85 truncate font-medium mt-0.5">
                                a.n. {{ acc.account_holder }}
                            </div>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="space-y-4 pl-5">
                    <div>
                        <span class="text-xs text-muted-foreground">Saldo</span>
                        <div
                            class="mt-0.5 font-mono text-2xl font-bold tracking-tight"
                        >
                            {{ formatCurrency(acc.balance, acc.currency) }}
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between border-t pt-2"
                    >
                        <div class="text-xs text-muted-foreground">
                            {{
                                acc.transactions_count +
                                acc.destination_transactions_count
                            }}
                            Transaksi
                        </div>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="h-7 px-2 text-xs hover:bg-muted"
                            @click="openTransfer(acc)"
                        >
                            <ArrowRightLeft class="mr-1 size-3.5" />
                            Transfer
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- DIALOG MODALS -->

        <!-- Create Account Modal -->
        <Dialog :open="isCreateOpen" @update:open="isCreateOpen = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Tambah Rekening Baru</DialogTitle>
                    <DialogDescription
                        >Buat rekening penyimpanan kas, tabungan bank, e-wallet,
                        atau kartu kredit baru.</DialogDescription
                    >
                </DialogHeader>

                <form @submit.prevent="handleCreate" class="space-y-4 py-2">
                    <div class="space-y-2">
                        <Label for="create-name">Nama Rekening</Label>
                        <Input
                            id="create-name"
                            v-model="createForm.name"
                            placeholder="Misal: Bank BCA, GoPay Utama"
                            required
                        />
                        <p
                            v-if="createForm.errors.name"
                            class="text-xs text-destructive"
                        >
                            {{ createForm.errors.name }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="create-type">Jenis</Label>
                            <Select v-model="createForm.type">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih Jenis" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="cash"
                                        >Kas Tunai</SelectItem
                                    >
                                    <SelectItem value="bank"
                                        >Rekening Bank</SelectItem
                                    >
                                    <SelectItem value="e-wallet"
                                        >E-Wallet (Dompet Digital)</SelectItem
                                    >
                                    <SelectItem value="credit_card"
                                        >Kartu Kredit</SelectItem
                                    >
                                    <SelectItem value="investment"
                                        >Investasi</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label for="create-currency">Mata Uang</Label>
                            <Select v-model="createForm.currency">
                                <SelectTrigger>
                                    <SelectValue placeholder="Mata Uang" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="IDR"
                                        >IDR (Rupiah)</SelectItem
                                    >
                                    <SelectItem value="USD"
                                        >USD (Dolar AS)</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="create-account-number">Nomor Rekening / Kode</Label>
                            <Input
                                id="create-account-number"
                                v-model="createForm.account_number"
                                placeholder="Misal: 123-456-7890"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="create-account-holder">Atas Nama (A/N)</Label>
                            <Input
                                id="create-account-holder"
                                v-model="createForm.account_holder"
                                placeholder="Misal: PT Workspace Jaya"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="create-logo">Logo Rekening / Bank</Label>
                        <Input
                            id="create-logo"
                            type="file"
                            accept="image/*"
                            @change="createForm.logo = ($event.target as HTMLInputElement).files?.[0] || null"
                            class="cursor-pointer text-xs h-9"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="create-balance">Saldo Awal</Label>
                        <Input
                            id="create-balance"
                            type="number"
                            step="0.01"
                            v-model="createForm.balance"
                            class="font-mono"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label>Warna Penanda</Label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="c in colors"
                                :key="c"
                                type="button"
                                class="size-6 transform rounded-full border transition duration-150 hover:scale-110"
                                :style="{ backgroundColor: c }"
                                :class="{
                                    'scale-105 ring-2 ring-ring':
                                        createForm.color === c,
                                }"
                                @click="createForm.color = c"
                            ></button>
                            <input
                                type="color"
                                v-model="createForm.color"
                                class="size-6 cursor-pointer overflow-hidden rounded-full border-0 bg-transparent p-0"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="create-notes">Catatan Tambahan</Label>
                        <Input
                            id="create-notes"
                            v-model="createForm.notes"
                            placeholder="Misal: Nomor Rekening 12345678"
                        />
                    </div>

                    <DialogFooter class="pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isCreateOpen = false"
                            >Batal</Button
                        >
                        <Button type="submit" :disabled="createForm.processing"
                            >Simpan</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Edit Account Modal -->
        <Dialog :open="isEditOpen" @update:open="isEditOpen = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Edit Rekening</DialogTitle>
                    <DialogDescription
                        >Ubah data rekening "{{
                            selectedAccount?.name
                        }}".</DialogDescription
                    >
                </DialogHeader>

                <form @submit.prevent="handleEdit" class="space-y-4 py-2">
                    <div class="space-y-2">
                        <Label for="edit-name">Nama Rekening</Label>
                        <Input
                            id="edit-name"
                            v-model="editForm.name"
                            required
                        />
                        <p
                            v-if="editForm.errors.name"
                            class="text-xs text-destructive"
                        >
                            {{ editForm.errors.name }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="edit-type">Jenis</Label>
                            <Select v-model="editForm.type">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih Jenis" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="cash"
                                        >Kas Tunai</SelectItem
                                    >
                                    <SelectItem value="bank"
                                        >Rekening Bank</SelectItem
                                    >
                                    <SelectItem value="e-wallet"
                                        >E-Wallet (Dompet Digital)</SelectItem
                                    >
                                    <SelectItem value="credit_card"
                                        >Kartu Kredit</SelectItem
                                    >
                                    <SelectItem value="investment"
                                        >Investasi</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label for="edit-currency">Mata Uang</Label>
                            <Select v-model="editForm.currency">
                                <SelectTrigger>
                                    <SelectValue placeholder="Mata Uang" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="IDR"
                                        >IDR (Rupiah)</SelectItem
                                    >
                                    <SelectItem value="USD"
                                        >USD (Dolar AS)</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="edit-account-number">Nomor Rekening / Kode</Label>
                            <Input
                                id="edit-account-number"
                                v-model="editForm.account_number"
                                placeholder="Misal: 123-456-7890"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="edit-account-holder">Atas Nama (A/N)</Label>
                            <Input
                                id="edit-account-holder"
                                v-model="editForm.account_holder"
                                placeholder="Misal: PT Workspace Jaya"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-logo">Logo Rekening / Bank (Unggah baru untuk mengganti)</Label>
                        <Input
                            id="edit-logo"
                            type="file"
                            accept="image/*"
                            @change="editForm.logo = ($event.target as HTMLInputElement).files?.[0] || null"
                            class="cursor-pointer text-xs h-9"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-balance"
                            >Saldo Saat Ini (Peringatan: Mengubah ini akan
                            memodifikasi saldo secara paksa)</Label
                        >
                        <Input
                            id="edit-balance"
                            type="number"
                            step="0.01"
                            v-model="editForm.balance"
                            class="font-mono"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label>Warna Penanda</Label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="c in colors"
                                :key="c"
                                type="button"
                                class="size-6 transform rounded-full border transition duration-150 hover:scale-110"
                                :style="{ backgroundColor: c }"
                                :class="{
                                    'scale-105 ring-2 ring-ring':
                                        editForm.color === c,
                                }"
                                @click="editForm.color = c"
                            ></button>
                            <input
                                type="color"
                                v-model="editForm.color"
                                class="size-6 cursor-pointer overflow-hidden rounded-full border-0 bg-transparent p-0"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-notes">Catatan Tambahan</Label>
                        <Input id="edit-notes" v-model="editForm.notes" />
                    </div>

                    <DialogFooter class="pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isEditOpen = false"
                            >Batal</Button
                        >
                        <Button type="submit" :disabled="editForm.processing"
                            >Simpan</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Transfer Funds Modal -->
        <Dialog :open="isTransferOpen" @update:open="isTransferOpen = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Transfer Dana Antar Rekening</DialogTitle>
                    <DialogDescription
                        >Pindahkan saldo dari satu akun rekening ke rekening
                        lainnya.</DialogDescription
                    >
                </DialogHeader>

                <form @submit.prevent="handleTransfer" class="space-y-4 py-2">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label>Rekening Asal</Label>
                            <Select v-model="transferForm.account_id" required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih Rekening" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="a in accounts"
                                        :key="a.id"
                                        :value="a.id.toString()"
                                    >
                                        {{ a.name }} ({{ a.currency }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label>Rekening Tujuan</Label>
                            <Select
                                v-model="transferForm.destination_account_id"
                                required
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih Rekening" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="a in accounts"
                                        :key="a.id"
                                        :value="a.id.toString()"
                                        :disabled="
                                            a.id.toString() ===
                                            transferForm.account_id
                                        "
                                    >
                                        {{ a.name }} ({{ a.currency }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Transfer Amount -->
                    <div class="space-y-2">
                        <Label for="tx-amount"
                            >Jumlah Transfer (dalam mata uang Rekening
                            Asal)</Label
                        >
                        <div class="relative">
                            <Input
                                id="tx-amount"
                                type="number"
                                step="0.01"
                                v-model="transferForm.amount"
                                @input="handleTransferAmountChange"
                                class="pl-14 font-mono"
                                required
                            />
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 font-mono text-sm text-muted-foreground uppercase"
                            >
                                {{ sourceAccount?.currency || 'Val' }}
                            </div>
                        </div>
                    </div>

                    <!-- Multi-currency section -->
                    <div
                        v-if="isMultiCurrency"
                        class="space-y-3 rounded-lg border bg-muted/60 p-3"
                    >
                        <div
                            class="flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400"
                        >
                            <Info class="size-4 shrink-0" />
                            <span
                                >Mata uang berbeda. Mohon tentukan kurs
                                penukaran.</span
                            >
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <Label for="tx-rate" class="text-xs"
                                    >Kurs Konversi</Label
                                >
                                <Input
                                    id="tx-rate"
                                    type="number"
                                    step="0.000001"
                                    v-model="transferForm.exchange_rate"
                                    @input="handleTransferRateChange"
                                    class="h-8 font-mono text-xs"
                                />
                            </div>
                            <div class="space-y-1">
                                <Label for="tx-converted" class="text-xs"
                                    >Jumlah Diterima (Tujuan)</Label
                                >
                                <div class="relative">
                                    <Input
                                        id="tx-converted"
                                        type="number"
                                        step="0.01"
                                        v-model="transferForm.converted_amount"
                                        @input="
                                            handleTransferConvertedAmountChange
                                        "
                                        class="h-8 pl-12 font-mono text-xs"
                                    />
                                    <div
                                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 font-mono text-[10px] text-muted-foreground uppercase"
                                    >
                                        {{ destAccount?.currency }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            class="mt-1 text-center font-mono text-[10px] text-muted-foreground"
                        >
                            1 {{ sourceAccount?.currency }} =
                            {{ transferForm.exchange_rate }}
                            {{ destAccount?.currency }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="tx-date">Tanggal Transaksi</Label>
                        <Input
                            id="tx-date"
                            type="date"
                            v-model="transferForm.transaction_date"
                            required
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="tx-desc"
                            >Deskripsi / Catatan Transfer</Label
                        >
                        <Input
                            id="tx-desc"
                            v-model="transferForm.description"
                            placeholder="Misal: Isi saldo e-wallet dari bank"
                        />
                    </div>

                    <DialogFooter class="pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isTransferOpen = false"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            :disabled="transferForm.processing"
                            >Kirim Transfer</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>

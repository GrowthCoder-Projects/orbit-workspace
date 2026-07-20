<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import {
    PiggyBank,
    Plus,
    Pencil,
    Trash2,
    Search,
    ArrowUpRight,
    ArrowDownRight,
    ArrowRightLeft,
    Download,
    Eye,
    Tag,
    X,
    Filter,
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
    index as transactionsIndex,
    store as storeTransaction,
    update as updateTransaction,
    destroy as destroyTransaction,
} from '@/routes/transactions';

const { confirm } = useConfirm();

const props = defineProps<{
    transactions: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        total: number;
    };
    accounts: any[];
    categories: any[];
    clients: any[];
    filters: any;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Finance Dashboard',
                href: '/app/finance',
            },
            {
                title: 'Transaksi Ledger',
                href: transactionsIndex().url,
            },
        ],
    },
});

// Modal States
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const selectedTransaction = ref<any>(null);

// Search & Filter State
const searchInput = ref(props.filters.search || '');
const filterType = ref(props.filters.type || 'all');
const filterAccount = ref(props.filters.account_id || 'all');
const filterCategory = ref(props.filters.category_id || 'all');
const filterClient = ref(props.filters.client_id || 'all');
const filterStartDate = ref(props.filters.start_date || '');
const filterEndDate = ref(props.filters.end_date || '');

// Forms
const createForm = useForm({
    account_id: '',
    destination_account_id: '',
    category_id: '',
    client_id: '',
    type: 'expense',
    amount: 0,
    converted_amount: null as number | null,
    exchange_rate: null as number | null,
    transaction_date: new Date().toISOString().split('T')[0],
    description: '',
    tags: [] as string[],
    attachment: null as File | null,
});

const editForm = useForm({
    account_id: '',
    destination_account_id: '',
    category_id: '',
    client_id: '',
    type: 'expense',
    amount: 0,
    converted_amount: null as number | null,
    exchange_rate: null as number | null,
    transaction_date: '',
    description: '',
    tags: [] as string[],
    attachment: null as File | null,
    _method: 'PATCH', // For file uploads via POST method simulation in multipart forms
});

// Tags helper state
const newTagText = ref('');

// Watchers / triggers for filters
const triggerFilter = () => {
    router.get(
        transactionsIndex().url,
        {
            search: searchInput.value || null,
            type: filterType.value === 'all' ? null : filterType.value,
            account_id:
                filterAccount.value === 'all' ? null : filterAccount.value,
            category_id:
                filterCategory.value === 'all' ? null : filterCategory.value,
            client_id: filterClient.value === 'all' ? null : filterClient.value,
            start_date: filterStartDate.value || null,
            end_date: filterEndDate.value || null,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    searchInput.value = '';
    filterType.value = 'all';
    filterAccount.value = 'all';
    filterCategory.value = 'all';
    filterClient.value = 'all';
    filterStartDate.value = '';
    filterEndDate.value = '';
    triggerFilter();
};

// Form Open Handlers
const openCreate = () => {
    createForm.reset();
    newTagText.value = '';
    isCreateOpen.value = true;
};

const openEdit = (tx: any) => {
    selectedTransaction.value = tx;
    editForm.account_id = tx.account_id.toString();
    editForm.destination_account_id = tx.destination_account_id
        ? tx.destination_account_id.toString()
        : '';
    editForm.category_id = tx.category_id ? tx.category_id.toString() : '';
    editForm.client_id = tx.client_id ? tx.client_id.toString() : '';
    editForm.type = tx.type;
    editForm.amount = tx.amount;
    editForm.converted_amount = tx.converted_amount || 0;
    editForm.exchange_rate = tx.exchange_rate || 1.0;
    editForm.transaction_date = tx.transaction_date;
    editForm.description = tx.description || '';
    editForm.tags = tx.tags || [];
    editForm.attachment = null;
    newTagText.value = '';
    isEditOpen.value = true;
};

// Tags Control
const addTag = (formType: 'create' | 'edit') => {
    const tag = newTagText.value.trim().toLowerCase();

    if (!tag) {
return;
}

    const form = formType === 'create' ? createForm : editForm;

    if (!form.tags.includes(tag)) {
        form.tags.push(tag);
    }

    newTagText.value = '';
};

const removeTag = (formType: 'create' | 'edit', index: number) => {
    const form = formType === 'create' ? createForm : editForm;
    form.tags.splice(index, 1);
};

// File Attachment Upload Helpers
const handleFileUpload = (e: any, formType: 'create' | 'edit') => {
    const file = e.target.files[0];

    if (!file) {
return;
}

    if (formType === 'create') {
        createForm.attachment = file;
    } else {
        editForm.attachment = file;
    }
};

// Handlers
const handleCreate = () => {
    createForm.post(storeTransaction().url, {
        onSuccess: () => {
            isCreateOpen.value = false;
            createForm.reset();
            toast.success('Transaksi berhasil dicatat.');
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
            toast.error(
                'Gagal mencatat transaksi. Silakan periksa kembali inputan Anda.',
            );
        },
    });
};

const handleEdit = () => {
    if (!selectedTransaction.value) {
return;
}

    // Using standard Inertia route with POST and _method: 'PATCH' since multipart forms containing files must be POST in PHP
    editForm.post(updateTransaction(selectedTransaction.value.id).url, {
        onSuccess: () => {
            isEditOpen.value = false;
            selectedTransaction.value = null;
            toast.success('Transaksi berhasil diperbarui.');
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
            toast.error(
                'Gagal memperbarui transaksi. Silakan periksa kembali inputan Anda.',
            );
        },
    });
};

const handleDelete = async (tx: any) => {
    const isConfirmed = await confirm({
        title: 'Hapus Transaksi',
        message: `Apakah Anda yakin ingin menghapus transaksi "${tx.description || 'ini'}"? Tindakan ini akan mengupdate saldo rekening!`,
        confirmText: 'Hapus',
        cancelText: 'Batal',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(destroyTransaction(tx.id).url);
    }
};

// Helper Mata Uang
const sourceAccount = (formType: 'create' | 'edit') => {
    const form = formType === 'create' ? createForm : editForm;

    return props.accounts.find((a) => a.id.toString() === form.account_id);
};

const destAccount = (formType: 'create' | 'edit') => {
    const form = formType === 'create' ? createForm : editForm;

    return props.accounts.find(
        (a) => a.id.toString() === form.destination_account_id,
    );
};

const isMultiCurrency = (formType: 'create' | 'edit') => {
    const src = sourceAccount(formType);
    const dst = destAccount(formType);

    return src && dst && src.currency !== dst.currency;
};

const handleAmountChange = (formType: 'create' | 'edit') => {
    const form = formType === 'create' ? createForm : editForm;

    if (form.type === 'transfer' && isMultiCurrency(formType)) {
        form.converted_amount = Number(
            (form.amount * form.exchange_rate).toFixed(2),
        );
    }
};

const handleRateChange = (formType: 'create' | 'edit') => {
    const form = formType === 'create' ? createForm : editForm;

    if (form.type === 'transfer' && isMultiCurrency(formType)) {
        form.converted_amount = Number(
            (form.amount * form.exchange_rate).toFixed(2),
        );
    }
};

const handleConvertedAmountChange = (formType: 'create' | 'edit') => {
    const form = formType === 'create' ? createForm : editForm;

    if (
        form.type === 'transfer' &&
        isMultiCurrency(formType) &&
        form.amount > 0
    ) {
        form.exchange_rate = Number(
            (form.converted_amount / form.amount).toFixed(6),
        );
    }
};

// Format Helpers
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

// Debt vs Cash Color Coding
const getTxAmountClass = (type: string) => {
    switch (type) {
        case 'income':
            return 'text-emerald-500 font-bold';
        case 'expense':
            return 'text-rose-500 font-bold';
        default:
            return 'text-blue-400 font-bold';
    }
};
</script>

<template>
    <Head title="Ledger Keuangan" />

    <div class="space-y-6 px-6 py-6">
        <!-- Header -->
        <div
            class="flex flex-col justify-between gap-4 border-b border-border pb-5 md:flex-row md:items-center"
        >
            <div class="space-y-1">
                <h2
                    class="font-outfit flex items-center gap-2 text-2xl font-bold tracking-tight"
                >
                    <ArrowRightLeft class="size-6 text-emerald-500" />
                    Transaksi Ledger
                </h2>
                <p class="text-sm text-muted-foreground">
                    Catat pengeluaran baru, klaim invoice dari klien, dan mutasi
                    saldo antar rekening.
                </p>
            </div>

            <div>
                <Button
                    size="sm"
                    @click="openCreate()"
                    class="flex items-center gap-1.5"
                >
                    <Plus class="size-4" />
                    Catat Transaksi Baru
                </Button>
            </div>
        </div>

        <!-- Filter Controls Card -->
        <Card class="shadow-sm">
            <CardHeader class="flex flex-row items-center justify-between pb-3">
                <CardTitle
                    class="flex items-center gap-2 text-sm font-medium text-foreground/80"
                >
                    <Filter class="size-4 text-muted-foreground" />
                    Filter & Pencarian
                </CardTitle>
                <Button
                    v-if="
                        filters.search ||
                        filters.type ||
                        filters.account_id ||
                        filters.category_id ||
                        filters.client_id ||
                        filters.start_date ||
                        filters.end_date
                    "
                    variant="ghost"
                    size="xs"
                    class="h-7 text-xs text-muted-foreground hover:bg-muted"
                    @click="clearFilters"
                >
                    Hapus Filter
                </Button>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-4">
                <!-- Search -->
                <div class="relative md:col-span-2">
                    <Search
                        class="absolute top-2.5 left-3 size-4 text-muted-foreground"
                    />
                    <Input
                        v-model="searchInput"
                        placeholder="Cari deskripsi, tag, kategori..."
                        class="pl-9 text-foreground"
                        @keyup.enter="triggerFilter"
                    />
                </div>

                <!-- Type -->
                <Select v-model="filterType" @update:modelValue="triggerFilter">
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Tipe Transaksi" />
                    </SelectTrigger>
                    <SelectContent class="">
                        <SelectItem value="all">Semua Tipe</SelectItem>
                        <SelectItem value="income">Pemasukan</SelectItem>
                        <SelectItem value="expense">Pengeluaran</SelectItem>
                        <SelectItem value="transfer">Transfer Dana</SelectItem>
                    </SelectContent>
                </Select>

                <!-- Account -->
                <Select
                    v-model="filterAccount"
                    @update:modelValue="triggerFilter"
                >
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Semua Rekening" />
                    </SelectTrigger>
                    <SelectContent class="">
                        <SelectItem value="all">Semua Rekening</SelectItem>
                        <SelectItem
                            v-for="a in accounts"
                            :key="a.id"
                            :value="a.id.toString()"
                        >
                            {{ a.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <!-- Category -->
                <Select
                    v-model="filterCategory"
                    @update:modelValue="triggerFilter"
                >
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Semua Kategori" />
                    </SelectTrigger>
                    <SelectContent class="">
                        <SelectItem value="all">Semua Kategori</SelectItem>
                        <SelectItem
                            v-for="c in categories"
                            :key="c.id"
                            :value="c.id.toString()"
                        >
                            {{ c.name }} ({{
                                c.type === 'income' ? 'Masuk' : 'Keluar'
                            }})
                        </SelectItem>
                    </SelectContent>
                </Select>

                <!-- Client -->
                <Select
                    v-model="filterClient"
                    @update:modelValue="triggerFilter"
                >
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Semua Klien" />
                    </SelectTrigger>
                    <SelectContent class="">
                        <SelectItem value="all">Semua Klien</SelectItem>
                        <SelectItem
                            v-for="cl in clients"
                            :key="cl.id"
                            :value="cl.id.toString()"
                        >
                            {{ cl.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <!-- Dates -->
                <div class="grid grid-cols-2 gap-2 md:col-span-2">
                    <Input
                        type="date"
                        v-model="filterStartDate"
                        @change="triggerFilter"
                        class=""
                    />
                    <Input
                        type="date"
                        v-model="filterEndDate"
                        @change="triggerFilter"
                        class=""
                    />
                </div>
            </CardContent>
        </Card>

        <!-- Transaction Table Card -->
        <Card class="shadow-sm">
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-foreground/80">
                        <thead
                            class="border-b border-border bg-background text-xs text-muted-foreground uppercase"
                        >
                            <tr>
                                <th scope="col" class="px-6 py-4">Tanggal</th>
                                <th scope="col" class="px-6 py-4">Deskripsi</th>
                                <th scope="col" class="px-6 py-4">Rekening</th>
                                <th scope="col" class="px-6 py-4">
                                    Kategori / Klien
                                </th>
                                <th scope="col" class="px-6 py-4">Tipe</th>
                                <th scope="col" class="px-6 py-4 text-right">
                                    Jumlah
                                </th>
                                <th scope="col" class="px-6 py-4 text-right">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-if="transactions.data.length === 0">
                                <td
                                    colspan="7"
                                    class="px-6 py-12 text-center text-muted-foreground"
                                >
                                    Tidak ada transaksi yang cocok dengan filter
                                    atau ledger kosong.
                                </td>
                            </tr>
                            <tr
                                v-for="tx in transactions.data"
                                :key="tx.id"
                                class="transition duration-150 hover:bg-muted/30"
                            >
                                <!-- Date -->
                                <td
                                    class="px-6 py-4 font-mono text-xs whitespace-nowrap"
                                >
                                    {{
                                        new Date(
                                            tx.transaction_date,
                                        ).toLocaleDateString('id-ID', {
                                            day: '2-digit',
                                            month: '2-digit',
                                            year: 'numeric',
                                        })
                                    }}
                                </td>

                                <!-- Description -->
                                <td class="px-6 py-4">
                                    <div class="font-medium text-foreground">
                                        {{
                                            tx.description ||
                                            (tx.type === 'transfer'
                                                ? 'Transfer Dana'
                                                : 'Transaksi Tanpa Deskripsi')
                                        }}
                                    </div>
                                    <div
                                        v-if="tx.tags && tx.tags.length > 0"
                                        class="mt-1 flex flex-wrap gap-1"
                                    >
                                        <span
                                            v-for="tag in tx.tags"
                                            :key="tag"
                                            class="py-0.2 rounded bg-muted px-1.5 text-[10px] text-muted-foreground"
                                        >
                                            {{ tag }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Account -->
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-muted-foreground"
                                >
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            class="font-medium text-foreground/80"
                                            >{{ tx.account?.name }}</span
                                        >
                                        <span
                                            v-if="tx.destination_account"
                                            class="flex items-center gap-1 text-muted-foreground"
                                        >
                                            ➔
                                            <span
                                                class="font-medium text-foreground/80"
                                                >{{
                                                    tx.destination_account?.name
                                                }}</span
                                            >
                                        </span>
                                    </div>
                                </td>

                                <!-- Category / Client -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div
                                        v-if="tx.category"
                                        class="inline-flex items-center gap-1"
                                    >
                                        <span
                                            class="size-2 rounded-full"
                                            :style="{
                                                backgroundColor:
                                                    tx.category.color,
                                            }"
                                        ></span>
                                        <span>{{ tx.category.name }}</span>
                                    </div>
                                    <div
                                        v-if="tx.client"
                                        class="mt-0.5 text-xs text-muted-foreground"
                                    >
                                        Klien:
                                        <span
                                            class="font-medium text-muted-foreground"
                                            >{{ tx.client.name }}</span
                                        >
                                    </div>
                                </td>

                                <!-- Type -->
                                <td
                                    class="px-6 py-4 font-mono text-xs whitespace-nowrap uppercase"
                                >
                                    <span
                                        class="rounded px-2 py-0.5 text-[10px] tracking-wide"
                                        :class="{
                                            'bg-emerald-500/10 text-emerald-500':
                                                tx.type === 'income',
                                            'bg-rose-500/10 text-rose-500':
                                                tx.type === 'expense',
                                            'bg-blue-500/10 text-blue-400':
                                                tx.type === 'transfer',
                                        }"
                                    >
                                        {{ tx.type }}
                                    </span>
                                </td>

                                <!-- Amount -->
                                <td
                                    class="px-6 py-4 text-right font-mono whitespace-nowrap"
                                >
                                    <div :class="getTxAmountClass(tx.type)">
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
                                    </div>
                                    <div
                                        v-if="
                                            tx.type === 'transfer' &&
                                            tx.account?.currency !==
                                                tx.destination_account?.currency
                                        "
                                        class="text-[10px] text-muted-foreground"
                                    >
                                        ➔
                                        {{
                                            formatCurrency(
                                                tx.converted_amount,
                                                tx.destination_account
                                                    ?.currency,
                                            )
                                        }}
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td
                                    class="px-6 py-4 text-right whitespace-nowrap"
                                >
                                    <div
                                        class="flex items-center justify-end gap-1.5"
                                    >
                                        <a
                                            v-if="tx.attachment_path"
                                            :href="`/storage/${tx.attachment_path}`"
                                            target="_blank"
                                            class="rounded p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground"
                                            title="Lihat Lampiran"
                                        >
                                            <Eye class="size-4" />
                                        </a>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="size-8 hover:bg-muted"
                                            @click="openEdit(tx)"
                                        >
                                            <Pencil
                                                class="size-4 text-muted-foreground"
                                            />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="size-8 hover:bg-muted"
                                            @click="handleDelete(tx)"
                                        >
                                            <Trash2
                                                class="size-4 text-rose-400"
                                            />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Native Pagination Controls -->
                <div
                    v-if="transactions.last_page > 1"
                    class="flex items-center justify-between border-t border-border bg-background/20 px-6 py-4 text-xs text-muted-foreground"
                >
                    <div>
                        Menampilkan
                        <span class="font-bold text-foreground">{{
                            transactions.data.length
                        }}</span>
                        dari
                        <span class="font-bold text-foreground">{{
                            transactions.total
                        }}</span>
                        transaksi
                    </div>
                    <div class="flex items-center gap-1">
                        <Button
                            v-for="link in transactions.links"
                            :key="link.label"
                            as-child
                            variant="outline"
                            size="xs"
                            class="h-7 border-border text-xs"
                            :class="{
                                'bg-emerald-600 text-foreground hover:bg-emerald-700':
                                    link.active,
                                'pointer-events-none opacity-50': !link.url,
                            }"
                        >
                            <Link
                                :href="link.url || '#'"
                                v-html="link.label"
                                preserve-state
                            ></Link>
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- DIALOG MODALS -->

        <!-- Create Transaction Modal -->
        <Dialog :open="isCreateOpen" @update:open="isCreateOpen = $event">
            <DialogContent
                class="max-h-[90vh] max-w-lg overflow-y-auto rounded-xl border border-border p-6 shadow-lg"
            >
                <DialogHeader
                    class="space-y-1.5 border-b border-border/60 pb-4"
                >
                    <DialogTitle
                        class="flex items-center gap-2 text-xl font-bold"
                    >
                        <span
                            class="rounded-lg bg-primary/10 p-1.5 text-primary"
                        >
                            <Plus class="size-5" />
                        </span>
                        Catat Transaksi Baru
                    </DialogTitle>
                    <DialogDescription class="text-xs text-muted-foreground">
                        Masukkan data transaksi kas masuk, kas keluar, atau
                        mutasi saldo rekening secara real-time.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="handleCreate" class="space-y-5 pt-4">
                    <!-- Type Selector -->
                    <div class="space-y-2">
                        <Label
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >Jenis Transaksi</Label
                        >
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                v-for="t in ['expense', 'income', 'transfer']"
                                :key="t"
                                type="button"
                                class="flex items-center justify-center gap-2 rounded-lg border py-2.5 text-xs font-semibold transition-all duration-200"
                                :class="
                                    createForm.type === t
                                        ? t === 'expense'
                                            ? 'border-rose-500 bg-rose-500 text-white shadow-xs'
                                            : t === 'income'
                                              ? 'border-emerald-500 bg-emerald-500 text-white shadow-xs'
                                              : 'border-blue-500 bg-blue-500 text-white shadow-xs'
                                        : 'border-transparent bg-muted/50 text-muted-foreground hover:bg-muted hover:text-foreground'
                                "
                                @click="createForm.type = t"
                            >
                                <component
                                    :is="
                                        t === 'income'
                                            ? ArrowUpRight
                                            : t === 'expense'
                                              ? ArrowDownRight
                                              : ArrowRightLeft
                                    "
                                    class="size-4"
                                />
                                {{
                                    t === 'income'
                                        ? 'Pemasukan'
                                        : t === 'expense'
                                          ? 'Pengeluaran'
                                          : 'Transfer'
                                }}
                            </button>
                        </div>
                    </div>

                    <!-- Accounts & Categories Grid -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >{{
                                    createForm.type === 'transfer'
                                        ? 'Rekening Asal'
                                        : 'Rekening'
                                }}</Label
                            >
                            <Select v-model="createForm.account_id" required>
                                <SelectTrigger class="h-10 w-full">
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
                            <p
                                v-if="createForm.errors.account_id"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ createForm.errors.account_id }}
                            </p>
                        </div>

                        <!-- Destination Account (Only for transfers) -->
                        <div
                            v-if="createForm.type === 'transfer'"
                            class="space-y-2"
                        >
                            <Label
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >Rekening Tujuan</Label
                            >
                            <Select
                                v-model="createForm.destination_account_id"
                                required
                            >
                                <SelectTrigger class="h-10 w-full">
                                    <SelectValue placeholder="Pilih Rekening" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="a in accounts"
                                        :key="a.id"
                                        :value="a.id.toString()"
                                        :disabled="
                                            a.id.toString() ===
                                            createForm.account_id
                                        "
                                    >
                                        {{ a.name }} ({{ a.currency }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="createForm.errors.destination_account_id"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ createForm.errors.destination_account_id }}
                            </p>
                        </div>

                        <!-- Category (Only for income/expense) -->
                        <div v-else class="space-y-2">
                            <Label
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >Kategori</Label
                            >
                            <Select
                                v-model="createForm.category_id"
                                :required="createForm.type !== 'transfer'"
                            >
                                <SelectTrigger class="h-10 w-full">
                                    <SelectValue placeholder="Pilih Kategori" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="c in categories.filter(
                                            (cat) =>
                                                cat.type === createForm.type,
                                        )"
                                        :key="c.id"
                                        :value="c.id.toString()"
                                    >
                                        {{ c.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="createForm.errors.category_id"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ createForm.errors.category_id }}
                            </p>
                        </div>
                    </div>

                    <!-- Client Relation (Optional, typically for client project incomes) -->
                    <div v-if="createForm.type === 'income'" class="space-y-2">
                        <Label
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >Klien Terkait (Opsional)</Label
                        >
                        <Select v-model="createForm.client_id">
                            <SelectTrigger class="h-10 w-full">
                                <SelectValue placeholder="Pilih Klien" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="cl in clients"
                                    :key="cl.id"
                                    :value="cl.id.toString()"
                                >
                                    {{ cl.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="createForm.errors.client_id"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ createForm.errors.client_id }}
                        </p>
                    </div>

                    <!-- Amount Input -->
                    <div class="space-y-2">
                        <Label
                            for="amount"
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >Jumlah Uang</Label
                        >
                        <div
                            class="relative flex items-center rounded-lg border border-input bg-muted/20 transition duration-150 focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20"
                        >
                            <span
                                class="pl-4 font-mono text-sm font-bold text-muted-foreground uppercase select-none"
                            >
                                {{ sourceAccount('create')?.currency || 'VAL' }}
                            </span>
                            <input
                                id="amount"
                                type="number"
                                step="0.01"
                                v-model="createForm.amount"
                                @input="handleAmountChange('create')"
                                class="w-full bg-transparent py-3 pr-4 pl-3 font-mono text-lg font-bold text-foreground outline-hidden placeholder:text-muted-foreground/50"
                                placeholder="0"
                                required
                            />
                        </div>
                        <p
                            v-if="createForm.errors.amount"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ createForm.errors.amount }}
                        </p>
                    </div>

                    <!-- Cross currency values (Transfers only) -->
                    <div
                        v-if="
                            createForm.type === 'transfer' &&
                            isMultiCurrency('create')
                        "
                        class="space-y-3 rounded-lg border border-border bg-muted/30 p-3.5"
                    >
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <Label
                                    for="create-rate"
                                    class="text-[10px] font-semibold tracking-wider text-muted-foreground uppercase"
                                    >Kurs Konversi</Label
                                >
                                <Input
                                    id="create-rate"
                                    type="number"
                                    step="0.000001"
                                    v-model="createForm.exchange_rate"
                                    @input="handleRateChange('create')"
                                    class="h-9 font-mono text-xs text-foreground"
                                />
                            </div>
                            <div class="space-y-1">
                                <Label
                                    for="create-converted"
                                    class="text-[10px] font-semibold tracking-wider text-muted-foreground uppercase"
                                    >Diterima di Tujuan</Label
                                >
                                <div class="relative">
                                    <Input
                                        id="create-converted"
                                        type="number"
                                        step="0.01"
                                        v-model="createForm.converted_amount"
                                        @input="
                                            handleConvertedAmountChange(
                                                'create',
                                            )
                                        "
                                        class="h-9 pl-12 font-mono text-xs text-foreground"
                                    />
                                    <div
                                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 font-mono text-[10px] text-muted-foreground uppercase"
                                    >
                                        {{ destAccount('create')?.currency }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Details & Date -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label
                                for="tx-date"
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >Tanggal Transaksi</Label
                            >
                            <Input
                                id="tx-date"
                                type="date"
                                v-model="createForm.transaction_date"
                                class="h-10"
                                required
                            />
                            <p
                                v-if="createForm.errors.transaction_date"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ createForm.errors.transaction_date }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label
                                for="attachment"
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >Bukti / Resi (Opsional)</Label
                            >
                            <Input
                                id="attachment"
                                type="file"
                                @change="handleFileUpload($event, 'create')"
                                class="h-10 cursor-pointer py-2 text-xs text-foreground file:-my-1 file:mr-2 file:rounded-md file:border-0 file:bg-muted file:px-2 file:py-1 file:text-xs file:font-semibold file:text-foreground hover:file:bg-muted/80"
                            />
                            <p
                                v-if="createForm.errors.attachment"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ createForm.errors.attachment }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label
                            for="description"
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >Deskripsi / Catatan Transaksi</Label
                        >
                        <Input
                            id="description"
                            v-model="createForm.description"
                            placeholder="Misal: Beli makan siang, Transfer tabungan"
                            class="h-10"
                            required
                        />
                        <p
                            v-if="createForm.errors.description"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ createForm.errors.description }}
                        </p>
                    </div>

                    <!-- Tags -->
                    <div class="space-y-2">
                        <Label
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >Penanda (Tags)</Label
                        >
                        <div class="flex items-center gap-2">
                            <Input
                                v-model="newTagText"
                                placeholder="Ketik tag lalu klik Tambah"
                                class="h-9 text-xs text-foreground"
                                @keyup.enter.prevent="addTag('create')"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="addTag('create')"
                                class="h-9 border-border"
                            >
                                Tambah
                            </Button>
                        </div>
                        <div
                            v-if="createForm.tags.length > 0"
                            class="mt-2 flex flex-wrap gap-1.5"
                        >
                            <span
                                v-for="(tag, idx) in createForm.tags"
                                :key="tag"
                                class="shadow-3xs inline-flex items-center gap-1.5 rounded-full border border-border bg-muted px-3 py-1 text-xs font-medium text-foreground/80"
                            >
                                {{ tag }}
                                <button
                                    type="button"
                                    @click="removeTag('create', idx)"
                                    class="text-muted-foreground transition-colors hover:text-rose-500"
                                >
                                    <X class="size-3" />
                                </button>
                            </span>
                        </div>
                    </div>

                    <DialogFooter class="border-t border-border/60 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isCreateOpen = false"
                            class="font-medium hover:bg-muted"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            class="bg-primary font-semibold text-primary-foreground shadow-xs hover:bg-primary/95"
                            :disabled="createForm.processing"
                            >Simpan Transaksi</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Edit Transaction Modal -->
        <Dialog :open="isEditOpen" @update:open="isEditOpen = $event">
            <DialogContent
                class="max-h-[90vh] max-w-lg overflow-y-auto rounded-xl border border-border p-6 shadow-lg"
            >
                <DialogHeader
                    class="space-y-1.5 border-b border-border/60 pb-4"
                >
                    <DialogTitle
                        class="flex items-center gap-2 text-xl font-bold"
                    >
                        <span
                            class="rounded-lg bg-primary/10 p-1.5 text-primary"
                        >
                            <Pencil class="size-5" />
                        </span>
                        Edit Transaksi
                    </DialogTitle>
                    <DialogDescription class="text-xs text-muted-foreground">
                        Ubah rincian data transaksi Anda di bawah ini.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="handleEdit" class="space-y-5 pt-4">
                    <!-- Accounts & Categories Grid -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >{{
                                    editForm.type === 'transfer'
                                        ? 'Rekening Asal'
                                        : 'Rekening'
                                }}</Label
                            >
                            <Select v-model="editForm.account_id" required>
                                <SelectTrigger class="h-10 w-full">
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
                            <p
                                v-if="editForm.errors.account_id"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ editForm.errors.account_id }}
                            </p>
                        </div>

                        <!-- Destination Account (Only for transfers) -->
                        <div
                            v-if="editForm.type === 'transfer'"
                            class="space-y-2"
                        >
                            <Label
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >Rekening Tujuan</Label
                            >
                            <Select
                                v-model="editForm.destination_account_id"
                                required
                            >
                                <SelectTrigger class="h-10 w-full">
                                    <SelectValue placeholder="Pilih Rekening" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="a in accounts"
                                        :key="a.id"
                                        :value="a.id.toString()"
                                        :disabled="
                                            a.id.toString() ===
                                            editForm.account_id
                                        "
                                    >
                                        {{ a.name }} ({{ a.currency }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="editForm.errors.destination_account_id"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ editForm.errors.destination_account_id }}
                            </p>
                        </div>

                        <!-- Category (Only for income/expense) -->
                        <div v-else class="space-y-2">
                            <Label
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >Kategori</Label
                            >
                            <Select
                                v-model="editForm.category_id"
                                :required="editForm.type !== 'transfer'"
                            >
                                <SelectTrigger class="h-10 w-full">
                                    <SelectValue placeholder="Pilih Kategori" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="c in categories.filter(
                                            (cat) => cat.type === editForm.type,
                                        )"
                                        :key="c.id"
                                        :value="c.id.toString()"
                                    >
                                        {{ c.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="editForm.errors.category_id"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ editForm.errors.category_id }}
                            </p>
                        </div>
                    </div>

                    <!-- Client Relation (Optional, typically for client project incomes) -->
                    <div v-if="editForm.type === 'income'" class="space-y-2">
                        <Label
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >Klien Terkait (Opsional)</Label
                        >
                        <Select v-model="editForm.client_id">
                            <SelectTrigger class="h-10 w-full">
                                <SelectValue placeholder="Pilih Klien" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="cl in clients"
                                    :key="cl.id"
                                    :value="cl.id.toString()"
                                >
                                    {{ cl.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="editForm.errors.client_id"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ editForm.errors.client_id }}
                        </p>
                    </div>

                    <!-- Amount Input -->
                    <div class="space-y-2">
                        <Label
                            for="edit-amount"
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >Jumlah Uang</Label
                        >
                        <div
                            class="relative flex items-center rounded-lg border border-input bg-muted/20 transition duration-150 focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20"
                        >
                            <span
                                class="pl-4 font-mono text-sm font-bold text-muted-foreground uppercase select-none"
                            >
                                {{ sourceAccount('edit')?.currency || 'VAL' }}
                            </span>
                            <input
                                id="edit-amount"
                                type="number"
                                step="0.01"
                                v-model="editForm.amount"
                                @input="handleAmountChange('edit')"
                                class="w-full bg-transparent py-3 pr-4 pl-3 font-mono text-lg font-bold text-foreground outline-hidden placeholder:text-muted-foreground/50"
                                placeholder="0"
                                required
                            />
                        </div>
                        <p
                            v-if="editForm.errors.amount"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ editForm.errors.amount }}
                        </p>
                    </div>

                    <!-- Cross currency values (Transfers only) -->
                    <div
                        v-if="
                            editForm.type === 'transfer' &&
                            isMultiCurrency('edit')
                        "
                        class="space-y-3 rounded-lg border border-border bg-muted/30 p-3.5"
                    >
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <Label
                                    for="edit-rate-val"
                                    class="text-[10px] font-semibold tracking-wider text-muted-foreground uppercase"
                                    >Kurs Konversi</Label
                                >
                                <Input
                                    id="edit-rate-val"
                                    type="number"
                                    step="0.000001"
                                    v-model="editForm.exchange_rate"
                                    @input="handleRateChange('edit')"
                                    class="h-9 font-mono text-xs text-foreground"
                                />
                            </div>
                            <div class="space-y-1">
                                <Label
                                    for="edit-converted-val"
                                    class="text-[10px] font-semibold tracking-wider text-muted-foreground uppercase"
                                    >Diterima di Tujuan</Label
                                >
                                <div class="relative">
                                    <Input
                                        id="edit-converted-val"
                                        type="number"
                                        step="0.01"
                                        v-model="editForm.converted_amount"
                                        @input="
                                            handleConvertedAmountChange('edit')
                                        "
                                        class="h-9 pl-12 font-mono text-xs text-foreground"
                                    />
                                    <div
                                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 font-mono text-[10px] text-muted-foreground uppercase"
                                    >
                                        {{ destAccount('edit')?.currency }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Details & Date -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label
                                for="edit-tx-date"
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >Tanggal Transaksi</Label
                            >
                            <Input
                                id="edit-tx-date"
                                type="date"
                                v-model="editForm.transaction_date"
                                class="h-10"
                                required
                            />
                            <p
                                v-if="editForm.errors.transaction_date"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ editForm.errors.transaction_date }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label
                                for="edit-attachment"
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >Ganti Bukti / Resi (Opsional)</Label
                            >
                            <Input
                                id="edit-attachment"
                                type="file"
                                @change="handleFileUpload($event, 'edit')"
                                class="h-10 cursor-pointer py-2 text-xs text-foreground file:-my-1 file:mr-2 file:rounded-md file:border-0 file:bg-muted file:px-2 file:py-1 file:text-xs file:font-semibold file:text-foreground hover:file:bg-muted/80"
                            />
                            <p
                                v-if="editForm.errors.attachment"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ editForm.errors.attachment }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label
                            for="edit-description"
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >Deskripsi / Catatan Transaksi</Label
                        >
                        <Input
                            id="edit-description"
                            v-model="editForm.description"
                            class="h-10"
                            required
                        />
                        <p
                            v-if="editForm.errors.description"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ editForm.errors.description }}
                        </p>
                    </div>

                    <!-- Tags -->
                    <div class="space-y-2">
                        <Label
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >Penanda (Tags)</Label
                        >
                        <div class="flex items-center gap-2">
                            <Input
                                v-model="newTagText"
                                placeholder="Ketik tag lalu klik Tambah"
                                class="h-9 text-xs text-foreground"
                                @keyup.enter.prevent="addTag('edit')"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="addTag('edit')"
                                class="h-9 border-border"
                            >
                                Tambah
                            </Button>
                        </div>
                        <div
                            v-if="editForm.tags.length > 0"
                            class="mt-2 flex flex-wrap gap-1.5"
                        >
                            <span
                                v-for="(tag, idx) in editForm.tags"
                                :key="tag"
                                class="shadow-3xs inline-flex items-center gap-1.5 rounded-full border border-border bg-muted px-3 py-1 text-xs font-medium text-foreground/80"
                            >
                                {{ tag }}
                                <button
                                    type="button"
                                    @click="removeTag('edit', idx)"
                                    class="text-muted-foreground transition-colors hover:text-rose-500"
                                >
                                    <X class="size-3" />
                                </button>
                            </span>
                        </div>
                    </div>

                    <DialogFooter class="border-t border-border/60 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isEditOpen = false"
                            class="font-medium hover:bg-muted"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            class="bg-primary font-semibold text-primary-foreground shadow-xs hover:bg-primary/95"
                            :disabled="editForm.processing"
                            >Simpan Transaksi</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>

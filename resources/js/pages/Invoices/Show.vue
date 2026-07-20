<script setup lang="ts">
import { Head, Link, useForm, router, setLayoutProps } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Download,
    Pencil,
    CheckCircle2,
    Trash2,
    Calendar,
    User,
    Coins,
    Sliders,
    Building2,
    FileText,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
    destroy as destroyInvoice,
    pay as payInvoice,
    edit as editInvoice,
} from '@/routes/invoices';

const props = defineProps<{
    invoice: any;
    accounts: any[];
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Invoices',
            href: '/app/invoices',
        },
        {
            title: `Detail #${props.invoice.invoice_number}`,
            href: `/app/invoices/${props.invoice.id}`,
        },
    ],
});

// Pay Modal States
const isPayOpen = ref(false);
const payForm = useForm({
    finance_account_id: props.accounts[0]?.id?.toString() || '',
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

const openPayModal = () => {
    payForm.finance_account_id = props.accounts[0]?.id?.toString() || '';
    isPayOpen.value = true;
};

const submitPay = () => {
    payForm.post(payInvoice(props.invoice.id).url, {
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

const handleDelete = async () => {
    const isConfirmed = await confirm({
        title: 'Hapus Invoice',
        message: 'Apakah Anda yakin ingin menghapus invoice ini secara permanen?',
        confirmText: 'Hapus',
        cancelText: 'Batal',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(destroyInvoice(props.invoice.id).url, {
            onSuccess: () => {
                toast.success('Invoice berhasil dihapus.');
            },
            onError: () => {
                toast.error('Gagal menghapus invoice.');
            },
        });
    }
};

const getStatusClass = (status: string) => {
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

// Formatting spacing in live preview
const getSpacingPaddingClass = (spacing: string) => {
    switch (spacing) {
        case 'compact':
            return 'p-4 gap-y-3';
        case 'spacious':
            return 'p-10 gap-y-8';
        default:
            return 'p-6 gap-y-5';
    }
};

const getSpacingItemPaddingClass = (spacing: string) => {
    switch (spacing) {
        case 'compact':
            return 'py-2 px-3';
        case 'spacious':
            return 'py-5 px-6';
        default:
            return 'py-3.5 px-4';
    }
};
</script>

<template>
    <Head :title="`Invoice #${invoice.invoice_number}`" />

    <div class="flex flex-1 flex-col gap-6 p-6">
        <!-- Actions Header Bar -->
        <div class="flex flex-col justify-between gap-4 border-b border-sidebar-border/60 pb-5 sm:flex-row sm:items-center">
            <div class="flex items-center gap-3">
                <Link href="/invoices">
                    <Button variant="ghost" size="icon" class="size-9">
                        <ArrowLeft class="size-4" />
                    </Button>
                </Link>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-bold tracking-tight">Invoice #{{ invoice.invoice_number }}</h1>
                        <span
                            class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold capitalize"
                            :class="getStatusClass(invoice.status)"
                        >
                            {{ invoice.status }}
                        </span>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Klien: {{ invoice.client?.name }} {{ invoice.client?.company ? `(${invoice.client.company})` : '' }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Mark as Paid -->
                <Button
                    v-if="invoice.status !== 'paid'"
                    class="bg-emerald-600 hover:bg-emerald-500 text-white gap-1.5"
                    @click="openPayModal"
                >
                    <CheckCircle2 class="size-4" />
                    Tandai Lunas (Paid)
                </Button>

                <!-- Download/Preview PDF -->
                <a :href="`/app/invoices/${invoice.id}/pdf`" target="_blank">
                    <Button variant="outline" class="gap-1.5">
                        <Download class="size-4" />
                        Download PDF
                    </Button>
                </a>

                <!-- Edit -->
                <Link :href="editInvoice(invoice.id).url">
                    <Button variant="outline" class="text-amber-500 hover:text-amber-600 gap-1.5">
                        <Pencil class="size-4" />
                        Edit Invoice
                    </Button>
                </Link>

                <!-- Delete -->
                <Button
                    variant="ghost"
                    class="text-rose-500 hover:bg-rose-500/10 hover:text-rose-600 size-9 p-0"
                    title="Hapus"
                    @click="handleDelete"
                >
                    <Trash2 class="size-4" />
                </Button>
            </div>
        </div>

        <!-- Main Layout (Left: Metadata info card, Right: Live preview) -->
        <div class="grid gap-6 lg:grid-cols-3">
            
            <!-- Left Info Panel (1/3 width) -->
            <div class="space-y-6">
                <!-- Metadata Details -->
                <Card class="border-sidebar-border/60">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-semibold uppercase text-muted-foreground">Rincian Invoice</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4 text-sm">
                        <div class="flex items-start gap-3">
                            <User class="size-4 text-muted-foreground mt-0.5" />
                            <div>
                                <div class="font-semibold text-muted-foreground text-xs">Penerbit</div>
                                <div class="font-medium mt-0.5">{{ invoice.user?.name }}</div>
                                <div class="text-xs text-muted-foreground">{{ invoice.user?.email }}</div>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <Building2 class="size-4 text-muted-foreground mt-0.5" />
                            <div>
                                <div class="font-semibold text-muted-foreground text-xs">Ditujukan Kepada</div>
                                <div class="font-medium mt-0.5">{{ invoice.client?.name }}</div>
                                <div v-if="invoice.client?.company" class="text-xs text-muted-foreground">{{ invoice.client.company }}</div>
                                <div class="text-xs text-muted-foreground mt-1">{{ invoice.client?.email }}</div>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <Calendar class="size-4 text-muted-foreground mt-0.5" />
                            <div>
                                <div class="font-semibold text-muted-foreground text-xs">Tanggal Terbit & Tenggat</div>
                                <div class="font-medium mt-0.5">Terbit: {{ invoice.issue_date }}</div>
                                <div class="text-xs text-muted-foreground">Tenggat: {{ invoice.due_date }}</div>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <Coins class="size-4 text-muted-foreground mt-0.5" />
                            <div>
                                <div class="font-semibold text-muted-foreground text-xs">Keuangan & Mata Uang</div>
                                <div class="font-medium mt-0.5">Subtotal: {{ formatCurrency(parseFloat(invoice.subtotal), invoice.currency) }}</div>
                                <div class="text-xs text-muted-foreground">Mata Uang: {{ invoice.currency }}</div>
                                <div v-if="invoice.finance_account" class="text-xs text-emerald-600 mt-1 font-medium flex items-center gap-1">
                                    <CheckCircle2 class="size-3" />
                                    Diterima di: {{ invoice.finance_account.name }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Layout Config Info -->
                <Card class="border-sidebar-border/60">
                    <CardHeader class="pb-3">
                        <CardTitle class="flex items-center gap-1.5 text-sm font-semibold uppercase text-muted-foreground">
                            <Sliders class="size-4" />
                            Kustomisasi Tampilan
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3.5 text-xs">
                        <div class="flex justify-between border-b pb-2">
                            <span class="text-muted-foreground">Template Preset:</span>
                            <span class="font-semibold capitalize">{{ invoice.template_name }}</span>
                        </div>
                        <div class="flex justify-between border-b pb-2 items-center">
                            <span class="text-muted-foreground">Warna Aksen:</span>
                            <span class="flex items-center gap-1.5 font-mono font-semibold">
                                <span class="size-3 rounded-full border border-zinc-200" :style="{ backgroundColor: invoice.color_accent }"></span>
                                {{ invoice.color_accent }}
                            </span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="text-muted-foreground">Font Keluarga:</span>
                            <span class="font-semibold">{{ invoice.font_family }}</span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="text-muted-foreground">Spacing (Kerapatan):</span>
                            <span class="font-semibold capitalize">{{ invoice.spacing }}</span>
                        </div>
                        <div v-if="invoice.brand_prefix" class="flex justify-between border-b pb-2">
                            <span class="text-muted-foreground">Prefix Brand:</span>
                            <span class="font-semibold font-mono">{{ invoice.brand_prefix }}</span>
                        </div>
                        <div v-if="invoice.brand_name" class="flex justify-between">
                            <span class="text-muted-foreground">Nama Brand:</span>
                            <span class="font-semibold">{{ invoice.brand_name }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Right Live Preview (2/3 width) -->
            <div class="lg:col-span-2">
                <Card class="border-sidebar-border/60 overflow-hidden shadow-lg bg-zinc-50 dark:bg-zinc-950 p-6 flex flex-col justify-center items-center min-h-[600px]">
                    <div class="text-center mb-4 text-xs text-muted-foreground flex items-center gap-1.5">
                        <Eye class="size-3.5" />
                        Live Preview (Interaktif)
                    </div>

                    <!-- Simulated Paper Page -->
                    <div
                        id="invoice-preview-paper"
                        class="w-full max-w-[700px] border bg-white text-zinc-900 border-zinc-200 dark:border-zinc-800 shadow rounded-md flex flex-col transition-all duration-300"
                        :class="[
                            getSpacingPaddingClass(invoice.spacing),
                            invoice.font_family === 'Times-Roman' ? 'font-serif' : (invoice.font_family === 'Courier' ? 'font-mono' : 'font-sans')
                        ]"
                        :style="{ borderTopColor: invoice.template_name === 'modern' ? invoice.color_accent : undefined, borderTopWidth: invoice.template_name === 'modern' ? '6px' : '1px' }"
                    >
                        <!-- Header custom text -->
                        <div v-if="invoice.header_text" class="text-[10px] text-zinc-400 whitespace-pre-line leading-relaxed text-center mb-2">
                            {{ invoice.header_text }}
                        </div>

                        <!-- Template 1: Modern Header -->
                        <div v-if="invoice.template_name === 'modern'" class="flex justify-between items-start">
                            <div>
                                <template v-if="invoice.logo_path">
                                    <img
                                        :src="`/storage/${invoice.logo_path}`"
                                        class="max-h-12 max-w-[180px] object-contain mb-1"
                                    />
                                    <div v-if="invoice.brand_name" class="text-xs font-bold text-zinc-600 mt-1">
                                        {{ invoice.brand_name }}
                                    </div>
                                </template>
                                <div v-else class="text-lg font-bold text-zinc-700 tracking-tight">
                                    {{ invoice.brand_name || invoice.user?.name || 'WORKSPACE' }}
                                </div>
                            </div>
                            <div class="text-right">
                                <h2 class="text-3xl font-extrabold tracking-wider uppercase" :style="{ color: invoice.color_accent }">INVOICE</h2>
                                <div class="text-xs font-mono font-semibold text-zinc-500 mt-1">#{{ invoice.invoice_number }}</div>
                                <div class="text-[10px] text-zinc-500 mt-1.5 space-y-0.5">
                                    <div>Terbit: {{ invoice.issue_date }}</div>
                                    <div>Tenggat: {{ invoice.due_date }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Template 2: Classic Header -->
                        <div v-else-if="invoice.template_name === 'classic'" class="text-center border-b-2 border-double border-zinc-900 pb-4">
                            <h2 class="text-3xl tracking-[6px] font-bold text-zinc-900">INVOICE</h2>
                            <div class="text-xs mt-1">Nomor: <strong>#{{ invoice.invoice_number }}</strong></div>
                            <div class="text-[10px] text-zinc-500 mt-1.5 flex items-center justify-center gap-3">
                                <span>Terbit: {{ invoice.issue_date }}</span>
                                <span class="text-zinc-300">|</span>
                                <span>Tenggat: {{ invoice.due_date }}</span>
                            </div>
                        </div>

                        <!-- Template 3: Minimalist Header -->
                        <div v-else class="flex justify-between items-start border-b-2 border-zinc-900 pb-4">
                            <div>
                                <div class="text-sm font-bold text-zinc-900">
                                    {{ invoice.user?.name }}
                                </div>
                                <div v-if="invoice.brand_name" class="text-xs font-semibold text-zinc-700 mt-0.5">{{ invoice.brand_name }}</div>
                                <div class="text-xs text-zinc-500">{{ invoice.user?.email }}</div>
                            </div>
                            <div class="text-right">
                                <h2 class="text-2xl font-bold tracking-widest text-zinc-900">INVOICE</h2>
                                <div class="text-xs mt-1">#{{ invoice.invoice_number }}</div>
                                <div class="text-[10px] text-zinc-500 mt-1.5 space-y-0.5">
                                    <div>Terbit: {{ invoice.issue_date }}</div>
                                    <div>Tenggat: {{ invoice.due_date }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Spacing Divider -->
                        <div class="h-4"></div>

                        <!-- Info Billing Section -->
                        <div class="grid grid-cols-2 gap-6 text-xs border-b border-zinc-100 pb-4" v-if="invoice.template_name !== 'minimalist'">
                            <!-- Ditujukan Kepada (Left Side) -->
                            <div>
                                <div class="text-[10px] uppercase font-bold text-zinc-400 tracking-wider">Ditujukan Kepada</div>
                                <div class="font-bold mt-1 text-zinc-800">{{ invoice.client?.name }}</div>
                                <div v-if="invoice.client?.company" class="text-zinc-600 font-medium mt-0.5">{{ invoice.client.company }}</div>
                                <div class="text-zinc-500 mt-0.5">{{ invoice.client?.email }}</div>
                                <div v-if="invoice.client?.tax_id" class="text-[10px] text-zinc-400 mt-0.5">NPWP/Tax ID: {{ invoice.client.tax_id }}</div>
                                <div v-if="invoice.client?.billing_address" class="text-[10px] text-zinc-600 whitespace-pre-line mt-1.5 leading-relaxed">{{ invoice.client.billing_address }}</div>
                                
                                <!-- Project details -->
                                <div v-if="invoice.project" class="mt-2 text-xs border-t border-zinc-100 pt-1.5">
                                    <span class="text-[9px] uppercase font-bold text-zinc-400 block">Proyek:</span>
                                    <span class="font-semibold text-zinc-700">{{ invoice.project.name }}</span>
                                </div>
                            </div>
                            
                            <!-- Penerbit (Right Side - Text Right) -->
                            <div class="text-right flex flex-col items-end">
                                <div class="text-[10px] uppercase font-bold text-zinc-400 tracking-wider">Penerbit</div>
                                <div class="font-bold mt-1 text-zinc-800">{{ invoice.user?.name }}</div>
                                <div v-if="invoice.brand_name" class="text-xs font-bold text-zinc-700 mt-0.5">{{ invoice.brand_name }}</div>
                                <div class="text-zinc-500 mt-0.5">{{ invoice.user?.email }}</div>
                            </div>
                        </div>

                        <!-- Minimalist Billing Info -->
                        <div class="grid grid-cols-2 gap-6 text-xs border-b border-zinc-900 pb-4" v-else>
                            <!-- Ditujukan Kepada (Left Side) -->
                            <div>
                                <div class="text-[10px] uppercase font-bold text-zinc-400 tracking-wider">Ditujukan Kepada</div>
                                <div class="font-bold mt-1 text-zinc-900">{{ invoice.client?.name }}</div>
                                <div v-if="invoice.client?.company" class="text-zinc-700 font-medium mt-0.5">{{ invoice.client.company }}</div>
                                <div class="text-zinc-600 mt-0.5">{{ invoice.client?.email }}</div>
                                <div v-if="invoice.client?.billing_address" class="text-[10px] text-zinc-600 whitespace-pre-line mt-1.5 leading-relaxed">{{ invoice.client.billing_address }}</div>
                                
                                <!-- Project details -->
                                <div v-if="invoice.project" class="mt-2 text-xs border-t border-zinc-900 pt-1.5">
                                    <span class="text-[9px] uppercase font-bold text-zinc-400 block">Proyek:</span>
                                    <span class="font-semibold text-zinc-700">{{ invoice.project.name }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <!-- Spacing holder -->
                            </div>
                        </div>

                        <div class="h-4" v-if="invoice.template_name !== 'minimalist'"></div>

                        <!-- Items Table preview -->
                        <table class="w-full border-collapse text-xs text-zinc-800">
                            <thead>
                                <tr class="text-left font-bold border-b-2" :style="{ borderBottomColor: invoice.template_name === 'minimalist' ? '#000000' : '#e5e7eb' }">
                                    <th class="pb-2 w-[55%]">Rincian Deskripsi</th>
                                    <th class="pb-2 w-[15%] text-right">QTY</th>
                                    <th class="pb-2 w-[15%] text-right">Harga</th>
                                    <th class="pb-2 w-[15%] text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                <tr v-for="item in invoice.items" :key="item.id">
                                    <td :class="getSpacingItemPaddingClass(invoice.spacing)" class="font-medium align-top">
                                        {{ item.description }}
                                    </td>
                                    <td :class="getSpacingItemPaddingClass(invoice.spacing)" class="text-right align-top">
                                        {{ parseFloat(item.quantity).toFixed(2) }}
                                    </td>
                                    <td :class="getSpacingItemPaddingClass(invoice.spacing)" class="text-right align-top">
                                        {{ formatCurrency(parseFloat(item.unit_price)) }}
                                    </td>
                                    <td :class="getSpacingItemPaddingClass(invoice.spacing)" class="text-right font-medium align-top">
                                        {{ formatCurrency(parseFloat(item.total)) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="h-2"></div>

                        <!-- Totals Section -->
                        <div class="w-[260px] ml-auto text-xs space-y-2 mt-4">
                            <div class="flex justify-between text-zinc-500">
                                <span>Subtotal:</span>
                                <span>{{ formatCurrency(parseFloat(invoice.subtotal)) }}</span>
                            </div>
                            <div v-if="parseFloat(invoice.tax_rate) > 0" class="flex justify-between text-zinc-500">
                                <span>Pajak ({{ parseFloat(invoice.tax_rate) }}%):</span>
                                <span>{{ formatCurrency((parseFloat(invoice.tax_rate) / 100) * parseFloat(invoice.subtotal)) }}</span>
                            </div>
                            <div v-if="parseFloat(invoice.discount_amount) > 0" class="flex justify-between text-zinc-500">
                                <span>Diskon:</span>
                                <span class="text-rose-600">-{{ formatCurrency(invoice.discount_type === 'percentage' ? (parseFloat(invoice.discount_amount) / 100) * parseFloat(invoice.subtotal) : parseFloat(invoice.discount_amount)) }}</span>
                            </div>
                            <div
                                class="flex justify-between font-bold pt-2 border-t-2"
                                :style="{ borderTopColor: invoice.template_name === 'minimalist' ? '#000000' : '#e5e7eb', borderBottomColor: invoice.template_name === 'minimalist' ? '#000000' : 'transparent', borderBottomWidth: invoice.template_name === 'minimalist' ? '2px' : '0px', paddingBottom: invoice.template_name === 'minimalist' ? '4px' : '0px' }"
                            >
                                <span>Total Tagihan:</span>
                                <span :style="{ color: invoice.template_name === 'minimalist' ? '#000000' : invoice.color_accent }">
                                    {{ formatCurrency(parseFloat(invoice.total)) }}
                                </span>
                            </div>
                        </div>

                        <div class="h-4"></div>

                        <!-- Notes & Footer preview -->
                        <div class="border-t border-zinc-100 pt-3 text-[10px] text-zinc-500" :style="{ borderTopColor: invoice.template_name === 'classic' ? '#333333' : '#f3f4f6' }">
                            <!-- Metode Pembayaran Rekening Bank -->
                            <div v-if="invoice.finance_account" class="mb-4">
                                <div class="font-bold text-zinc-700 mb-1">Metode Pembayaran / Rincian Rekening:</div>
                                <div class="p-3 bg-zinc-50 rounded border border-zinc-100/60 max-w-sm flex items-start gap-3">
                                    <div v-if="invoice.finance_account.logo_path" class="size-10 rounded border border-zinc-200 bg-white flex items-center justify-center p-1 shrink-0 shadow-xs">
                                        <img :src="`/storage/${invoice.finance_account.logo_path}`" class="size-full object-contain" />
                                    </div>
                                    <div class="min-w-0 flex-1 text-xs space-y-0.5 text-zinc-600">
                                        <div class="font-bold text-zinc-800 text-[11px]">{{ invoice.finance_account.name }} <span class="text-[9px] text-zinc-400 font-normal uppercase">({{ invoice.finance_account.type }})</span></div>
                                        <div v-if="invoice.finance_account.account_number" class="font-mono text-[10px] text-zinc-800 font-semibold">{{ invoice.finance_account.account_number }}</div>
                                        <div v-if="invoice.finance_account.account_holder" class="text-[9px] text-zinc-500">a.n. {{ invoice.finance_account.account_holder }}</div>
                                        <div v-if="invoice.finance_account.notes" class="text-zinc-500 whitespace-pre-line text-[9px] leading-relaxed mt-1 border-t border-zinc-200/50 pt-1">{{ invoice.finance_account.notes }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Catatan Tambahan -->
                            <div v-if="invoice.notes" class="mb-4">
                                <div class="font-bold text-zinc-700 mb-1">Catatan Tambahan:</div>
                                <div class="whitespace-pre-line leading-relaxed">{{ invoice.notes }}</div>
                            </div>
                            <div v-if="invoice.footer_text" class="text-center font-medium italic mt-2">
                                {{ invoice.footer_text }}
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </div>

        <!-- Pay Invoice Modal Dialog -->
        <Dialog v-model:open="isPayOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Tandai Pembayaran Invoice</DialogTitle>
                    <DialogDescription>
                        Invoice <strong class="font-mono">{{ invoice.invoice_number }}</strong> sebesar
                        <strong>{{ formatCurrency(parseFloat(invoice.total), invoice.currency) }}</strong>
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

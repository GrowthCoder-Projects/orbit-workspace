<script setup lang="ts">
import { Head, Link, useForm, setLayoutProps } from '@inertiajs/vue3';
import {
    Plus,
    Trash2,
    Save,
    ArrowLeft,
    FileText,
    Image,
    Sliders,
    Type,
    X,
    Calendar,
    Coins,
    Building2,
    User,
} from '@lucide/vue';
import { ref, computed, onMounted, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import {
    store as storeInvoice,
    update as updateInvoice,
    index as invoicesIndex,
} from '@/routes/invoices';

const props = defineProps<{
    invoice?: any;
    clients: any[];
    projects: any[];
    accounts: any[];
    nextNumber?: string;
}>();

const isEdit = computed(() => !!props.invoice);

setLayoutProps({
    breadcrumbs: [
        { title: 'Invoices', href: '/app/invoices' },
        { title: isEdit.value ? 'Edit' : 'Buat Baru', href: '#' },
    ],
});

// Form initialization
const form = useForm({
    client_id: '',
    project_id: '',
    invoice_number: '',
    brand_prefix: '',
    brand_name: '',
    status: 'draft',
    issue_date: new Date().toISOString().split('T')[0],
    due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    currency: 'IDR',
    tax_rate: 0,
    discount_amount: 0,
    discount_type: 'fixed',
    template_name: 'modern',
    color_accent: '#3b82f6',
    font_family: 'Helvetica',
    spacing: 'cozy',
    header_text: '',
    footer_text: '',
    notes: '',
    finance_account_id: '',
    logo: null as File | null,
    items: [] as Array<{
        description: string;
        quantity: number;
        unit_price: number;
        tax_rate: number;
        discount_amount: number;
        total: number;
    }>,
    _method: 'POST', // Simulated method for file uploads
});

// Logo preview
const logoPreview = ref<string | null>(null);

onMounted(() => {
    if (isEdit.value && props.invoice) {
        form.client_id = props.invoice.client_id.toString();
        form.project_id = props.invoice.project_id ? props.invoice.project_id.toString() : '';
        form.invoice_number = props.invoice.invoice_number;
        form.brand_prefix = props.invoice.brand_prefix || '';
        form.brand_name = props.invoice.brand_name || '';
        form.status = props.invoice.status;
        form.issue_date = props.invoice.issue_date;
        form.due_date = props.invoice.due_date;
        form.currency = props.invoice.currency;
        form.tax_rate = props.invoice.tax_rate;
        form.discount_amount = props.invoice.discount_amount;
        form.discount_type = props.invoice.discount_type;
        form.template_name = props.invoice.template_name;
        form.color_accent = props.invoice.color_accent;
        form.font_family = props.invoice.font_family;
        form.spacing = props.invoice.spacing;
        form.header_text = props.invoice.header_text || '';
        form.footer_text = props.invoice.footer_text || '';
        form.notes = props.invoice.notes || '';
        form.finance_account_id = props.invoice.finance_account_id ? props.invoice.finance_account_id.toString() : '';
        
        if (props.invoice.logo_path) {
            logoPreview.value = `/storage/${props.invoice.logo_path}`;
        }

        // Map items
        form.items = props.invoice.items.map((item: any) => ({
            description: item.description,
            quantity: parseFloat(item.quantity),
            unit_price: parseFloat(item.unit_price),
            tax_rate: parseFloat(item.tax_rate),
            discount_amount: parseFloat(item.discount_amount),
            total: parseFloat(item.total),
        }));
        
        form._method = 'PATCH'; // PUT/PATCH override for file upload forms
    } else {
        form.invoice_number = props.nextNumber || '';
        addItem(); // add empty item initially
    }
    
    setTimeout(() => {
        isLoaded.value = true;
    }, 50);
});

// Items operations
const addItem = () => {
    form.items.push({
        description: '',
        quantity: 1,
        unit_price: 0,
        tax_rate: 0,
        discount_amount: 0,
        total: 0,
    });
};

const removeItem = (index: number) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    } else {
        toast.warning('Invoice minimal harus memiliki 1 rincian item.');
    }
};

// Logo Upload Handler
const handleLogoUpload = (e: any) => {
    const file = e.target.files[0];

    if (!file) {
return;
}

    form.logo = file;
    
    // Preview
    const reader = new FileReader();
    reader.onload = (event) => {
        logoPreview.value = event.target?.result as string;
    };
    reader.readAsDataURL(file);
};

// Dynamic client-side calculations
const calculateItemTotal = (item: any) => {
    const subtotal = item.quantity * item.unit_price;
    const tax = (item.tax_rate / 100) * subtotal;
    const discount = item.discount_amount;
    item.total = Math.max(0, subtotal + tax - discount);

    return item.total;
};

// Recalculate each item when inputs change
watch(() => form.items, (newItems) => {
    newItems.forEach((item) => {
        calculateItemTotal(item);
    });
}, { deep: true });

// Dynamic invoice number formatting based on Brand Prefix
const isLoaded = ref(false);
const baseInvoiceNumber = ref(isEdit.value && props.invoice ? props.invoice.invoice_number : (props.nextNumber || 'INV-2026-0001'));

watch(() => form.brand_prefix, (newBrand) => {
    if (!isLoaded.value) {
return;
}
    
    const parts = baseInvoiceNumber.value.split('-');

    if (parts.length >= 3) {
        const prefix = parts[0];
        const rest = parts.slice(parts.length - 2).join('-');
        
        if (newBrand.trim()) {
            form.invoice_number = `${prefix}-${newBrand.trim().replace(/\s+/g, '-').toUpperCase()}-${rest}`;
        } else {
            form.invoice_number = `${prefix}-${rest}`;
        }
    } else {
        if (newBrand.trim()) {
            form.invoice_number = `INV-${newBrand.trim().replace(/\s+/g, '-').toUpperCase()}-${baseInvoiceNumber.value.replace('INV-', '')}`;
        } else {
            form.invoice_number = baseInvoiceNumber.value;
        }
    }
});

const totals = computed(() => {
    let subtotal = 0;
    form.items.forEach((item) => {
        subtotal += item.total;
    });

    const tax = (form.tax_rate / 100) * subtotal;
    let discount = 0;

    if (form.discount_type === 'percentage') {
        discount = (form.discount_amount / 100) * subtotal;
    } else {
        discount = form.discount_amount;
    }

    const total = Math.max(0, subtotal + tax - discount);

    return { subtotal, tax, discount, total };
});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: form.currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

const selectedClient = computed(() => {
    return props.clients.find(c => c.id.toString() === form.client_id);
});

const selectedProject = computed(() => {
    return props.projects.find(p => p.id.toString() === form.project_id);
});

const selectedAccount = computed(() => {
    return props.accounts.find(a => a.id.toString() === form.finance_account_id);
});

const showCustomizer = ref(true);

const getSpacingPaddingClass = (spacing: string) => {
    switch (spacing) {
        case 'compact':
            return 'p-6 gap-y-4';
        case 'spacious':
            return 'p-16 gap-y-10';
        default:
            return 'p-10 gap-y-6';
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

const submitForm = () => {
    // Validate client selected
    if (!form.client_id) {
        toast.error('Silakan pilih Klien terlebih dahulu.');

        return;
    }

    // Validate items not empty
    const emptyDesc = form.items.some((item) => !item.description.trim());

    if (emptyDesc) {
        toast.error('Rincian deskripsi item tidak boleh kosong.');

        return;
    }

    const invalidPrice = form.items.some((item) => item.quantity <= 0 || item.unit_price < 0);

    if (invalidPrice) {
        toast.error('Jumlah/QTY harus lebih besar dari 0 dan Harga Satuan tidak boleh negatif.');

        return;
    }

    const url = isEdit.value 
        ? updateInvoice(props.invoice.id).url 
        : storeInvoice().url;

    form.post(url, {
        onSuccess: () => {
            toast.success(isEdit.value ? 'Invoice berhasil diperbarui.' : 'Invoice berhasil dibuat.');
        },
        onError: (errors) => {
            console.error(errors);
            toast.error('Terjadi kesalahan. Periksa kembali inputan Anda.');
        },
    });
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Invoice' : 'Buat Invoice'" />

    <div class="flex flex-1 flex-col gap-6 p-6">
        <!-- Header -->
        <div class="flex flex-col justify-between gap-4 border-b border-sidebar-border/60 pb-5 sm:flex-row sm:items-center">
            <div class="flex items-center gap-3">
                <Link :href="isEdit ? `/invoices/${invoice.id}` : '/invoices'">
                    <Button variant="ghost" size="icon" class="size-9">
                        <ArrowLeft class="size-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">
                        {{ isEdit ? `Edit Invoice #${invoice.invoice_number}` : 'Buat Invoice Baru' }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Sesuaikan tampilan dan isi detail invoice Anda langsung pada kertas kerja di bawah.
                    </p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <Button
                    type="button"
                    class="gap-2 text-white font-medium shadow-xs"
                    :style="{ backgroundColor: form.color_accent }"
                    @click="submitForm"
                    :disabled="form.processing"
                >
                    <Save class="size-4" />
                    Simpan Invoice
                </Button>
            </div>
        </div>

        <!-- Visual Customization Panel (Top Horizontal Card) -->
        <Card class="border-sidebar-border/60 shadow-xs">
            <CardHeader class="pb-3 flex flex-row items-center justify-between">
                <div class="flex items-center gap-2">
                    <Sliders class="size-4 text-muted-foreground" />
                    <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Kustomisasi Tampilan</CardTitle>
                </div>
                <Button variant="ghost" size="sm" class="h-8 text-xs gap-1.5" @click="showCustomizer = !showCustomizer">
                    {{ showCustomizer ? 'Sembunyikan Panel' : 'Tampilkan Panel' }}
                </Button>
            </CardHeader>
            <CardContent v-show="showCustomizer" class="space-y-4 pt-0">
                <!-- Baris 1: Pengaturan Visual -->
                <div class="grid gap-6 sm:grid-cols-4 pb-4 border-b border-sidebar-border/30">
                    <!-- Preset Desain -->
                    <div class="space-y-2 sm:col-span-1">
                        <Label class="text-[11px] font-semibold text-muted-foreground">Preset Desain Template</Label>
                        <div class="grid grid-cols-3 gap-1.5">
                            <button
                                v-for="tpl in ['modern', 'classic', 'minimalist']"
                                :key="tpl"
                                type="button"
                                class="flex flex-col items-center gap-1 rounded-md border py-2 text-center transition-all hover:bg-sidebar-accent/30"
                                :class="[
                                    form.template_name === tpl
                                        ? 'border-primary bg-primary/5 text-primary font-medium'
                                        : 'border-sidebar-border/70 text-muted-foreground',
                                ]"
                                @click="form.template_name = tpl"
                            >
                                <FileText class="size-4" />
                                <span class="text-[10px] capitalize">{{ tpl }}</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Warna Aksen -->
                    <div class="space-y-2 sm:col-span-1">
                        <Label class="text-[11px] font-semibold text-muted-foreground">Warna Aksen Brand</Label>
                        <div class="flex items-center gap-2">
                            <Input
                                type="color"
                                v-model="form.color_accent"
                                class="size-8 p-0 border border-sidebar-border/80 rounded cursor-pointer animate-none"
                            />
                            <Input
                                type="text"
                                v-model="form.color_accent"
                                placeholder="#3b82f6"
                                class="h-8 text-xs font-mono w-24"
                            />
                        </div>
                    </div>
                    
                    <!-- Spacing -->
                    <div class="space-y-2 sm:col-span-1">
                        <Label for="spacing" class="text-[11px] font-semibold text-muted-foreground">Kerapatan Konten</Label>
                        <select
                            id="spacing"
                            v-model="form.spacing"
                            class="flex h-8 w-full rounded-md border border-sidebar-border bg-white dark:bg-zinc-900 px-3 py-1 text-xs shadow-xs focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="compact">Ringkas (Compact)</option>
                            <option value="cozy">Nyaman (Cozy)</option>
                            <option value="spacious">Lebar (Spacious)</option>
                        </select>
                    </div>
                    
                    <!-- Font -->
                    <div class="space-y-2 sm:col-span-1">
                        <Label for="font_family" class="text-[11px] font-semibold text-muted-foreground">Font Keluarga</Label>
                        <select
                            id="font_family"
                            v-model="form.font_family"
                            class="flex h-8 w-full rounded-md border border-sidebar-border bg-white dark:bg-zinc-900 px-3 py-1 text-xs shadow-xs focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="Helvetica">Helvetica (Sans-Serif)</option>
                            <option value="Times-Roman">Times New Roman (Serif)</option>
                            <option value="Courier">Courier (Monospace)</option>
                        </select>
                    </div>
                </div>

                <!-- Baris 2: Pengaturan Konten & Data -->
                <div class="grid gap-6 sm:grid-cols-3 lg:grid-cols-6">
                    <!-- Klien -->
                    <div class="space-y-2 sm:col-span-1">
                        <Label for="client_id" class="text-[11px] font-semibold text-muted-foreground">Pilih Klien *</Label>
                        <select
                            id="client_id"
                            v-model="form.client_id"
                            class="flex h-8 w-full rounded-md border border-sidebar-border bg-white dark:bg-zinc-900 px-3 py-1 text-xs shadow-xs focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="" disabled>Pilih Klien</option>
                            <option
                                v-for="client in clients"
                                :key="client.id"
                                :value="client.id.toString()"
                            >
                                {{ client.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Proyek -->
                    <div class="space-y-2 sm:col-span-1">
                        <Label for="project_id" class="text-[11px] font-semibold text-muted-foreground">Pilih Proyek (Opsional)</Label>
                        <select
                            id="project_id"
                            v-model="form.project_id"
                            class="flex h-8 w-full rounded-md border border-sidebar-border bg-white dark:bg-zinc-900 px-3 py-1 text-xs shadow-xs focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="">Pilih Proyek (Opsional)</option>
                            <option
                                v-for="project in projects"
                                :key="project.id"
                                :value="project.id.toString()"
                            >
                                {{ project.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Metode Pembayaran (Account) -->
                    <div class="space-y-2 sm:col-span-1">
                        <Label for="finance_account_id" class="text-[11px] font-semibold text-muted-foreground">Metode Pembayaran</Label>
                        <select
                            id="finance_account_id"
                            v-model="form.finance_account_id"
                            class="flex h-8 w-full rounded-md border border-sidebar-border bg-white dark:bg-zinc-900 px-3 py-1 text-xs shadow-xs focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="">Pilih Rekening / Cash</option>
                            <option
                                v-for="acc in accounts"
                                :key="acc.id"
                                :value="acc.id.toString()"
                            >
                                {{ acc.name }} ({{ acc.type.toUpperCase() }})
                            </option>
                        </select>
                    </div>

                    <!-- Prefix Brand / No Invoice -->
                    <div class="space-y-2 sm:col-span-1">
                        <Label for="brand_prefix" class="text-[11px] font-semibold text-muted-foreground">Prefix Nomor Invoice</Label>
                        <Input
                            id="brand_prefix"
                            v-model="form.brand_prefix"
                            placeholder="e.g. GR"
                            class="h-8 text-xs font-mono"
                            :disabled="isEdit"
                        />
                    </div>

                    <!-- Mata Uang -->
                    <div class="space-y-2 sm:col-span-1">
                        <Label for="currency" class="text-[11px] font-semibold text-muted-foreground">Mata Uang</Label>
                        <select
                            id="currency"
                            v-model="form.currency"
                            class="flex h-8 w-full rounded-md border border-sidebar-border bg-white dark:bg-zinc-900 px-3 py-1 text-xs shadow-xs focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="IDR">Rupiah (IDR)</option>
                            <option value="USD">Dolar AS (USD)</option>
                            <option value="EUR">Euro (EUR)</option>
                            <option value="SGD">Dolar SGD (SGD)</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="space-y-2 sm:col-span-1">
                        <Label for="status" class="text-[11px] font-semibold text-muted-foreground">Status Invoice</Label>
                        <select
                            id="status"
                            v-model="form.status"
                            :disabled="isEdit && invoice.status === 'paid'"
                            class="flex h-8 w-full rounded-md border border-sidebar-border bg-white dark:bg-zinc-900 px-3 py-1 text-xs shadow-xs focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <option value="draft">Draft</option>
                            <option value="sent">Sent (Tertunggak)</option>
                            <option value="overdue">Overdue (Jatuh Tempo)</option>
                            <option value="paid" v-if="!isEdit">Paid (Lunas)</option>
                        </select>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Interactive WYSIWYG Kertas Invoice A4 -->
        <div class="flex flex-col justify-center items-center py-4 bg-zinc-50 dark:bg-zinc-950/40 rounded-xl border border-dashed border-sidebar-border/80">
            <!-- Simulated A4 Paper -->
            <div
                id="invoice-editor-paper"
                class="w-full max-w-[800px] border bg-white text-zinc-900 border-zinc-200 dark:border-zinc-800 shadow-md rounded-md flex flex-col transition-all duration-300 min-h-[900px] text-xs"
                :class="[
                    getSpacingPaddingClass(form.spacing),
                    form.font_family === 'Times-Roman' ? 'font-serif' : (form.font_family === 'Courier' ? 'font-mono' : 'font-sans')
                ]"
                :style="{ borderTopColor: form.template_name === 'modern' ? form.color_accent : undefined, borderTopWidth: form.template_name === 'modern' ? '6px' : '1px' }"
            >
                <!-- Custom Header Text Area -->
                <div class="w-full text-center mb-2">
                    <textarea
                        v-model="form.header_text"
                        placeholder="Klik di sini untuk menulis teks custom header (misal Alamat Kantor / Kontak)"
                        rows="1"
                        class="bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-zinc-900 border-dashed focus:ring-0 focus:outline-none p-1 text-[10px] text-zinc-400 w-full text-center resize-none placeholder:italic"
                    ></textarea>
                </div>

                <!-- Spacing Divider -->
                <div class="h-2"></div>

                <!-- Template 1: Modern Header -->
                <div v-if="form.template_name === 'modern'" class="flex justify-between items-start">
                    <div>
                        <!-- Logo Upload Box directly on the paper -->
                        <div class="flex flex-col items-start gap-1 relative group/logo-upload">
                            <input
                                type="file"
                                accept="image/*"
                                class="hidden"
                                id="paper-logo-input"
                                @change="handleLogoUpload"
                            />
                            
                            <div v-if="logoPreview" class="relative flex items-center justify-center p-1 bg-white rounded border border-zinc-200 group/logo-container">
                                <img :src="logoPreview" class="max-h-12 max-w-[180px] object-contain" />
                                <Button
                                    type="button"
                                    variant="destructive"
                                    size="icon"
                                    class="absolute -top-1.5 -right-1.5 size-5 rounded-full opacity-0 group-hover/logo-container:opacity-100 transition-opacity"
                                    @click="logoPreview = null; form.logo = null;"
                                >
                                    <X class="size-3" />
                                </Button>
                            </div>
                            <label v-else for="paper-logo-input" class="flex flex-col items-center justify-center border border-dashed border-zinc-300 rounded px-3 py-2 bg-zinc-50 hover:bg-zinc-100 cursor-pointer transition-colors text-zinc-500">
                                <Image class="size-5 text-zinc-400 mb-0.5" />
                                <span class="text-[9px]">Klik unggah logo</span>
                            </label>
                        </div>
                        
                        <!-- Brand Name Input -->
                        <div class="mt-2">
                            <input
                                type="text"
                                v-model="form.brand_name"
                                placeholder="Tulis Nama Brand/Usaha"
                                class="bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-zinc-900 border-dashed focus:ring-0 focus:outline-none p-0.5 font-bold text-xs text-zinc-700 w-48"
                            />
                        </div>
                    </div>
                    <div class="text-right">
                        <h2 class="text-3xl font-extrabold tracking-wider uppercase" :style="{ color: form.color_accent }">INVOICE</h2>
                        <div class="w-48 ml-auto mt-1">
                            <input
                                type="text"
                                v-model="form.invoice_number"
                                placeholder="Nomor Invoice *"
                                class="bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-zinc-900 border-dashed focus:ring-0 focus:outline-none p-0.5 font-mono font-semibold text-right text-xs text-zinc-500 w-full"
                                required
                            />
                        </div>
                        <div class="space-y-1 mt-2 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <span class="text-zinc-400 text-[9px] uppercase font-bold">Terbit:</span>
                                <input
                                    type="date"
                                    v-model="form.issue_date"
                                    class="bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-zinc-900 border-dashed focus:ring-0 focus:outline-none p-0 w-28 text-right text-zinc-700"
                                />
                            </div>
                            <div class="flex items-center justify-end gap-1.5">
                                <span class="text-zinc-400 text-[9px] uppercase font-bold">Tenggat:</span>
                                <input
                                    type="date"
                                    v-model="form.due_date"
                                    class="bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-zinc-900 border-dashed focus:ring-0 focus:outline-none p-0 w-28 text-right text-zinc-700"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Template 2: Classic Header -->
                <div v-else-if="form.template_name === 'classic'" class="text-center border-b-2 border-double border-zinc-900 pb-3">
                    <h2 class="text-3xl tracking-[6px] font-bold text-zinc-900">INVOICE</h2>
                    <div class="w-64 mx-auto mt-1 flex items-center justify-center gap-1">
                        <span class="text-xs">Nomor:</span>
                        <input
                            type="text"
                            v-model="form.invoice_number"
                            placeholder="Nomor Invoice *"
                            class="bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-zinc-900 border-dashed focus:ring-0 focus:outline-none p-0.5 font-bold text-center text-xs text-zinc-800 w-44"
                            required
                        />
                    </div>
                    <div class="flex items-center justify-center gap-3 mt-1.5">
                        <div class="flex items-center gap-1.5">
                            <span class="text-zinc-400 text-[9px] uppercase font-bold">Terbit:</span>
                            <input
                                type="date"
                                v-model="form.issue_date"
                                class="bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-zinc-900 border-dashed focus:ring-0 focus:outline-none p-0 w-28 text-zinc-700"
                            />
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-zinc-400 text-[9px] uppercase font-bold">Tenggat:</span>
                            <input
                                type="date"
                                v-model="form.due_date"
                                class="bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-zinc-900 border-dashed focus:ring-0 focus:outline-none p-0 w-28 text-zinc-700"
                            />
                        </div>
                    </div>
                </div>

                <!-- Template 3: Minimalist Header -->
                <div v-else class="flex justify-between items-start border-b-2 border-zinc-900 pb-3">
                    <div>
                        <div class="text-sm font-bold text-zinc-900">
                            {{ $page.props.auth.user.name }}
                        </div>
                        <div class="text-xs text-zinc-500">{{ $page.props.auth.user.email }}</div>
                        <div class="mt-1">
                            <input
                                type="text"
                                v-model="form.brand_name"
                                placeholder="Tulis Nama Brand/Usaha"
                                class="bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-zinc-900 border-dashed focus:ring-0 focus:outline-none p-0.5 text-xs text-zinc-600 font-semibold w-48"
                            />
                        </div>
                    </div>
                    <div class="text-right">
                        <h2 class="text-2xl font-bold tracking-widest text-zinc-900">INVOICE</h2>
                        <div class="w-48 ml-auto mt-1">
                            <input
                                type="text"
                                v-model="form.invoice_number"
                                placeholder="Nomor Invoice *"
                                class="bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-zinc-900 border-dashed focus:ring-0 focus:outline-none p-0.5 font-mono text-right text-xs text-zinc-800 w-full"
                                required
                            />
                        </div>
                        <div class="space-y-1 mt-2 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <span class="text-zinc-400 text-[9px] uppercase font-bold">Terbit:</span>
                                <input
                                    type="date"
                                    v-model="form.issue_date"
                                    class="bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-zinc-900 border-dashed focus:ring-0 focus:outline-none p-0 w-28 text-right text-zinc-700"
                                />
                            </div>
                            <div class="flex items-center justify-end gap-1.5">
                                <span class="text-zinc-400 text-[9px] uppercase font-bold">Tenggat:</span>
                                <input
                                    type="date"
                                    v-model="form.due_date"
                                    class="bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-zinc-900 border-dashed focus:ring-0 focus:outline-none p-0 w-28 text-right text-zinc-700"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Spacing Divider -->
                <div class="h-4"></div>

                <!-- Info Billing Section (Issuer and Customer) - SWAPPED POSITIONS -->
                <div class="grid grid-cols-2 gap-6 text-xs border-b border-zinc-100 pb-4" v-if="form.template_name !== 'minimalist'">
                    <!-- Ditujukan Kepada (Left Side) -->
                    <div>
                        <div class="text-[10px] uppercase font-bold text-zinc-400 tracking-wider">Ditujukan Kepada</div>
                        <div v-if="selectedClient" class="mt-1 space-y-0.5">
                            <div class="font-bold text-zinc-800">{{ selectedClient.name }}</div>
                            <div v-if="selectedClient.company" class="text-zinc-600 font-medium">{{ selectedClient.company }}</div>
                            <div class="text-zinc-500">{{ selectedClient.email }}</div>
                            <div v-if="selectedClient.tax_id" class="text-[10px] text-zinc-400">NPWP/Tax ID: {{ selectedClient.tax_id }}</div>
                            <div v-if="selectedClient.billing_address" class="text-[10px] text-zinc-500 whitespace-pre-line mt-1.5 leading-relaxed">{{ selectedClient.billing_address }}</div>
                        </div>
                        <div v-else class="text-zinc-400 italic mt-1.5">Pilih klien di panel kustomisasi...</div>
                        
                        <!-- Project Detail -->
                        <div v-if="selectedProject" class="mt-2 text-xs border-t border-zinc-100 pt-1.5">
                            <span class="text-[9px] uppercase font-bold text-zinc-400 block">Proyek:</span>
                            <span class="font-semibold text-zinc-700">{{ selectedProject.name }}</span>
                        </div>
                    </div>

                    <!-- Penerbit (Right Side - Text Right) -->
                    <div class="text-right flex flex-col items-end">
                        <div class="text-[10px] uppercase font-bold text-zinc-400 tracking-wider">Penerbit</div>
                        <div class="font-bold mt-1 text-zinc-800">{{ $page.props.auth.user.name }}</div>
                        <div class="text-zinc-500">{{ $page.props.auth.user.email }}</div>
                        
                        <!-- brand name on-paper input aligned right -->
                        <div class="mt-1 flex justify-end">
                            <input
                                type="text"
                                v-model="form.brand_name"
                                placeholder="Tulis Nama Brand/Usaha"
                                class="bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-zinc-900 border-dashed focus:ring-0 focus:outline-none p-0.5 text-xs text-zinc-700 font-bold w-48 text-right"
                            />
                        </div>
                    </div>
                </div>

                <!-- Minimalist Billing Info -->
                <div class="grid grid-cols-2 gap-6 text-xs border-b border-zinc-900 pb-4" v-else>
                    <!-- Ditujukan Kepada (Left Side) -->
                    <div>
                        <div class="text-[10px] uppercase font-bold text-zinc-400 tracking-wider">Ditujukan Kepada</div>
                        <div v-if="selectedClient" class="mt-1 space-y-0.5">
                            <div class="font-bold text-zinc-800">{{ selectedClient.name }}</div>
                            <div v-if="selectedClient.company" class="text-zinc-600 font-medium">{{ selectedClient.company }}</div>
                            <div class="text-zinc-500">{{ selectedClient.email }}</div>
                            <div v-if="selectedClient.tax_id" class="text-[10px] text-zinc-400">NPWP/Tax ID: {{ selectedClient.tax_id }}</div>
                            <div v-if="selectedClient.billing_address" class="text-[10px] text-zinc-500 whitespace-pre-line mt-1.5 leading-relaxed">{{ selectedClient.billing_address }}</div>
                        </div>
                        <div v-else class="text-zinc-400 italic mt-1.5">Pilih klien di panel kustomisasi...</div>
                        
                        <!-- Project Detail -->
                        <div v-if="selectedProject" class="mt-2 text-xs border-t border-zinc-900 pt-1.5">
                            <span class="text-[9px] uppercase font-bold text-zinc-400 block">Proyek:</span>
                            <span class="font-semibold text-zinc-700">{{ selectedProject.name }}</span>
                        </div>
                    </div>
                </div>

                <!-- Spacing Divider -->
                <div class="h-2"></div>

                <!-- Line Items Table -->
                <div class="mt-4">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-zinc-300" :style="{ borderBottomColor: form.color_accent }">
                                <th class="font-bold text-zinc-700 pb-2">RINCIAN DESKRIPSI</th>
                                <th class="font-bold text-zinc-700 pb-2 text-center w-20">QTY</th>
                                <th class="font-bold text-zinc-700 pb-2 text-right w-36">HARGA SATUAN</th>
                                <th class="font-bold text-zinc-700 pb-2 text-right w-36">TOTAL</th>
                                <th class="pb-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in form.items" :key="index" class="border-b border-zinc-100 group/row-item">
                                <td class="align-top" :class="getSpacingItemPaddingClass(form.spacing)">
                                    <input
                                        type="text"
                                        v-model="item.description"
                                        placeholder="e.g. Jasa Desain UI/UX & Prototipe"
                                        class="bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-zinc-900 border-dashed focus:ring-0 focus:outline-none p-0.5 w-full text-xs text-zinc-800"
                                        required
                                    />
                                </td>
                                <td class="align-top text-center w-20" :class="getSpacingItemPaddingClass(form.spacing)">
                                    <input
                                        type="number"
                                        v-model.number="item.quantity"
                                        class="bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-zinc-900 border-dashed focus:ring-0 focus:outline-none p-0.5 w-full text-xs text-center text-zinc-800"
                                        min="0.01"
                                        step="any"
                                        required
                                    />
                                </td>
                                <td class="align-top text-right w-36" :class="getSpacingItemPaddingClass(form.spacing)">
                                    <input
                                        type="number"
                                        v-model.number="item.unit_price"
                                        class="bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-zinc-900 border-dashed focus:ring-0 focus:outline-none p-0.5 w-full text-xs text-right text-zinc-800"
                                        min="0"
                                        step="any"
                                        required
                                    />
                                </td>
                                <td class="align-top text-right font-medium w-36" :class="getSpacingItemPaddingClass(form.spacing)">
                                    {{ formatCurrency(item.total) }}
                                </td>
                                <td class="align-middle text-center w-10">
                                    <Button
                                        v-if="form.items.length > 1"
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="size-6 rounded-full text-rose-500 hover:text-rose-600 hover:bg-rose-50 opacity-0 group-hover/row-item:opacity-100 transition-opacity"
                                        @click="removeItem(index)"
                                    >
                                        <Trash2 class="size-3" />
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <!-- Add Item Button -->
                    <div class="mt-3">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="text-xs h-7 gap-1 shadow-xs border-dashed"
                            @click="addItem"
                        >
                            <Plus class="size-3" />
                            Tambah Rincian Item
                        </Button>
                    </div>
                </div>

                <!-- Spacing Divider -->
                <div class="h-4"></div>

                <!-- Summary Section (Notes, Tax, Discount, Total) -->
                <div class="grid grid-cols-2 gap-8 text-xs pt-4 border-t border-zinc-200">
                    <div>
                        <!-- Metode Pembayaran Rekening Bank -->
                        <div class="mb-4">
                            <div class="text-[9px] uppercase font-bold text-zinc-400 tracking-wider">Metode Pembayaran</div>
                            <div v-if="selectedAccount" class="mt-1 p-3 bg-zinc-50 dark:bg-zinc-900 rounded border border-zinc-100 dark:border-zinc-800 flex items-start gap-3">
                                <div v-if="selectedAccount.logo_path" class="size-10 rounded border border-zinc-200 dark:border-zinc-800 bg-white flex items-center justify-center p-1 shrink-0 shadow-xs">
                                    <img :src="`/storage/${selectedAccount.logo_path}`" class="size-full object-contain" />
                                </div>
                                <div class="min-w-0 flex-1 text-xs space-y-0.5 text-zinc-700">
                                    <div class="font-bold text-zinc-800">{{ selectedAccount.name }} <span class="text-[9px] text-zinc-400 font-normal uppercase">({{ selectedAccount.type }})</span></div>
                                    <div v-if="selectedAccount.account_number" class="font-mono text-[11px] text-zinc-800 font-semibold mt-0.5">{{ selectedAccount.account_number }}</div>
                                    <div v-if="selectedAccount.account_holder" class="text-[10px] text-zinc-500">a.n. {{ selectedAccount.account_holder }}</div>
                                    <div v-if="selectedAccount.notes" class="text-zinc-500 whitespace-pre-line text-[10px] leading-relaxed mt-1 border-t border-zinc-200/50 pt-1">{{ selectedAccount.notes }}</div>
                                </div>
                            </div>
                            <div v-else class="text-zinc-400 italic mt-1 bg-zinc-50 dark:bg-zinc-900 p-2 rounded border border-dashed border-zinc-200">
                                Pilih metode pembayaran di panel kustomisasi...
                            </div>
                        </div>

                        <!-- Catatan Tambahan -->
                        <div>
                            <div class="text-[9px] uppercase font-bold text-zinc-400 tracking-wider">Catatan Tambahan (Opsional)</div>
                            <textarea
                                v-model="form.notes"
                                placeholder="Tulis catatan atau instruksi tambahan di sini..."
                                class="bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-zinc-900 border-dashed focus:ring-0 focus:outline-none p-1 mt-1 w-full min-h-[50px] text-xs text-zinc-600 leading-relaxed placeholder:italic"
                            ></textarea>
                        </div>
                    </div>
                    
                    <div class="flex flex-col justify-end space-y-2">
                        <div class="flex justify-between border-b border-zinc-100 pb-1">
                            <span class="text-zinc-500 font-medium">Subtotal:</span>
                            <span class="font-bold text-zinc-800">{{ formatCurrency(totals.subtotal) }}</span>
                        </div>
                        
                        <!-- Tax Editable -->
                        <div class="flex justify-between items-center border-b border-zinc-100 pb-1">
                            <div class="flex items-center gap-1">
                                <span class="text-zinc-500 font-medium">Pajak:</span>
                                <input
                                    type="number"
                                    v-model.number="form.tax_rate"
                                    class="bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-zinc-900 border-dashed focus:ring-0 focus:outline-none p-0 w-8 text-center text-zinc-700 transition-colors"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                />
                                <span class="text-zinc-500">%</span>
                            </div>
                            <span class="font-bold text-zinc-800">{{ formatCurrency(totals.tax) }}</span>
                        </div>
                        
                        <!-- Discount Editable -->
                        <div class="flex justify-between items-center border-b border-zinc-100 pb-1">
                            <div class="flex items-center gap-1">
                                <span class="text-zinc-500 font-medium">Diskon:</span>
                                <input
                                    type="number"
                                    v-model.number="form.discount_amount"
                                    class="bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-zinc-900 border-dashed focus:ring-0 focus:outline-none p-0 w-16 text-right text-zinc-700 transition-colors"
                                    min="0"
                                    step="0.01"
                                />
                                <select
                                    v-model="form.discount_type"
                                    class="bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-zinc-900 border-dashed focus:ring-0 focus:outline-none p-0 text-zinc-500 transition-colors cursor-pointer appearance-none text-right font-medium"
                                >
                                    <option value="fixed">IDR</option>
                                    <option value="percentage">%</option>
                                </select>
                            </div>
                            <span class="font-bold text-rose-500">-{{ formatCurrency(totals.discount) }}</span>
                        </div>
                        
                        <!-- Grand Total -->
                        <div class="flex justify-between pt-1 border-t border-zinc-300 text-sm font-bold">
                            <span class="text-zinc-900">Total Tagihan:</span>
                            <span :style="{ color: form.color_accent }">{{ formatCurrency(totals.total) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Spacing Divider -->
                <div class="h-8"></div>

                <!-- Custom Footer Text Area -->
                <div class="w-full text-center mt-auto border-t border-zinc-100 pt-3">
                    <textarea
                        v-model="form.footer_text"
                        placeholder="Klik di sini untuk menulis teks custom footer (misal Terimakasih atas kerjasamanya / Syarat & Ketentuan)"
                        rows="1"
                        class="bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-zinc-900 border-dashed focus:ring-0 focus:outline-none p-1 text-[10px] text-zinc-400 w-full text-center resize-none placeholder:italic"
                    ></textarea>
                </div>
            </div>
        </div>
    </div>
</template>

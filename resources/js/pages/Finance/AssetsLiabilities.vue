<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    Plus,
    Pencil,
    Trash2,
    ShieldAlert,
    TrendingUp,
    Briefcase,
    Building2,
    Calendar,
    Receipt,
    Check,
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
    index as assetsIndex,
    store as storeAsset,
    update as updateAsset,
    destroy as destroyAsset,
} from '@/routes/assets';
import {
    store as storeLiability,
    update as updateLiability,
    destroy as destroyLiability,
} from '@/routes/liabilities';

const { confirm } = useConfirm();

const props = defineProps<{
    assets: any[];
    liabilities: any[];
    totalAssets: number;
    totalLiabilities: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Finance Dashboard',
                href: '/app/finance',
            },
            {
                title: 'Aset & Utang',
                href: assetsIndex().url,
            },
        ],
    },
});

// Assets form & state
const isAssetModalOpen = ref(false);
const editingAsset = ref<any>(null);
const assetForm = useForm({
    name: '',
    category: 'property',
    purchase_value: 0.0,
    current_value: 0.0,
    purchase_date: '',
    depreciation_rate: 0.0,
    notes: '',
});

// Liabilities form & state
const isLiabilityModalOpen = ref(false);
const editingLiability = ref<any>(null);
const liabilityForm = useForm({
    name: '',
    type: 'loan',
    total_amount: 0.0,
    remaining_amount: 0.0,
    interest_rate: 0.0,
    due_date: '',
    notes: '',
});

// Formats
const formatIDR = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

// Asset CRUD actions
const openAssetModal = (asset?: any) => {
    if (asset) {
        editingAsset.value = asset;
        assetForm.name = asset.name;
        assetForm.category = asset.category;
        assetForm.purchase_value = parseFloat(asset.purchase_value);
        assetForm.current_value = parseFloat(asset.current_value);
        assetForm.purchase_date = asset.purchase_date
            ? asset.purchase_date.substring(0, 10)
            : '';
        assetForm.depreciation_rate = parseFloat(asset.depreciation_rate);
        assetForm.notes = asset.notes || '';
    } else {
        editingAsset.value = null;
        assetForm.reset();
        assetForm.category = 'property';
    }

    isAssetModalOpen.value = true;
};

const saveAsset = () => {
    if (editingAsset.value) {
        assetForm.put(updateAsset(editingAsset.value.id).url, {
            onSuccess: () => {
                isAssetModalOpen.value = false;
                editingAsset.value = null;
            },
        });
    } else {
        assetForm.post(storeAsset().url, {
            onSuccess: () => {
                isAssetModalOpen.value = false;
            },
        });
    }
};

const deleteAsset = async (id: number) => {
    const isConfirmed = await confirm({
        title: 'Hapus Aset',
        message: 'Apakah Anda yakin ingin menghapus catatan aset ini?',
        confirmText: 'Hapus',
        cancelText: 'Batal',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(destroyAsset(id).url);
    }
};

// Liability CRUD actions
const openLiabilityModal = (lia?: any) => {
    if (lia) {
        editingLiability.value = lia;
        liabilityForm.name = lia.name;
        liabilityForm.type = lia.type;
        liabilityForm.total_amount = parseFloat(lia.total_amount);
        liabilityForm.remaining_amount = parseFloat(lia.remaining_amount);
        liabilityForm.interest_rate = parseFloat(lia.interest_rate);
        liabilityForm.due_date = lia.due_date
            ? lia.due_date.substring(0, 10)
            : '';
        liabilityForm.notes = lia.notes || '';
    } else {
        editingLiability.value = null;
        liabilityForm.reset();
        liabilityForm.type = 'loan';
    }

    isLiabilityModalOpen.value = true;
};

const saveLiability = () => {
    if (editingLiability.value) {
        liabilityForm.put(updateLiability(editingLiability.value.id).url, {
            onSuccess: () => {
                isLiabilityModalOpen.value = false;
                editingLiability.value = null;
            },
        });
    } else {
        liabilityForm.post(storeLiability().url, {
            onSuccess: () => {
                isLiabilityModalOpen.value = false;
            },
        });
    }
};

const deleteLiability = async (id: number) => {
    const isConfirmed = await confirm({
        title: 'Hapus Kewajiban',
        message: 'Apakah Anda yakin ingin menghapus catatan kewajiban/utang ini?',
        confirmText: 'Hapus',
        cancelText: 'Batal',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(destroyLiability(id).url);
    }
};

const getAssetCategoryName = (cat: string) => {
    const cats: Record<string, string> = {
        property: 'Properti / Tanah',
        vehicle: 'Kendaraan Bermotor',
        electronics: 'Elektronik / Gadget',
        other: 'Aset Fisik Lain',
    };

    return cats[cat] || cat;
};

const getLiabilityTypeName = (type: string) => {
    const types: Record<string, string> = {
        loan: 'Pinjaman / Utang Bank',
        credit_card: 'Kartu Kredit',
        paylater: 'PayLater',
        installment: 'Cicilan Kredit',
    };

    return types[type] || type;
};
</script>

<template>
    <Head title="Aset & Kewajiban" />

    <div class="space-y-6 px-6 py-6">
        <!-- Header -->
        <div class="space-y-1 border-b border-border pb-5">
            <h2
                class="font-outfit flex items-center gap-2 text-2xl font-bold tracking-tight"
            >
                <Building2 class="size-6 text-emerald-500" />
                Aset Fisik & Kewajiban / Utang
            </h2>
            <p class="text-sm text-muted-foreground">
                Pencatatan aset fisik bernilai tinggi serta kewajiban
                cicilan/utang berjalan untuk menghitung kekayaan bersih Anda.
            </p>
        </div>

        <!-- Net Worth Card -->
        <Card class="relative overflow-hidden shadow-sm">
            <div
                class="absolute top-0 right-0 h-48 w-48 rounded-full bg-emerald-500/5 blur-3xl"
            ></div>
            <CardHeader class="pb-2">
                <CardTitle
                    class="font-outfit flex items-center gap-1.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    <TrendingUp class="size-4 text-emerald-400" />
                    Kekayaan Bersih Fisik (Net Worth)
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    class="font-mono text-3xl font-extrabold tracking-tight text-foreground"
                >
                    {{ formatIDR(totalAssets - totalLiabilities) }}
                </div>
                <div
                    class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 font-mono text-xs text-muted-foreground"
                >
                    <div>
                        Total Aset Fisik:
                        <span class="font-bold text-emerald-400">{{
                            formatIDR(totalAssets)
                        }}</span>
                    </div>
                    <div class="text-muted-foreground">|</div>
                    <div>
                        Total Kewajiban/Utang:
                        <span class="font-bold text-rose-400">{{
                            formatIDR(totalLiabilities)
                        }}</span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- SPLIT LAYOUT -->
        <div class="grid gap-8 lg:grid-cols-2">
            <!-- LEFT COLUMN: Physical Assets -->
            <div
                class="space-y-6 rounded-2xl border border-border/60 bg-card/30 p-6 shadow-2xs backdrop-blur-xs"
            >
                <div
                    class="flex items-center justify-between border-b border-border/40 pb-4"
                >
                    <div class="space-y-0.5">
                        <h3
                            class="font-outfit flex items-center gap-2 text-lg font-bold text-foreground"
                        >
                            <Briefcase class="size-5 text-emerald-500" />
                            Daftar Aset Fisik
                        </h3>
                        <p class="text-xs text-muted-foreground">
                            Daftar barang berharga dan kepemilikan properti
                            fisik Anda.
                        </p>
                    </div>
                    <Button
                        size="sm"
                        variant="outline"
                        @click="openAssetModal()"
                        class="shadow-3xs border-border font-medium hover:bg-muted"
                    >
                        <Plus class="mr-1.5 size-4" />
                        Tambah Aset
                    </Button>
                </div>

                <div
                    v-if="assets.length === 0"
                    class="rounded-xl border border-dashed border-border/70 bg-muted/10 p-10 text-center"
                >
                    <Briefcase
                        class="mx-auto mb-3 size-10 text-muted-foreground/60"
                    />
                    <p
                        class="font-outfit text-sm font-semibold text-foreground/80"
                    >
                        Belum ada aset fisik
                    </p>
                    <p class="font-inter mt-1 text-xs text-muted-foreground">
                        Mulai daftarkan aset fisik berharga Anda dengan klik
                        tombol di atas.
                    </p>
                </div>

                <div v-else class="space-y-4">
                    <Card
                        v-for="a in assets"
                        :key="a.id"
                        class="group relative overflow-hidden shadow-xs transition-all duration-300 hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-md"
                    >
                        <CardHeader class="pb-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="rounded-lg bg-emerald-500/10 p-2.5 text-emerald-400 transition-transform group-hover:scale-105"
                                    >
                                        <Briefcase class="size-5" />
                                    </div>
                                    <div>
                                        <CardTitle
                                            class="font-outfit text-base font-bold text-foreground"
                                            >{{ a.name }}</CardTitle
                                        >
                                        <CardDescription
                                            class="font-inter mt-0.5 text-xs text-muted-foreground"
                                        >
                                            {{
                                                getAssetCategoryName(a.category)
                                            }}
                                        </CardDescription>
                                    </div>
                                </div>

                                <div
                                    class="flex items-center gap-1 opacity-0 transition duration-150 group-hover:opacity-100"
                                >
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-7 hover:bg-muted"
                                        @click="openAssetModal(a)"
                                    >
                                        <Pencil
                                            class="size-3.5 text-muted-foreground"
                                        />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-7 hover:bg-muted"
                                        @click="deleteAsset(a.id)"
                                    >
                                        <Trash2
                                            class="size-3.5 text-rose-400"
                                        />
                                    </Button>
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-3 font-mono">
                            <div class="grid grid-cols-2 gap-4 text-xs">
                                <div>
                                    <span class="block text-muted-foreground"
                                        >Nilai Beli</span
                                    >
                                    <span
                                        class="font-bold text-foreground/80"
                                        >{{ formatIDR(a.purchase_value) }}</span
                                    >
                                </div>
                                <div>
                                    <span class="block text-muted-foreground"
                                        >Nilai Sekarang</span
                                    >
                                    <span class="font-bold text-emerald-400">{{
                                        formatIDR(a.current_value)
                                    }}</span>
                                </div>
                            </div>

                            <div
                                class="font-inter flex flex-wrap items-center gap-x-4 gap-y-1 pt-1 text-xs text-muted-foreground"
                            >
                                <span class="flex items-center gap-1">
                                    <Calendar class="size-3.5" />
                                    {{
                                        new Date(
                                            a.purchase_date,
                                        ).toLocaleDateString('id-ID', {
                                            year: 'numeric',
                                            month: 'short',
                                            day: 'numeric',
                                        })
                                    }}
                                </span>
                                <span>•</span>
                                <span
                                    >Depresiasi:
                                    {{ a.depreciation_rate }}%/tahun</span
                                >
                            </div>

                            <p
                                v-if="a.notes"
                                class="font-inter rounded-md border border-border/50 bg-muted/30 p-2.5 text-xs text-muted-foreground"
                            >
                                {{ a.notes }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- RIGHT COLUMN: Liabilities & Debts -->
            <div
                class="space-y-6 rounded-2xl border border-border/60 bg-card/30 p-6 shadow-2xs backdrop-blur-xs"
            >
                <div
                    class="flex items-center justify-between border-b border-border/40 pb-4"
                >
                    <div class="space-y-0.5">
                        <h3
                            class="font-outfit flex items-center gap-2 text-lg font-bold text-foreground"
                        >
                            <Receipt class="size-5 text-rose-500" />
                            Daftar Utang & Kewajiban
                        </h3>
                        <p class="text-xs text-muted-foreground">
                            Rincian kewajiban keuangan, hutang berjalan, dan
                            cicilan Anda.
                        </p>
                    </div>
                    <Button
                        size="sm"
                        variant="outline"
                        @click="openLiabilityModal()"
                        class="shadow-3xs border-border font-medium hover:bg-muted"
                    >
                        <Plus class="mr-1.5 size-4" />
                        Tambah Utang
                    </Button>
                </div>

                <div
                    v-if="liabilities.length === 0"
                    class="rounded-xl border border-dashed border-border/70 bg-muted/10 p-10 text-center"
                >
                    <Receipt
                        class="mx-auto mb-3 size-10 text-muted-foreground/60"
                    />
                    <p
                        class="font-outfit text-sm font-semibold text-foreground/80"
                    >
                        Belum ada utang & kewajiban
                    </p>
                    <p class="font-inter mt-1 text-xs text-muted-foreground">
                        Mulai catat kewajiban keuangan atau cicilan Anda dengan
                        klik tombol di atas.
                    </p>
                </div>

                <div v-else class="space-y-4">
                    <Card
                        v-for="l in liabilities"
                        :key="l.id"
                        class="group relative overflow-hidden shadow-xs transition-all duration-300 hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-md"
                    >
                        <CardHeader class="pb-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="rounded-lg bg-rose-500/10 p-2.5 text-rose-400 transition-transform group-hover:scale-105"
                                    >
                                        <Receipt class="size-5" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <CardTitle
                                                class="font-outfit text-base font-bold text-foreground"
                                                >{{ l.name }}</CardTitle
                                            >
                                            <span
                                                v-if="l.remaining_amount <= 0"
                                                class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-500"
                                            >
                                                <Check class="size-3" /> Lunas
                                            </span>
                                        </div>
                                        <CardDescription
                                            class="font-inter mt-0.5 text-xs text-muted-foreground"
                                        >
                                            {{ getLiabilityTypeName(l.type) }}
                                        </CardDescription>
                                    </div>
                                </div>

                                <div
                                    class="flex items-center gap-1 opacity-0 transition duration-150 group-hover:opacity-100"
                                >
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-7 hover:bg-muted"
                                        @click="openLiabilityModal(l)"
                                    >
                                        <Pencil
                                            class="size-3.5 text-muted-foreground"
                                        />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-7 hover:bg-muted"
                                        @click="deleteLiability(l.id)"
                                    >
                                        <Trash2
                                            class="size-3.5 text-rose-400"
                                        />
                                    </Button>
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-3 font-mono">
                            <div class="grid grid-cols-2 gap-4 text-xs">
                                <div>
                                    <span class="block text-muted-foreground"
                                        >Total Utang Awal</span
                                    >
                                    <span
                                        class="font-bold text-foreground/80"
                                        >{{ formatIDR(l.total_amount) }}</span
                                    >
                                </div>
                                <div>
                                    <span class="block text-muted-foreground"
                                        >Sisa Hutang</span
                                    >
                                    <span class="font-bold text-rose-400">{{
                                        formatIDR(l.remaining_amount)
                                    }}</span>
                                </div>
                            </div>

                            <div
                                class="font-inter flex flex-wrap items-center gap-x-4 gap-y-1 pt-1 text-xs text-muted-foreground"
                            >
                                <span
                                    v-if="l.due_date"
                                    class="flex items-center gap-1"
                                >
                                    <Calendar class="size-3.5" />
                                    Jatuh Tempo:
                                    {{
                                        new Date(l.due_date).toLocaleDateString(
                                            'id-ID',
                                            {
                                                year: 'numeric',
                                                month: 'short',
                                                day: 'numeric',
                                            },
                                        )
                                    }}
                                </span>
                                <span v-if="l.due_date">•</span>
                                <span>Bunga: {{ l.interest_rate }}%/tahun</span>
                            </div>

                            <p
                                v-if="l.notes"
                                class="font-inter rounded-md border border-border/50 bg-muted/30 p-2.5 text-xs text-muted-foreground"
                            >
                                {{ l.notes }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- RECORD/EDIT ASSET MODAL -->
        <Dialog
            :open="isAssetModalOpen"
            @update:open="isAssetModalOpen = $event"
        >
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="font-outfit"
                        >{{ editingAsset ? 'Ubah' : 'Catat' }} Aset
                        Fisik</DialogTitle
                    >
                    <DialogDescription class="font-inter text-muted-foreground">
                        Masukkan rincian aset fisik Anda untuk menaksir valuasi
                        kekayaan bersih.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="saveAsset" class="space-y-4 py-2">
                    <div class="space-y-2">
                        <Label for="asset-name" class="text-foreground/80"
                            >Nama Aset / Barang</Label
                        >
                        <Input
                            id="asset-name"
                            v-model="assetForm.name"
                            class=""
                            placeholder="Misal: Rumah BSD, Honda Vario 2024"
                            required
                        />
                    </div>

                    <div class="space-y-2">
                        <Label class="text-foreground/80">Kategori Aset</Label>
                        <Select v-model="assetForm.category" required>
                            <SelectTrigger class="">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent class="">
                                <SelectItem value="property"
                                    >Properti / Tanah</SelectItem
                                >
                                <SelectItem value="vehicle"
                                    >Kendaraan Bermotor</SelectItem
                                >
                                <SelectItem value="electronics"
                                    >Elektronik / Gadget</SelectItem
                                >
                                <SelectItem value="other"
                                    >Aset Fisik Lain</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label
                                for="asset-buy-val"
                                class="text-foreground/80"
                                >Nilai Pembelian (IDR)</Label
                            >
                            <Input
                                id="asset-buy-val"
                                type="number"
                                step="0.01"
                                v-model="assetForm.purchase_value"
                                class="font-mono text-foreground"
                                required
                            />
                        </div>
                        <div class="space-y-2">
                            <Label
                                for="asset-curr-val"
                                class="text-foreground/80"
                                >Taksiran Nilai Sekarang</Label
                            >
                            <Input
                                id="asset-curr-val"
                                type="number"
                                step="0.01"
                                v-model="assetForm.current_value"
                                class="font-mono text-foreground"
                                required
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="asset-date" class="text-foreground/80"
                                >Tanggal Pembelian</Label
                            >
                            <Input
                                id="asset-date"
                                type="date"
                                v-model="assetForm.purchase_date"
                                class=""
                                required
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="asset-dep" class="text-foreground/80"
                                >Penyusutan per Tahun (%)</Label
                            >
                            <Input
                                id="asset-dep"
                                type="number"
                                step="0.01"
                                v-model="assetForm.depreciation_rate"
                                class="font-mono text-foreground"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="asset-notes" class="text-foreground/80"
                            >Catatan</Label
                        >
                        <textarea
                            id="asset-notes"
                            v-model="assetForm.notes"
                            class="flex min-h-[80px] w-full rounded-md border px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-hidden disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="Keterangan kondisi barang..."
                        />
                    </div>

                    <DialogFooter class="pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isAssetModalOpen = false"
                            class="hover:bg-muted"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            class="bg-emerald-600 text-foreground hover:bg-emerald-700"
                            :disabled="assetForm.processing"
                            >Simpan Aset</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- RECORD/EDIT LIABILITY MODAL -->
        <Dialog
            :open="isLiabilityModalOpen"
            @update:open="isLiabilityModalOpen = $event"
        >
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="font-outfit"
                        >{{ editingLiability ? 'Ubah' : 'Catat' }} Kewajiban /
                        Utang</DialogTitle
                    >
                    <DialogDescription class="font-inter text-muted-foreground">
                        Masukkan rincian nominal utang atau kredit berjalan
                        Anda.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="saveLiability" class="space-y-4 py-2">
                    <div class="space-y-2">
                        <Label for="lia-name" class="text-foreground/80"
                            >Nama Kredit / Kewajiban</Label
                        >
                        <Input
                            id="lia-name"
                            v-model="liabilityForm.name"
                            class=""
                            placeholder="Misal: KPR Bank Mandiri, Cicilan Shopee PayLater"
                            required
                        />
                    </div>

                    <div class="space-y-2">
                        <Label class="text-foreground/80"
                            >Jenis Kewajiban</Label
                        >
                        <Select v-model="liabilityForm.type" required>
                            <SelectTrigger class="">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent class="">
                                <SelectItem value="loan"
                                    >Pinjaman / Utang Bank</SelectItem
                                >
                                <SelectItem value="credit_card"
                                    >Kartu Kredit</SelectItem
                                >
                                <SelectItem value="paylater"
                                    >PayLater</SelectItem
                                >
                                <SelectItem value="installment"
                                    >Cicilan Kredit</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="lia-total" class="text-foreground/80"
                                >Jumlah Utang Awal (IDR)</Label
                            >
                            <Input
                                id="lia-total"
                                type="number"
                                step="0.01"
                                v-model="liabilityForm.total_amount"
                                class="font-mono text-foreground"
                                required
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="lia-rem" class="text-foreground/80"
                                >Sisa Utang Saat Ini</Label
                            >
                            <Input
                                id="lia-rem"
                                type="number"
                                step="0.01"
                                v-model="liabilityForm.remaining_amount"
                                class="font-mono text-foreground"
                                required
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="lia-rate" class="text-foreground/80"
                                >Suku Bunga per Tahun (%)</Label
                            >
                            <Input
                                id="lia-rate"
                                type="number"
                                step="0.01"
                                v-model="liabilityForm.interest_rate"
                                class="font-mono text-foreground"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="lia-date" class="text-foreground/80"
                                >Tanggal Jatuh Tempo</Label
                            >
                            <Input
                                id="lia-date"
                                type="date"
                                v-model="liabilityForm.due_date"
                                class=""
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="lia-notes" class="text-foreground/80"
                            >Catatan</Label
                        >
                        <textarea
                            id="lia-notes"
                            v-model="liabilityForm.notes"
                            class="flex min-h-[80px] w-full rounded-md border px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-hidden disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="Keterangan opsional..."
                        />
                    </div>

                    <DialogFooter class="pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isLiabilityModalOpen = false"
                            class="hover:bg-muted"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            class="bg-rose-600 text-foreground hover:bg-rose-700"
                            :disabled="liabilityForm.processing"
                            >Simpan Kewajiban</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>

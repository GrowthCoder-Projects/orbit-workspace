<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    Plus,
    Pencil,
    Trash2,
    TrendingUp,
    TrendingDown,
    BadgeDollarSign,
    LineChart,
    Wallet,
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
    index as investmentsIndex,
    store as storeInvestment,
    update as updateInvestment,
    destroy as destroyInvestment,
} from '@/routes/investments';

const { confirm } = useConfirm();

const props = defineProps<{
    investments: any[];
    accounts: any[];
    totalPortfolioValue: number;
    totalProfitLoss: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Finance Dashboard',
                href: '/app/finance',
            },
            {
                title: 'Portofolio Investasi',
                href: investmentsIndex().url,
            },
        ],
    },
});

const isModalOpen = ref(false);
const editingInvestment = ref<any>(null);

const form = useForm({
    account_id: '',
    name: '',
    type: 'stock',
    shares_quantity: 0.0,
    average_buy_price: 0.0,
    current_price: 0.0,
    currency: 'IDR',
});

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

const formatIDR = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

const openInvestmentModal = (inv?: any) => {
    if (inv) {
        editingInvestment.value = inv;
        form.account_id = inv.account_id.toString();
        form.name = inv.name;
        form.type = inv.type;
        form.shares_quantity = parseFloat(inv.shares_quantity);
        form.average_buy_price = parseFloat(inv.average_buy_price);
        form.current_price = parseFloat(inv.current_price);
        form.currency = inv.currency;
    } else {
        editingInvestment.value = null;
        form.reset();
        form.type = 'stock';
        form.currency = 'IDR';

        if (props.accounts.length > 0) {
            form.account_id = props.accounts[0].id.toString();
        }
    }

    isModalOpen.value = true;
};

const handleSave = () => {
    if (editingInvestment.value) {
        form.put(updateInvestment(editingInvestment.value.id).url, {
            onSuccess: () => {
                isModalOpen.value = false;
                editingInvestment.value = null;
            },
        });
    } else {
        form.post(storeInvestment().url, {
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    }
};

const handleDelete = async (id: number) => {
    const isConfirmed = await confirm({
        title: 'Hapus Investasi',
        message: 'Apakah Anda yakin ingin menghapus catatan investasi ini?',
        confirmText: 'Hapus',
        cancelText: 'Batal',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(destroyInvestment(id).url);
    }
};

const getTypeName = (type: string) => {
    const types: Record<string, string> = {
        stock: 'Saham',
        mutual_fund: 'Reksa Dana',
        gold: 'Emas',
        crypto: 'Kripto',
        bond: 'Obligasi',
        deposit: 'Deposito Bunga',
    };

    return types[type] || type;
};
</script>

<template>
    <Head title="Portofolio Investasi" />

    <div class="space-y-6 px-6 py-6">
        <!-- Header -->
        <div
            class="flex flex-col justify-between gap-4 border-b border-border pb-5 md:flex-row md:items-center"
        >
            <div class="space-y-1">
                <h2
                    class="font-outfit flex items-center gap-2 text-2xl font-bold tracking-tight"
                >
                    <LineChart class="size-6 text-indigo-400" />
                    Portofolio Investasi
                </h2>
                <p class="text-sm text-muted-foreground">
                    Pantau kinerja investasi Anda (saham, reksa dana, kripto,
                    emas) beserta perhitungan keuntungan berjalan.
                </p>
            </div>

            <Button
                size="sm"
                @click="openInvestmentModal()"
                class="flex items-center gap-1.5 bg-indigo-600 font-medium text-white shadow-xs hover:bg-indigo-700"
            >
                <Plus class="size-4" />
                Catat Investasi
            </Button>
        </div>

        <!-- Portfolio Cards Summary -->
        <div class="grid gap-6 md:grid-cols-2">
            <Card class="shadow-sm">
                <CardHeader class="pb-2">
                    <CardTitle
                        class="font-outfit flex items-center gap-1.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        <Wallet class="size-4 text-indigo-400" />
                        Total Nilai Portofolio
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        class="font-mono text-3xl font-extrabold tracking-tight text-foreground"
                    >
                        {{ formatIDR(totalPortfolioValue) }}
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Estimasi nilai pasar saat ini dikonversi ke IDR.
                    </p>
                </CardContent>
            </Card>

            <Card class="shadow-sm">
                <CardHeader class="pb-2">
                    <CardTitle
                        class="font-outfit flex items-center gap-1.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        <BadgeDollarSign
                            class="size-4"
                            :class="
                                totalProfitLoss >= 0
                                    ? 'text-emerald-400'
                                    : 'text-rose-400'
                            "
                        />
                        Total Profit / Loss Berjalan
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        class="flex items-center gap-2 font-mono text-3xl font-extrabold tracking-tight"
                        :class="
                            totalProfitLoss >= 0
                                ? 'text-emerald-500'
                                : 'text-rose-500'
                        "
                    >
                        <TrendingUp
                            v-if="totalProfitLoss >= 0"
                            class="size-7"
                        />
                        <TrendingDown v-else class="size-7" />
                        {{ totalProfitLoss >= 0 ? '+' : ''
                        }}{{ formatIDR(totalProfitLoss) }}
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Selisih nilai beli awal vs nilai pasar saat ini.
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Investment Ledger Table -->
        <Card class="overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-foreground/80">
                    <thead
                        class="/60 border-b text-xs text-muted-foreground uppercase"
                    >
                        <tr>
                            <th scope="col" class="px-6 py-4">
                                Instrumen Aset
                            </th>
                            <th scope="col" class="px-6 py-4">Tipe</th>
                            <th scope="col" class="px-6 py-4">Rekening</th>
                            <th scope="col" class="px-6 py-4 text-right">
                                Jumlah Unit
                            </th>
                            <th scope="col" class="px-6 py-4 text-right">
                                Harga Beli Rata2
                            </th>
                            <th scope="col" class="px-6 py-4 text-right">
                                Harga Pasar
                            </th>
                            <th scope="col" class="px-6 py-4 text-right">
                                Nilai Total
                            </th>
                            <th scope="col" class="px-6 py-4 text-right">
                                P/L (ROI)
                            </th>
                            <th scope="col" class="px-6 py-4 text-center">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border font-mono">
                        <tr v-if="investments.length === 0">
                            <td
                                colspan="9"
                                class="font-inter px-6 py-8 text-center text-muted-foreground"
                            >
                                Belum ada aset investasi terdaftar. Klik "Catat
                                Investasi" untuk memulai.
                            </td>
                        </tr>
                        <tr
                            v-for="inv in investments"
                            :key="inv.id"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <th
                                scope="row"
                                class="font-outfit px-6 py-4 font-bold font-normal text-foreground"
                            >
                                {{ inv.name }}
                            </th>
                            <td
                                class="font-inter px-6 py-4 text-xs text-muted-foreground"
                            >
                                {{ getTypeName(inv.type) }}
                            </td>
                            <td
                                class="font-inter px-6 py-4 text-xs text-muted-foreground"
                            >
                                {{ inv.account_name }}
                            </td>
                            <td class="px-6 py-4 text-right font-medium">
                                {{ inv.shares_quantity }}
                            </td>
                            <td
                                class="px-6 py-4 text-right text-muted-foreground"
                            >
                                {{
                                    formatCurrency(
                                        inv.average_buy_price,
                                        inv.currency,
                                    )
                                }}
                            </td>
                            <td class="px-6 py-4 text-right text-foreground">
                                {{
                                    formatCurrency(
                                        inv.current_price,
                                        inv.currency,
                                    )
                                }}
                            </td>
                            <td
                                class="px-6 py-4 text-right font-bold text-foreground"
                            >
                                {{
                                    formatCurrency(
                                        inv.current_value,
                                        inv.currency,
                                    )
                                }}
                            </td>
                            <td
                                class="px-6 py-4 text-right font-bold"
                                :class="
                                    inv.profit_loss >= 0
                                        ? 'text-emerald-500'
                                        : 'text-rose-500'
                                "
                            >
                                {{ inv.profit_loss >= 0 ? '+' : ''
                                }}{{
                                    formatCurrency(
                                        inv.profit_loss,
                                        inv.currency,
                                    )
                                }}
                                <span class="font-inter text-xs font-normal">
                                    ({{ inv.roi.toFixed(2) }}%)</span
                                >
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div
                                    class="flex items-center justify-center gap-1.5"
                                >
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-7 hover:bg-muted"
                                        @click="openInvestmentModal(inv)"
                                    >
                                        <Pencil
                                            class="size-3.5 text-muted-foreground"
                                        />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-7 hover:bg-muted"
                                        @click="handleDelete(inv.id)"
                                    >
                                        <Trash2
                                            class="size-3.5 text-rose-400"
                                        />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>

        <!-- RECORD/EDIT INVESTMENT MODAL -->
        <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="font-outfit"
                        >{{ editingInvestment ? 'Ubah' : 'Catat' }} Aset
                        Investasi</DialogTitle
                    >
                    <DialogDescription class="font-inter text-muted-foreground">
                        Pilih broker/wallet penyimpan, tipe instrumen, jumlah
                        lembar/gram, dan harga rata-rata beli.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="handleSave" class="space-y-4 py-2">
                    <div class="space-y-2">
                        <Label class="text-foreground/80"
                            >Rekening Wadah / Broker</Label
                        >
                        <Select v-model="form.account_id" required>
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Pilih Rekening" />
                            </SelectTrigger>
                            <SelectContent class="">
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

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="inv-name" class="text-foreground/80"
                                >Nama Aset / Kode Ticker</Label
                            >
                            <Input
                                id="inv-name"
                                v-model="form.name"
                                class=""
                                placeholder="Misal: BBCA, BTC, Logam Mulia"
                                required
                            />
                        </div>
                        <div class="space-y-2">
                            <Label class="text-foreground/80">Tipe Aset</Label>
                            <Select v-model="form.type" required>
                                <SelectTrigger class="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent class="">
                                    <SelectItem value="stock">Saham</SelectItem>
                                    <SelectItem value="mutual_fund"
                                        >Reksa Dana</SelectItem
                                    >
                                    <SelectItem value="gold">Emas</SelectItem>
                                    <SelectItem value="crypto"
                                        >Kripto</SelectItem
                                    >
                                    <SelectItem value="bond"
                                        >Obligasi</SelectItem
                                    >
                                    <SelectItem value="deposit"
                                        >Deposito Berbunga</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2 space-y-2">
                            <Label for="inv-quantity" class="text-foreground/80"
                                >Jumlah Unit / Kuantitas</Label
                            >
                            <Input
                                id="inv-quantity"
                                type="number"
                                step="0.000001"
                                v-model="form.shares_quantity"
                                class="font-mono text-foreground"
                                required
                            />
                        </div>
                        <div class="space-y-2">
                            <Label class="text-foreground/80">Mata Uang</Label>
                            <Select v-model="form.currency">
                                <SelectTrigger class="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent class="">
                                    <SelectItem value="IDR">IDR</SelectItem>
                                    <SelectItem value="USD">USD</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label
                                for="inv-buy-price"
                                class="text-foreground/80"
                                >Harga Beli Rata-Rata</Label
                            >
                            <Input
                                id="inv-buy-price"
                                type="number"
                                step="0.01"
                                v-model="form.average_buy_price"
                                class="font-mono text-foreground"
                                required
                            />
                        </div>
                        <div class="space-y-2">
                            <Label
                                for="inv-current-price"
                                class="text-foreground/80"
                                >Harga Pasar Sekarang</Label
                            >
                            <Input
                                id="inv-current-price"
                                type="number"
                                step="0.01"
                                v-model="form.current_price"
                                class="font-mono text-foreground"
                                required
                            />
                        </div>
                    </div>

                    <DialogFooter class="pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isModalOpen = false"
                            class="font-medium hover:bg-muted"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            class="bg-indigo-600 font-semibold text-white shadow-xs hover:bg-indigo-700"
                            :disabled="form.processing"
                            >Simpan Aset</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>

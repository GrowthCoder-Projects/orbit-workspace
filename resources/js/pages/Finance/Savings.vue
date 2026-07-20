<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    Plus,
    Pencil,
    Trash2,
    Calendar,
    Target,
    PiggyBank,
    Compass,
    TrendingUp,
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
    store as storeGoal,
    update as updateGoal,
    destroy as destroyGoal,
} from '@/routes/goals';
import {
    index as savingsIndex,
    store as storeSaving,
    update as updateSaving,
    destroy as destroySaving,
} from '@/routes/savings';

const { confirm } = useConfirm();

const props = defineProps<{
    savings: any[];
    goals: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Finance Dashboard',
                href: '/app/finance',
            },
            {
                title: 'Tabungan & Target',
                href: savingsIndex().url,
            },
        ],
    },
});

// Savings form & state
const isSavingModalOpen = ref(false);
const editingSaving = ref<any>(null);
const savingForm = useForm({
    name: '',
    target_amount: 0,
    current_amount: 0,
    target_date: '',
    currency: 'IDR',
    color: '#10b981',
    notes: '',
});

// Goals form & state
const isGoalModalOpen = ref(false);
const editingGoal = ref<any>(null);
const goalForm = useForm({
    title: '',
    target_amount: 0,
    current_amount: 0,
    deadline: '',
    notes: '',
});

// Formats
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

const getProgressPercent = (current: number, target: number) => {
    if (target <= 0) {
return 0;
}

    return Math.round((current / target) * 100);
};

// Savings modal actions
const openSavingModal = (saving?: any) => {
    if (saving) {
        editingSaving.value = saving;
        savingForm.name = saving.name;
        savingForm.target_amount = parseFloat(saving.target_amount);
        savingForm.current_amount = parseFloat(saving.current_amount);
        savingForm.target_date = saving.target_date
            ? saving.target_date.substring(0, 10)
            : '';
        savingForm.currency = saving.currency;
        savingForm.color = saving.color || '#10b981';
        savingForm.notes = saving.notes || '';
    } else {
        editingSaving.value = null;
        savingForm.reset();
        savingForm.currency = 'IDR';
        savingForm.color = '#10b981';
    }

    isSavingModalOpen.value = true;
};

const saveSaving = () => {
    if (editingSaving.value) {
        savingForm.put(updateSaving(editingSaving.value.id).url, {
            onSuccess: () => {
                isSavingModalOpen.value = false;
                editingSaving.value = null;
            },
        });
    } else {
        savingForm.post(storeSaving().url, {
            onSuccess: () => {
                isSavingModalOpen.value = false;
            },
        });
    }
};

const deleteSaving = async (id: number) => {
    const isConfirmed = await confirm({
        title: 'Hapus Target Tabungan',
        message: 'Apakah Anda yakin ingin menghapus target tabungan ini?',
        confirmText: 'Hapus',
        cancelText: 'Batal',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(destroySaving(id).url);
    }
};

// Goals modal actions
const openGoalModal = (goal?: any) => {
    if (goal) {
        editingGoal.value = goal;
        goalForm.title = goal.title;
        goalForm.target_amount = parseFloat(goal.target_amount);
        goalForm.current_amount = parseFloat(goal.current_amount);
        goalForm.deadline = goal.deadline ? goal.deadline.substring(0, 10) : '';
        goalForm.notes = goal.notes || '';
    } else {
        editingGoal.value = null;
        goalForm.reset();
    }

    isGoalModalOpen.value = true;
};

const saveGoal = () => {
    if (editingGoal.value) {
        goalForm.put(updateGoal(editingGoal.value.id).url, {
            onSuccess: () => {
                isGoalModalOpen.value = false;
                editingGoal.value = null;
            },
        });
    } else {
        goalForm.post(storeGoal().url, {
            onSuccess: () => {
                isGoalModalOpen.value = false;
            },
        });
    }
};

const deleteGoal = async (id: number) => {
    const isConfirmed = await confirm({
        title: 'Hapus Target Finansial',
        message: 'Apakah Anda yakin ingin menghapus target finansial ini?',
        confirmText: 'Hapus',
        cancelText: 'Batal',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(destroyGoal(id).url);
    }
};
</script>

<template>
    <Head title="Tabungan & Target Finansial" />

    <div class="space-y-6 px-6 py-6">
        <!-- Header -->
        <div class="space-y-1 border-b border-border pb-5">
            <h2
                class="font-outfit flex items-center gap-2 text-2xl font-bold tracking-tight"
            >
                <Target class="size-6 text-emerald-500" />
                Target Tabungan & Sasaran Finansial
            </h2>
            <p class="text-sm text-muted-foreground">
                Kelola tabungan berjangka khusus dan rencana sasaran finansial
                jangka panjang Anda.
            </p>
        </div>

        <div class="grid gap-8 lg:grid-cols-2">
            <!-- LEFT COLUMN: Savings Targets -->
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
                            <PiggyBank class="size-5 text-emerald-500" />
                            Target Tabungan Khusus
                        </h3>
                        <p class="text-xs text-muted-foreground">
                            Alokasi target tabungan spesifik untuk keperluan
                            masa depan.
                        </p>
                    </div>
                    <Button
                        size="sm"
                        variant="outline"
                        @click="openSavingModal()"
                        class="shadow-3xs border-border font-medium hover:bg-muted"
                    >
                        <Plus class="mr-1.5 size-4" />
                        Tambah Target
                    </Button>
                </div>

                <div
                    v-if="savings.length === 0"
                    class="rounded-xl border border-dashed border-border/70 bg-muted/10 p-10 text-center"
                >
                    <PiggyBank
                        class="mx-auto mb-3 size-10 text-muted-foreground/60"
                    />
                    <p
                        class="font-outfit text-sm font-semibold text-foreground/80"
                    >
                        Belum ada target tabungan
                    </p>
                    <p class="font-inter mt-1 text-xs text-muted-foreground">
                        Mulai rancang target tabungan Anda dengan klik tombol di
                        atas.
                    </p>
                </div>

                <div v-else class="space-y-4">
                    <Card
                        v-for="s in savings"
                        :key="s.id"
                        class="group relative overflow-hidden shadow-xs transition-all duration-300 hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-md"
                    >
                        <CardHeader class="pb-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="rounded-lg p-2.5 transition-transform group-hover:scale-105"
                                        :style="{
                                            backgroundColor: s.color + '15',
                                            color: s.color,
                                        }"
                                    >
                                        <PiggyBank class="size-5" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <CardTitle
                                                class="font-outfit text-base font-bold text-foreground"
                                                >{{ s.name }}</CardTitle
                                            >
                                            <span
                                                v-if="
                                                    s.current_amount >=
                                                    s.target_amount
                                                "
                                                class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-500"
                                            >
                                                <Check class="size-3" />
                                                Terpenuhi
                                            </span>
                                        </div>
                                        <CardDescription
                                            v-if="s.target_date"
                                            class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"
                                        >
                                            <Calendar class="size-3" />
                                            Target:
                                            {{
                                                new Date(
                                                    s.target_date,
                                                ).toLocaleDateString('id-ID', {
                                                    year: 'numeric',
                                                    month: 'short',
                                                    day: 'numeric',
                                                })
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
                                        @click="openSavingModal(s)"
                                    >
                                        <Pencil
                                            class="size-3.5 text-muted-foreground"
                                        />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-7 hover:bg-muted"
                                        @click="deleteSaving(s.id)"
                                    >
                                        <Trash2
                                            class="size-3.5 text-rose-400"
                                        />
                                    </Button>
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-3">
                            <div
                                class="flex items-end justify-between font-mono text-xs"
                            >
                                <div class="text-muted-foreground">
                                    <span class="font-bold text-foreground">{{
                                        formatCurrency(
                                            s.current_amount,
                                            s.currency,
                                        )
                                    }}</span>
                                    <span class="text-muted-foreground">
                                        /
                                    </span>
                                    <span>{{
                                        formatCurrency(
                                            s.target_amount,
                                            s.currency,
                                        )
                                    }}</span>
                                </div>
                                <div class="font-bold text-emerald-500">
                                    {{
                                        getProgressPercent(
                                            s.current_amount,
                                            s.target_amount,
                                        )
                                    }}%
                                </div>
                            </div>

                            <div
                                class="h-2 w-full overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full rounded-full transition-all duration-300"
                                    :style="{
                                        width:
                                            Math.min(
                                                getProgressPercent(
                                                    s.current_amount,
                                                    s.target_amount,
                                                ),
                                                100,
                                            ) + '%',
                                        backgroundColor: s.color,
                                    }"
                                ></div>
                            </div>

                            <div class="mt-1 flex items-center justify-between">
                                <div
                                    v-if="s.target_amount > s.current_amount"
                                    class="text-[10px] font-medium text-muted-foreground"
                                >
                                    Kekurangan:
                                    <span
                                        class="font-mono font-bold text-foreground/80"
                                        >{{
                                            formatCurrency(
                                                s.target_amount -
                                                    s.current_amount,
                                                s.currency,
                                            )
                                        }}</span
                                    >
                                </div>
                                <div
                                    v-else
                                    class="flex items-center gap-0.5 text-[10px] font-bold text-emerald-500"
                                >
                                    <Check class="size-3" /> Target tercapai!
                                </div>
                            </div>

                            <p
                                v-if="s.notes"
                                class="font-inter line-clamp-2 rounded-md border border-border/50 bg-muted/30 p-2.5 text-xs text-muted-foreground"
                            >
                                {{ s.notes }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- RIGHT COLUMN: Financial Goals -->
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
                            <Compass class="size-5 text-indigo-500" />
                            Sasaran Jangka Panjang
                        </h3>
                        <p class="text-xs text-muted-foreground">
                            Target finansial jangka panjang untuk pencapaian
                            strategis.
                        </p>
                    </div>
                    <Button
                        size="sm"
                        variant="outline"
                        @click="openGoalModal()"
                        class="shadow-3xs border-border font-medium hover:bg-muted"
                    >
                        <Plus class="mr-1.5 size-4" />
                        Tambah Sasaran
                    </Button>
                </div>

                <div
                    v-if="goals.length === 0"
                    class="rounded-xl border border-dashed border-border/70 bg-muted/10 p-10 text-center"
                >
                    <Compass
                        class="mx-auto mb-3 size-10 text-muted-foreground/60"
                    />
                    <p
                        class="font-outfit text-sm font-semibold text-foreground/80"
                    >
                        Belum ada sasaran jangka panjang
                    </p>
                    <p class="font-inter mt-1 text-xs text-muted-foreground">
                        Mulai rencanakan sasaran finansial masa depan Anda
                        sekarang.
                    </p>
                </div>

                <div v-else class="space-y-4">
                    <Card
                        v-for="g in goals"
                        :key="g.id"
                        class="group relative overflow-hidden shadow-xs transition-all duration-300 hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-md"
                    >
                        <CardHeader class="pb-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="rounded-lg bg-indigo-500/10 p-2.5 text-indigo-400 transition-transform group-hover:scale-105"
                                    >
                                        <Compass class="size-5" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <CardTitle
                                                class="font-outfit text-base font-bold text-foreground"
                                                >{{ g.title }}</CardTitle
                                            >
                                            <span
                                                v-if="
                                                    g.current_amount >=
                                                    g.target_amount
                                                "
                                                class="inline-flex items-center gap-1 rounded-full bg-indigo-500/10 px-2 py-0.5 text-[10px] font-semibold text-indigo-400"
                                            >
                                                <Check class="size-3" />
                                                Terpenuhi
                                            </span>
                                        </div>
                                        <CardDescription
                                            v-if="g.deadline"
                                            class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"
                                        >
                                            <Calendar class="size-3" />
                                            Deadline:
                                            {{
                                                new Date(
                                                    g.deadline,
                                                ).toLocaleDateString('id-ID', {
                                                    year: 'numeric',
                                                    month: 'short',
                                                    day: 'numeric',
                                                })
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
                                        @click="openGoalModal(g)"
                                    >
                                        <Pencil
                                            class="size-3.5 text-muted-foreground"
                                        />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-7 hover:bg-muted"
                                        @click="deleteGoal(g.id)"
                                    >
                                        <Trash2
                                            class="size-3.5 text-rose-400"
                                        />
                                    </Button>
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-3">
                            <div
                                class="flex items-end justify-between font-mono text-xs"
                            >
                                <div class="text-muted-foreground">
                                    <span class="font-bold text-foreground">{{
                                        formatCurrency(g.current_amount, 'IDR')
                                    }}</span>
                                    <span class="text-muted-foreground">
                                        /
                                    </span>
                                    <span>{{
                                        formatCurrency(g.target_amount, 'IDR')
                                    }}</span>
                                </div>
                                <div class="font-bold text-indigo-400">
                                    {{
                                        getProgressPercent(
                                            g.current_amount,
                                            g.target_amount,
                                        )
                                    }}%
                                </div>
                            </div>

                            <div
                                class="h-2 w-full overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full rounded-full bg-indigo-500 transition-all duration-300"
                                    :style="{
                                        width:
                                            Math.min(
                                                getProgressPercent(
                                                    g.current_amount,
                                                    g.target_amount,
                                                ),
                                                100,
                                            ) + '%',
                                    }"
                                ></div>
                            </div>

                            <div class="mt-1 flex items-center justify-between">
                                <div
                                    v-if="g.target_amount > g.current_amount"
                                    class="text-[10px] font-medium text-muted-foreground"
                                >
                                    Kekurangan:
                                    <span
                                        class="font-mono font-bold text-foreground/80"
                                        >{{
                                            formatCurrency(
                                                g.target_amount -
                                                    g.current_amount,
                                                'IDR',
                                            )
                                        }}</span
                                    >
                                </div>
                                <div
                                    v-else
                                    class="flex items-center gap-0.5 text-[10px] font-bold text-indigo-400"
                                >
                                    <Check class="size-3" /> Target tercapai!
                                </div>
                            </div>

                            <p
                                v-if="g.notes"
                                class="font-inter line-clamp-2 rounded-md border border-border/50 bg-muted/30 p-2.5 text-xs text-muted-foreground"
                            >
                                {{ g.notes }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- TARGET TABUNGAN MODAL -->
        <Dialog
            :open="isSavingModalOpen"
            @update:open="isSavingModalOpen = $event"
        >
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="font-outfit"
                        >{{ editingSaving ? 'Ubah' : 'Tambah' }} Target
                        Tabungan</DialogTitle
                    >
                    <DialogDescription class="font-inter text-muted-foreground">
                        Atur nama, jumlah target, mata uang, dan tanggal jatuh
                        tempo rencana tabungan khusus Anda.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="saveSaving" class="space-y-4 py-2">
                    <div class="space-y-2">
                        <Label for="saving-name" class="text-foreground/80"
                            >Nama Tabungan</Label
                        >
                        <Input
                            id="saving-name"
                            v-model="savingForm.name"
                            class=""
                            placeholder="Misal: Dana Darurat, Uang Liburan"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2 space-y-2">
                            <Label
                                for="saving-target"
                                class="text-foreground/80"
                                >Target Nominal</Label
                            >
                            <Input
                                id="saving-target"
                                type="number"
                                step="0.01"
                                v-model="savingForm.target_amount"
                                class="font-mono text-foreground"
                                required
                            />
                        </div>
                        <div class="space-y-2">
                            <Label class="text-foreground/80">Mata Uang</Label>
                            <Select v-model="savingForm.currency">
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
                                for="saving-current"
                                class="text-foreground/80"
                                >Saldo Saat Ini</Label
                            >
                            <Input
                                id="saving-current"
                                type="number"
                                step="0.01"
                                v-model="savingForm.current_amount"
                                class="font-mono text-foreground"
                                required
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="saving-date" class="text-foreground/80"
                                >Target Tanggal</Label
                            >
                            <Input
                                id="saving-date"
                                type="date"
                                v-model="savingForm.target_date"
                                class=""
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="saving-color" class="text-foreground/80"
                            >Warna Penanda Kartu</Label
                        >
                        <div
                            class="flex items-center gap-3 rounded-lg border border-input bg-muted/10 p-2.5"
                        >
                            <input
                                id="saving-color"
                                type="color"
                                v-model="savingForm.color"
                                class="h-8 w-12 cursor-pointer rounded border border-border bg-transparent p-0"
                            />
                            <span class="text-xs text-muted-foreground">
                                Klik kotak untuk memilih warna kustom penanda
                                visual kartu tabungan Anda.
                            </span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="saving-notes" class="text-foreground/80"
                            >Catatan</Label
                        >
                        <textarea
                            id="saving-notes"
                            v-model="savingForm.notes"
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm text-foreground transition-[border-color,box-shadow] placeholder:text-muted-foreground/50 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="Keterangan opsional..."
                        />
                    </div>

                    <DialogFooter class="pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isSavingModalOpen = false"
                            class="font-medium hover:bg-muted"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            class="bg-emerald-600 font-semibold text-white shadow-xs hover:bg-emerald-700"
                            :disabled="savingForm.processing"
                            >Simpan Target</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- FINANCIAL GOAL MODAL -->
        <Dialog :open="isGoalModalOpen" @update:open="isGoalModalOpen = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="font-outfit"
                        >{{ editingGoal ? 'Ubah' : 'Tambah' }} Sasaran
                        Finansial</DialogTitle
                    >
                    <DialogDescription class="font-inter text-muted-foreground">
                        Atur sasaran jangka panjang seperti kepemilikan aset
                        berharga, target dana pensiun, dll.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="saveGoal" class="space-y-4 py-2">
                    <div class="space-y-2">
                        <Label for="goal-title" class="text-foreground/80"
                            >Nama Sasaran Finansial</Label
                        >
                        <Input
                            id="goal-title"
                            v-model="goalForm.title"
                            class=""
                            placeholder="Misal: Dana Pensiun, Uang DP Rumah"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="goal-target" class="text-foreground/80"
                                >Target Nominal (IDR)</Label
                            >
                            <Input
                                id="goal-target"
                                type="number"
                                step="0.01"
                                v-model="goalForm.target_amount"
                                class="font-mono text-foreground"
                                required
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="goal-current" class="text-foreground/80"
                                >Terkumpul Saat Ini</Label
                            >
                            <Input
                                id="goal-current"
                                type="number"
                                step="0.01"
                                v-model="goalForm.current_amount"
                                class="font-mono text-foreground"
                                required
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="goal-deadline" class="text-foreground/80"
                            >Deadline Target</Label
                        >
                        <Input
                            id="goal-deadline"
                            type="date"
                            v-model="goalForm.deadline"
                            class=""
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="goal-notes" class="text-foreground/80"
                            >Catatan</Label
                        >
                        <textarea
                            id="goal-notes"
                            v-model="goalForm.notes"
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm text-foreground transition-[border-color,box-shadow] placeholder:text-muted-foreground/50 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="Keterangan taktik pencapaian..."
                        />
                    </div>

                    <DialogFooter class="pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isGoalModalOpen = false"
                            class="font-medium hover:bg-muted"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            class="bg-indigo-600 font-semibold text-white shadow-xs hover:bg-indigo-700"
                            :disabled="goalForm.processing"
                            >Simpan Sasaran</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import * as LucideIcons from '@lucide/vue';
import {
    Tag,
    Plus,
    Pencil,
    Trash2,
    HelpCircle,
    ArrowUpRight,
    ArrowDownRight,
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
    index as categoriesIndex,
    store as storeCategory,
    update as updateCategory,
    destroy as destroyCategory,
} from '@/routes/categories';

const { confirm } = useConfirm();

const props = defineProps<{
    categories: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Finance Dashboard',
                href: '/app/finance',
            },
            {
                title: 'Kategori',
                href: categoriesIndex().url,
            },
        ],
    },
});

// Modal States
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const selectedCategory = ref<any>(null);

// Forms
const createForm = useForm({
    name: '',
    type: 'expense',
    icon: 'HelpCircle',
    color: '#3b82f6',
});

const editForm = useForm({
    name: '',
    type: 'expense',
    icon: 'HelpCircle',
    color: '#3b82f6',
});

// Icon List for dropdown selection
const availableIcons = [
    'Briefcase',
    'Laptop',
    'TrendingUp',
    'Gift',
    'DollarSign',
    'Utensils',
    'Car',
    'CreditCard',
    'Gamepad2',
    'ShoppingBag',
    'HeartPulse',
    'Coins',
    'HelpCircle',
    'Home',
    'Activity',
    'BookOpen',
    'Bookmark',
    'Phone',
    'Music',
    'MapPin',
];

// Color Presets
const colors = [
    '#3b82f6', // Blue
    '#10b981', // Green
    '#f59e0b', // Yellow
    '#ef4444', // Red
    '#8b5cf6', // Purple
    '#ec4899', // Pink
    '#06b6d4', // Cyan
    '#f97316', // Orange
    '#6b7280', // Gray
];

// Open Actions
const openCreate = () => {
    createForm.reset();
    isCreateOpen.value = true;
};

const openEdit = (cat: any) => {
    selectedCategory.value = cat;
    editForm.name = cat.name;
    editForm.type = cat.type;
    editForm.icon = cat.icon || 'HelpCircle';
    editForm.color = cat.color || '#3b82f6';
    isEditOpen.value = true;
};

// Handlers
const handleCreate = () => {
    createForm.post(storeCategory().url, {
        onSuccess: () => {
            isCreateOpen.value = false;
            createForm.reset();
        },
    });
};

const handleEdit = () => {
    if (!selectedCategory.value) {
return;
}

    editForm.patch(updateCategory(selectedCategory.value.id).url, {
        onSuccess: () => {
            isEditOpen.value = false;
            selectedCategory.value = null;
        },
    });
};

const handleDelete = async (cat: any) => {
    const isConfirmed = await confirm({
        title: 'Hapus Kategori',
        message: `Apakah Anda yakin ingin menghapus kategori "${cat.name}"? Transaksi yang menggunakan kategori ini akan di-nullify!`,
        confirmText: 'Hapus',
        cancelText: 'Batal',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(destroyCategory(cat.id).url);
    }
};

// Render Lucide Icon dynamically
const getIconComponent = (iconName: string) => {
    return (LucideIcons as any)[iconName] || HelpCircle;
};
</script>

<template>
    <Head title="Kategori Keuangan" />

    <div class="space-y-6 px-6 py-6">
        <!-- Header -->
        <div
            class="flex flex-col justify-between gap-4 border-b border-border pb-5 md:flex-row md:items-center"
        >
            <div class="space-y-1">
                <h2
                    class="font-outfit flex items-center gap-2 text-2xl font-bold tracking-tight"
                >
                    <Tag class="size-6 text-emerald-500" />
                    Kategori Keuangan
                </h2>
                <p class="text-sm text-muted-foreground">
                    Atur kategori pengeluaran dan pemasukan untuk mempermudah
                    analisis anggaran Anda.
                </p>
            </div>

            <div>
                <Button
                    size="sm"
                    @click="openCreate()"
                    class="flex items-center gap-1.5"
                >
                    <Plus class="size-4" />
                    Tambah Kategori
                </Button>
            </div>
        </div>

        <!-- Income vs Expense Grid -->
        <div class="grid gap-6 md:grid-cols-2">
            <!-- Pemasukan (Income) -->
            <Card class="shadow-sm">
                <CardHeader>
                    <CardTitle
                        class="font-outfit flex items-center gap-2 text-base font-semibold text-foreground"
                    >
                        <ArrowUpRight class="size-5 text-emerald-500" />
                        Kategori Pemasukan
                    </CardTitle>
                    <CardDescription class="text-muted-foreground"
                        >Master data pencatatan kas masuk</CardDescription
                    >
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-if="
                            categories.filter((c) => c.type === 'income')
                                .length === 0
                        "
                        class="py-10 text-center text-sm text-muted-foreground"
                    >
                        Belum ada kategori pemasukan.
                    </div>
                    <div
                        v-for="cat in categories.filter(
                            (c) => c.type === 'income',
                        )"
                        :key="cat.id"
                        class="group flex items-center justify-between rounded-lg border border-border bg-background/60 p-3 transition duration-150 hover:border-border"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="rounded-lg p-2"
                                :style="{
                                    backgroundColor: cat.color + '15',
                                    color: cat.color,
                                }"
                            >
                                <component
                                    :is="getIconComponent(cat.icon)"
                                    class="size-4"
                                />
                            </div>
                            <div>
                                <h4
                                    class="text-sm font-semibold text-foreground"
                                >
                                    {{ cat.name }}
                                </h4>
                                <span
                                    class="font-mono text-xs text-muted-foreground"
                                    >{{
                                        cat.transactions_count
                                    }}
                                    Transaksi</span
                                >
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-1 opacity-0 transition duration-150 group-hover:opacity-100"
                        >
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-8 hover:bg-muted"
                                @click="openEdit(cat)"
                            >
                                <Pencil
                                    class="size-3.5 text-muted-foreground"
                                />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-8 hover:bg-muted"
                                @click="handleDelete(cat)"
                            >
                                <Trash2 class="size-3.5 text-rose-400" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Pengeluaran (Expense) -->
            <Card class="shadow-sm">
                <CardHeader>
                    <CardTitle
                        class="font-outfit flex items-center gap-2 text-base font-semibold text-foreground"
                    >
                        <ArrowDownRight class="size-5 text-rose-500" />
                        Kategori Pengeluaran
                    </CardTitle>
                    <CardDescription class="text-muted-foreground"
                        >Master data pencatatan kas keluar</CardDescription
                    >
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-if="
                            categories.filter((c) => c.type === 'expense')
                                .length === 0
                        "
                        class="py-10 text-center text-sm text-muted-foreground"
                    >
                        Belum ada kategori pengeluaran.
                    </div>
                    <div
                        v-for="cat in categories.filter(
                            (c) => c.type === 'expense',
                        )"
                        :key="cat.id"
                        class="group flex items-center justify-between rounded-lg border border-border bg-background/60 p-3 transition duration-150 hover:border-border"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="rounded-lg p-2"
                                :style="{
                                    backgroundColor: cat.color + '15',
                                    color: cat.color,
                                }"
                            >
                                <component
                                    :is="getIconComponent(cat.icon)"
                                    class="size-4"
                                />
                            </div>
                            <div>
                                <h4
                                    class="text-sm font-semibold text-foreground"
                                >
                                    {{ cat.name }}
                                </h4>
                                <span
                                    class="font-mono text-xs text-muted-foreground"
                                    >{{
                                        cat.transactions_count
                                    }}
                                    Transaksi</span
                                >
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-1 opacity-0 transition duration-150 group-hover:opacity-100"
                        >
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-8 hover:bg-muted"
                                @click="openEdit(cat)"
                            >
                                <Pencil
                                    class="size-3.5 text-muted-foreground"
                                />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-8 hover:bg-muted"
                                @click="handleDelete(cat)"
                            >
                                <Trash2 class="size-3.5 text-rose-400" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- DIALOG MODALS -->

        <!-- Create Category Modal -->
        <Dialog :open="isCreateOpen" @update:open="isCreateOpen = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="font-outfit"
                        >Tambah Kategori Baru</DialogTitle
                    >
                    <DialogDescription class="text-muted-foreground"
                        >Buat kategori baru untuk memilah kas masuk atau kas
                        keluar.</DialogDescription
                    >
                </DialogHeader>

                <form @submit.prevent="handleCreate" class="space-y-4 py-2">
                    <div class="space-y-2">
                        <Label for="create-name" class="text-foreground/80"
                            >Nama Kategori</Label
                        >
                        <Input
                            id="create-name"
                            v-model="createForm.name"
                            placeholder="Misal: Investasi, Makanan, Tagihan"
                            class=""
                            required
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label class="text-foreground/80">Jenis</Label>
                            <Select v-model="createForm.type">
                                <SelectTrigger class="">
                                    <SelectValue placeholder="Pilih Jenis" />
                                </SelectTrigger>
                                <SelectContent class="">
                                    <SelectItem value="expense"
                                        >Pengeluaran</SelectItem
                                    >
                                    <SelectItem value="income"
                                        >Pemasukan</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label class="text-foreground/80"
                                >Ikon Lucide</Label
                            >
                            <Select v-model="createForm.icon">
                                <SelectTrigger class="">
                                    <SelectValue placeholder="Pilih Ikon" />
                                </SelectTrigger>
                                <SelectContent class="max-h-56">
                                    <SelectItem
                                        v-for="ic in availableIcons"
                                        :key="ic"
                                        :value="ic"
                                    >
                                        <div class="flex items-center gap-2">
                                            <component
                                                :is="getIconComponent(ic)"
                                                class="size-3.5 text-muted-foreground"
                                            />
                                            <span>{{ ic }}</span>
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label class="text-foreground/80">Warna Kategori</Label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="c in colors"
                                :key="c"
                                type="button"
                                class="size-6 transform rounded-full border border-border transition duration-150 hover:scale-110"
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

                    <DialogFooter class="pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isCreateOpen = false"
                            class="hover:bg-muted"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            class="bg-emerald-600 text-foreground hover:bg-emerald-700"
                            :disabled="createForm.processing"
                            >Simpan Kategori</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Edit Category Modal -->
        <Dialog :open="isEditOpen" @update:open="isEditOpen = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="font-outfit">Edit Kategori</DialogTitle>
                    <DialogDescription class="text-muted-foreground"
                        >Ubah rincian kategori "{{
                            selectedCategory?.name
                        }}".</DialogDescription
                    >
                </DialogHeader>

                <form @submit.prevent="handleEdit" class="space-y-4 py-2">
                    <div class="space-y-2">
                        <Label for="edit-name" class="text-foreground/80"
                            >Nama Kategori</Label
                        >
                        <Input
                            id="edit-name"
                            v-model="editForm.name"
                            class=""
                            required
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label class="text-foreground/80">Jenis</Label>
                            <Select v-model="editForm.type">
                                <SelectTrigger class="">
                                    <SelectValue placeholder="Pilih Jenis" />
                                </SelectTrigger>
                                <SelectContent class="">
                                    <SelectItem value="expense"
                                        >Pengeluaran</SelectItem
                                    >
                                    <SelectItem value="income"
                                        >Pemasukan</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label class="text-foreground/80"
                                >Ikon Lucide</Label
                            >
                            <Select v-model="editForm.icon">
                                <SelectTrigger class="">
                                    <SelectValue placeholder="Pilih Ikon" />
                                </SelectTrigger>
                                <SelectContent class="max-h-56">
                                    <SelectItem
                                        v-for="ic in availableIcons"
                                        :key="ic"
                                        :value="ic"
                                    >
                                        <div class="flex items-center gap-2">
                                            <component
                                                :is="getIconComponent(ic)"
                                                class="size-3.5 text-muted-foreground"
                                            />
                                            <span>{{ ic }}</span>
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label class="text-foreground/80">Warna Kategori</Label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="c in colors"
                                :key="c"
                                type="button"
                                class="size-6 transform rounded-full border border-border transition duration-150 hover:scale-110"
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

                    <DialogFooter class="pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isEditOpen = false"
                            class="hover:bg-muted"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            class="bg-emerald-600 text-foreground hover:bg-emerald-700"
                            :disabled="editForm.processing"
                            >Simpan Kategori</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useConfirm } from '@/composables/useConfirm';

const { confirm } = useConfirm();
import {
    Users,
    Plus,
    Search,
    Building2,
    Mail,
    Phone,
    Receipt,
    MapPin,
    FileText,
    Edit,
    Trash2,
    ExternalLink,
    CheckCircle2,
    Clock,
    AlertTriangle,
    Coins,
    Copy,
    Check,
    ArrowLeft,
    FolderKanban,
} from '@lucide/vue';
import {
    index as clientsIndex,
    store as clientStore,
    update as clientUpdate,
    destroy as clientDestroy,
} from '@/routes/clients';
import { show as projectShow } from '@/routes/projects';

type Project = {
    id: number;
    name: string;
    slug: string;
    color: string | null;
    status: 'active' | 'pipeline' | 'archived';
    created_at: string;
};

type Client = {
    id: number;
    name: string;
    company: string | null;
    email: string | null;
    phone: string | null;
    tax_id: string | null;
    billing_address: string | null;
    notes: string | null;
    projects_count: number;
    projects: Project[];
    paid_amount: number;
    unpaid_amount: number;
    overdue_amount: number;
    lifetime_value: number;
    created_at: string;
};

const props = defineProps<{
    clients: Client[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Clients', href: clientsIndex().url }],
    },
});

// Selection & Navigation
const selectedClientId = ref<number | null>(null);
const search = ref('');
const copiedField = ref<string | null>(null);
const isMobileDetailActive = ref(false);

// Form / Sheet State
const isSheetOpen = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null as number | null,
    name: '',
    company: '',
    email: '',
    phone: '',
    tax_id: '',
    billing_address: '',
    notes: '',
});

// Compute active selected client reactively
const selectedClient = computed(() => {
    if (selectedClientId.value === null) {
return null;
}

    return props.clients.find((c) => c.id === selectedClientId.value) || null;
});

// Set initial selection
onMounted(() => {
    if (props.clients.length > 0) {
        selectedClientId.value = props.clients[0].id;
    }
});

// Filtered clients list
const filteredClients = computed(() => {
    if (!search.value) {
return props.clients;
}

    const query = search.value.toLowerCase();

    return props.clients.filter(
        (c) =>
            c.name.toLowerCase().includes(query) ||
            (c.company && c.company.toLowerCase().includes(query)) ||
            (c.email && c.email.toLowerCase().includes(query)),
    );
});

// Automatically manage selection changes when list changes or resets
watch(filteredClients, (newVal) => {
    if (newVal.length > 0) {
        // If current selection is not in the filtered list, select the first filtered item
        const exists = newVal.some((c) => c.id === selectedClientId.value);

        if (!exists) {
            selectedClientId.value = newVal[0].id;
        }
    } else {
        selectedClientId.value = null;
    }
});

// Copy helpers
function copyText(text: string, label: string) {
    navigator.clipboard.writeText(text);
    copiedField.value = label;
    toast.success(`${label} copied to clipboard`);
    setTimeout(() => {
        if (copiedField.value === label) {
            copiedField.value = null;
        }
    }, 2000);
}

// Dialog Actions
function openCreateSheet() {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    isSheetOpen.value = true;
}

function openEditSheet(client: Client) {
    isEditing.value = true;
    form.clearErrors();
    form.id = client.id;
    form.name = client.name;
    form.company = client.company || '';
    form.email = client.email || '';
    form.phone = client.phone || '';
    form.tax_id = client.tax_id || '';
    form.billing_address = client.billing_address || '';
    form.notes = client.notes || '';
    isSheetOpen.value = true;
}

function selectClient(client: Client) {
    selectedClientId.value = client.id;
    isMobileDetailActive.value = true;
}

function submitForm() {
    if (isEditing.value && form.id) {
        form.put(clientUpdate(form.id).url, {
            onSuccess: () => {
                isSheetOpen.value = false;
                toast.success('Client updated successfully');
            },
        });
    } else {
        form.post(clientStore().url, {
            onSuccess: (page) => {
                isSheetOpen.value = false;
                toast.success('Client created successfully');
                // Select the newly created client
                const newName = form.name;
                const match = props.clients.find((c) => c.name === newName);

                if (match) {
                    selectedClientId.value = match.id;
                }
            },
        });
    }
}

async function deleteClient(client: Client) {
    const isConfirmed = await confirm({
        title: 'Delete Client',
        message: `Are you sure you want to delete ${client.name}? Associated projects will be unlinked.`,
        confirmText: 'Delete',
        cancelText: 'Cancel',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(clientDestroy(client.id).url, {
            onSuccess: () => {
                toast.success('Client deleted successfully');
                isMobileDetailActive.value = false;

                if (props.clients.length > 0) {
                    selectedClientId.value = props.clients[0].id;
                } else {
                    selectedClientId.value = null;
                }
            },
        });
    }
}

function getInitials(name: string): string {
    return name
        .split(/[\s-_]+/)
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase();
}

const statusConfig = {
    active: {
        label: 'Active',
        variant: 'default' as const,
        classes: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20',
    },
    pipeline: {
        label: 'Pipeline',
        variant: 'secondary' as const,
        classes: 'bg-amber-500/10 text-amber-500 border-amber-500/20',
    },
    archived: {
        label: 'Archived',
        variant: 'outline' as const,
        classes: 'bg-slate-500/10 text-slate-500 border-slate-500/20',
    },
};

const avatarColors = [
    'bg-indigo-500/10 text-indigo-500 border-indigo-500/20 dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-900',
    'bg-emerald-500/10 text-emerald-500 border-emerald-500/20 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900',
    'bg-purple-500/10 text-purple-500 border-purple-500/20 dark:bg-purple-950/40 dark:text-purple-400 dark:border-purple-900',
    'bg-rose-500/10 text-rose-500 border-rose-500/20 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900',
    'bg-blue-500/10 text-blue-500 border-blue-500/20 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-900',
];

function getAvatarColor(id: number): string {
    return avatarColors[id % avatarColors.length];
}
</script>

<template>
    <Head title="Clients" />

    <div
        class="flex h-[calc(100vh-4rem)] flex-col space-y-6 overflow-hidden px-6 py-6"
    >
        <!-- Header -->
        <div class="flex shrink-0 items-center justify-between border-b pb-5">
            <div class="space-y-1">
                <h2
                    class="flex items-center gap-2 text-2xl font-bold tracking-tight"
                >
                    <Users class="size-6 text-primary" />
                    Clients
                </h2>
                <p class="text-sm text-muted-foreground">
                    Manage client records, billing details, projects, and notes
                    in one place.
                </p>
            </div>
            <Button
                @click="openCreateSheet"
                class="h-9 gap-1.5 text-xs font-medium"
            >
                <Plus class="size-4" />
                New Client
            </Button>
        </div>

        <!-- Main Workspace (Desktop Split-Pane) -->
        <div
            class="relative grid min-h-0 flex-1 grid-cols-12 gap-6 overflow-hidden"
        >
            <!-- Left Pane: Client List -->
            <div
                class="col-span-12 flex h-full min-h-0 flex-col space-y-4 border-r pr-2 lg:col-span-4 lg:pr-6"
                :class="[isMobileDetailActive ? 'hidden lg:flex' : 'flex']"
            >
                <!-- Search -->
                <div class="relative shrink-0">
                    <Search
                        class="absolute top-2.5 left-3 size-4 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        placeholder="Search clients..."
                        class="h-9 pl-9"
                    />
                </div>

                <!-- Client List Items -->
                <div
                    class="flex-1 scrollbar-thin space-y-2 overflow-y-auto pr-2"
                >
                    <div
                        v-if="filteredClients.length === 0"
                        class="flex flex-col items-center justify-center rounded-xl border border-dashed py-12 text-center"
                    >
                        <Users class="mb-2 size-8 text-muted-foreground/60" />
                        <p class="text-sm font-medium text-foreground">
                            No clients found
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Try broadening your search or add a new client.
                        </p>
                    </div>

                    <button
                        v-for="client in filteredClients"
                        :key="client.id"
                        @click="selectClient(client)"
                        class="group flex w-full items-start gap-3 rounded-xl border p-3.5 text-left transition-all duration-200"
                        :class="[
                            selectedClientId === client.id
                                ? 'border-primary/20 bg-sidebar-accent text-foreground shadow-sm'
                                : 'border-border bg-card text-muted-foreground hover:bg-muted/40 hover:text-foreground',
                        ]"
                    >
                        <!-- Client Avatar -->
                        <div
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg border text-xs font-bold transition-transform duration-200 group-hover:scale-105"
                            :class="getAvatarColor(client.id)"
                        >
                            {{ getInitials(client.name) }}
                        </div>

                        <!-- Client Summary -->
                        <div class="min-w-0 flex-1 space-y-1">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <h4
                                    class="truncate text-sm font-semibold text-foreground"
                                >
                                    {{ client.name }}
                                </h4>
                                <Badge
                                    variant="secondary"
                                    class="h-4 shrink-0 px-1.5 py-0 text-[10px] font-normal text-muted-foreground"
                                >
                                    {{ client.projects_count }}
                                    {{
                                        client.projects_count === 1
                                            ? 'Project'
                                            : 'Projects'
                                    }}
                                </Badge>
                            </div>

                            <div
                                class="flex items-center justify-between gap-2 text-xs"
                            >
                                <span
                                    class="flex items-center gap-1 truncate text-muted-foreground/80"
                                >
                                    <Building2
                                        v-if="client.company"
                                        class="size-3.5"
                                    />
                                    {{ client.company || 'Individual Client' }}
                                </span>
                            </div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Right Pane: Client Detail -->
            <div
                class="col-span-12 flex h-full min-h-0 flex-col bg-background lg:col-span-8 lg:bg-transparent"
                :class="[isMobileDetailActive ? 'flex' : 'hidden lg:flex']"
            >
                <div
                    v-if="selectedClient"
                    class="flex h-full min-h-0 flex-col space-y-6 overflow-hidden"
                >
                    <!-- Detail Header -->
                    <div
                        class="flex shrink-0 items-center justify-between border-b pb-4"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <!-- Mobile Back Button -->
                            <Button
                                variant="ghost"
                                size="icon"
                                class="mr-1 size-8 shrink-0 lg:hidden"
                                @click="isMobileDetailActive = false"
                            >
                                <ArrowLeft class="size-4" />
                            </Button>

                            <div
                                class="flex size-12 shrink-0 items-center justify-center rounded-xl border text-lg font-bold"
                                :class="getAvatarColor(selectedClient.id)"
                            >
                                {{ getInitials(selectedClient.name) }}
                            </div>
                            <div class="min-w-0">
                                <h3
                                    class="truncate text-lg font-bold text-foreground"
                                >
                                    {{ selectedClient.name }}
                                </h3>
                                <p
                                    class="flex items-center gap-1.5 truncate text-xs text-muted-foreground"
                                >
                                    <Building2 class="size-3.5" />
                                    {{
                                        selectedClient.company ||
                                        'Individual Client'
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex shrink-0 items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                class="h-8 gap-1.5 text-xs"
                                @click="openEditSheet(selectedClient)"
                            >
                                <Edit class="size-3.5" />
                                Edit
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="h-8 gap-1.5 text-xs text-destructive hover:bg-destructive/10 hover:text-destructive"
                                @click="deleteClient(selectedClient)"
                            >
                                <Trash2 class="size-3.5" />
                                Delete
                            </Button>
                        </div>
                    </div>

                    <!-- Scrollable Details Content -->
                    <div
                        class="flex-1 scrollbar-thin space-y-6 overflow-y-auto pr-2 pb-6"
                    >
                        <!-- Financial Aggregations (Stubs) -->
                        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                            <!-- Paid -->
                            <Card
                                class="relative overflow-hidden border-l-2 border-l-emerald-500 bg-card/40"
                            >
                                <CardContent
                                    class="flex items-center gap-3 p-4"
                                >
                                    <div
                                        class="rounded-lg bg-emerald-500/10 p-2 text-emerald-500"
                                    >
                                        <CheckCircle2 class="size-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="text-[10px] font-medium tracking-wider text-muted-foreground uppercase"
                                        >
                                            Paid Invoices
                                        </p>
                                        <h4
                                            class="mt-0.5 text-sm font-bold text-foreground"
                                        >
                                            ${{
                                                selectedClient.paid_amount.toFixed(
                                                    2,
                                                )
                                            }}
                                        </h4>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Unpaid -->
                            <Card
                                class="relative overflow-hidden border-l-2 border-l-amber-500 bg-card/40"
                            >
                                <CardContent
                                    class="flex items-center gap-3 p-4"
                                >
                                    <div
                                        class="rounded-lg bg-amber-500/10 p-2 text-amber-500"
                                    >
                                        <Clock class="size-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="text-[10px] font-medium tracking-wider text-muted-foreground uppercase"
                                        >
                                            Unpaid
                                        </p>
                                        <h4
                                            class="mt-0.5 text-sm font-bold text-foreground"
                                        >
                                            ${{
                                                selectedClient.unpaid_amount.toFixed(
                                                    2,
                                                )
                                            }}
                                        </h4>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Overdue -->
                            <Card
                                class="relative overflow-hidden border-l-2 border-l-rose-500 bg-card/40"
                            >
                                <CardContent
                                    class="flex items-center gap-3 p-4"
                                >
                                    <div
                                        class="rounded-lg bg-rose-500/10 p-2 text-rose-500"
                                    >
                                        <AlertTriangle class="size-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="text-[10px] font-medium tracking-wider text-muted-foreground uppercase"
                                        >
                                            Overdue
                                        </p>
                                        <h4
                                            class="mt-0.5 text-sm font-bold text-foreground"
                                        >
                                            ${{
                                                selectedClient.overdue_amount.toFixed(
                                                    2,
                                                )
                                            }}
                                        </h4>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Lifetime Value -->
                            <Card
                                class="relative overflow-hidden border-l-2 border-l-indigo-500 bg-card/40"
                            >
                                <CardContent
                                    class="flex items-center gap-3 p-4"
                                >
                                    <div
                                        class="rounded-lg bg-indigo-500/10 p-2 text-indigo-500"
                                    >
                                        <Coins class="size-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="text-[10px] font-medium tracking-wider text-muted-foreground uppercase"
                                        >
                                            Lifetime Value
                                        </p>
                                        <h4
                                            class="mt-0.5 text-sm font-bold text-foreground"
                                        >
                                            ${{
                                                selectedClient.lifetime_value.toFixed(
                                                    2,
                                                )
                                            }}
                                        </h4>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Info & Address Grid -->
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Contact Info Box -->
                            <div
                                class="space-y-4 rounded-xl border bg-card p-5 shadow-sm"
                            >
                                <h4
                                    class="flex items-center gap-2 text-xs font-bold tracking-wider text-muted-foreground uppercase"
                                >
                                    <Mail class="size-3.5 text-primary" />
                                    Contact & Tax Information
                                </h4>

                                <div class="space-y-3.5 text-sm">
                                    <!-- Email -->
                                    <div
                                        class="group flex items-center justify-between gap-4"
                                    >
                                        <span class="text-muted-foreground"
                                            >Email Address</span
                                        >
                                        <div
                                            class="flex min-w-0 items-center gap-1.5"
                                        >
                                            <span
                                                class="truncate font-medium text-foreground select-all"
                                                >{{
                                                    selectedClient.email || '-'
                                                }}</span
                                            >
                                            <button
                                                v-if="selectedClient.email"
                                                @click="
                                                    copyText(
                                                        selectedClient.email,
                                                        'Email',
                                                    )
                                                "
                                                class="rounded p-1 text-muted-foreground opacity-0 transition-all duration-150 group-hover:opacity-100 hover:bg-muted hover:text-foreground focus:opacity-100"
                                                title="Copy Email"
                                            >
                                                <Check
                                                    v-if="
                                                        copiedField === 'Email'
                                                    "
                                                    class="size-3 text-emerald-500"
                                                />
                                                <Copy v-else class="size-3" />
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Phone -->
                                    <div
                                        class="group flex items-center justify-between gap-4"
                                    >
                                        <span class="text-muted-foreground"
                                            >Phone Number</span
                                        >
                                        <div
                                            class="flex min-w-0 items-center gap-1.5"
                                        >
                                            <span
                                                class="truncate font-medium text-foreground"
                                                >{{
                                                    selectedClient.phone || '-'
                                                }}</span
                                            >
                                            <button
                                                v-if="selectedClient.phone"
                                                @click="
                                                    copyText(
                                                        selectedClient.phone,
                                                        'Phone',
                                                    )
                                                "
                                                class="rounded p-1 text-muted-foreground opacity-0 transition-all duration-150 group-hover:opacity-100 hover:bg-muted hover:text-foreground focus:opacity-100"
                                                title="Copy Phone"
                                            >
                                                <Check
                                                    v-if="
                                                        copiedField === 'Phone'
                                                    "
                                                    class="size-3 text-emerald-500"
                                                />
                                                <Copy v-else class="size-3" />
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Tax ID -->
                                    <div
                                        class="group flex items-center justify-between gap-4"
                                    >
                                        <span class="text-muted-foreground"
                                            >Tax ID / NPWP</span
                                        >
                                        <div
                                            class="flex min-w-0 items-center gap-1.5"
                                        >
                                            <span
                                                class="truncate font-mono text-xs font-medium text-foreground"
                                                >{{
                                                    selectedClient.tax_id || '-'
                                                }}</span
                                            >
                                            <button
                                                v-if="selectedClient.tax_id"
                                                @click="
                                                    copyText(
                                                        selectedClient.tax_id,
                                                        'Tax ID',
                                                    )
                                                "
                                                class="rounded p-1 text-muted-foreground opacity-0 transition-all duration-150 group-hover:opacity-100 hover:bg-muted hover:text-foreground focus:opacity-100"
                                                title="Copy Tax ID"
                                            >
                                                <Check
                                                    v-if="
                                                        copiedField === 'Tax ID'
                                                    "
                                                    class="size-3 text-emerald-500"
                                                />
                                                <Copy v-else class="size-3" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Billing Address Box -->
                            <div
                                class="flex flex-col justify-between space-y-4 rounded-xl border bg-card p-5 shadow-sm"
                            >
                                <div>
                                    <h4
                                        class="flex items-center gap-2 text-xs font-bold tracking-wider text-muted-foreground uppercase"
                                    >
                                        <MapPin class="size-3.5 text-primary" />
                                        Billing Address
                                    </h4>
                                    <p
                                        class="mt-3 text-sm leading-relaxed font-medium whitespace-pre-line text-foreground"
                                    >
                                        {{
                                            selectedClient.billing_address ||
                                            'No billing address provided.'
                                        }}
                                    </p>
                                </div>

                                <div
                                    v-if="selectedClient.billing_address"
                                    class="mt-2 flex shrink-0 justify-end"
                                >
                                    <Button
                                        variant="outline"
                                        size="xs"
                                        class="h-7 gap-1 px-2.5 text-[10px]"
                                        @click="
                                            copyText(
                                                selectedClient.billing_address,
                                                'Address',
                                            )
                                        "
                                    >
                                        <Check
                                            v-if="copiedField === 'Address'"
                                            class="size-3 text-emerald-500"
                                        />
                                        <Copy v-else class="size-3" />
                                        {{
                                            copiedField === 'Address'
                                                ? 'Copied'
                                                : 'Copy Address'
                                        }}
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Projects List -->
                        <div
                            class="space-y-4 rounded-xl border bg-card p-5 shadow-sm"
                        >
                            <h4
                                class="flex items-center gap-2 border-b pb-2 text-xs font-bold tracking-wider text-muted-foreground uppercase"
                            >
                                <FolderKanban class="size-4 text-primary" />
                                Projects History
                            </h4>

                            <div
                                v-if="
                                    selectedClient.projects &&
                                    selectedClient.projects.length > 0
                                "
                                class="divide-y"
                            >
                                <div
                                    v-for="project in selectedClient.projects"
                                    :key="project.id"
                                    class="group flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0"
                                >
                                    <div
                                        class="flex min-w-0 items-center gap-2.5"
                                    >
                                        <span
                                            class="size-2.5 shrink-0 rounded-full"
                                            :style="{
                                                backgroundColor:
                                                    project.color || '#5C59D9',
                                            }"
                                        ></span>
                                        <span
                                            class="truncate text-sm font-semibold text-foreground transition-colors duration-150 group-hover:text-primary"
                                        >
                                            {{ project.name }}
                                        </span>
                                    </div>

                                    <div
                                        class="flex shrink-0 items-center gap-3"
                                    >
                                        <Badge
                                            variant="outline"
                                            class="shrink-0 px-2 py-0.5 text-[10px] font-normal tracking-wider uppercase"
                                            :class="
                                                statusConfig[project.status]
                                                    .classes
                                            "
                                        >
                                            {{
                                                statusConfig[project.status]
                                                    .label
                                            }}
                                        </Badge>

                                        <Link
                                            :href="projectShow(project.id).url"
                                            class="rounded p-1.5 text-muted-foreground transition-all duration-150 hover:bg-muted hover:text-foreground"
                                        >
                                            <ExternalLink class="size-3.5" />
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-else
                                class="flex flex-col items-center justify-center py-6 text-center text-muted-foreground"
                            >
                                <FolderKanban
                                    class="mb-1 size-6 text-muted-foreground/40"
                                />
                                <p class="text-xs">
                                    No projects associated with this client.
                                </p>
                            </div>
                        </div>

                        <!-- Notes Area -->
                        <div
                            class="space-y-4 rounded-xl border bg-card p-5 shadow-sm"
                        >
                            <h4
                                class="flex items-center gap-2 border-b pb-2 text-xs font-bold tracking-wider text-muted-foreground uppercase"
                            >
                                <FileText class="size-4 text-primary" />
                                Private Notes
                            </h4>
                            <div
                                class="min-h-[5rem] text-sm leading-relaxed whitespace-pre-line text-foreground/90"
                            >
                                {{
                                    selectedClient.notes ||
                                    'No private notes registered for this client.'
                                }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State (No client selected) -->
                <div
                    v-else
                    class="flex flex-1 flex-col items-center justify-center py-12 text-center"
                >
                    <div
                        class="mb-3 animate-pulse rounded-full border border-primary/10 bg-primary/5 p-4 text-primary/40"
                    >
                        <Users class="size-10" />
                    </div>
                    <h3 class="text-base font-bold text-foreground">
                        No client selected
                    </h3>
                    <p
                        class="mt-1 max-w-xs text-xs leading-relaxed text-muted-foreground"
                    >
                        Select a client from the sidebar on the left or create a
                        new client to view and manage details.
                    </p>
                </div>
            </div>
        </div>

        <Sheet :open="isSheetOpen" @update:open="isSheetOpen = $event">
            <SheetContent
                class="flex h-full flex-col overflow-hidden p-6 sm:max-w-md md:max-w-lg"
            >
                <SheetHeader class="shrink-0 border-b p-0 pb-4">
                    <SheetTitle
                        class="flex items-center gap-2 font-bold text-foreground"
                    >
                        <Users class="size-5 text-primary" />
                        {{ isEditing ? 'Edit Client' : 'New Client' }}
                    </SheetTitle>
                    <SheetDescription
                        class="mt-1 text-xs text-muted-foreground"
                    >
                        {{
                            isEditing
                                ? 'Update the client record details.'
                                : 'Create a new client profile for billing and projects.'
                        }}
                    </SheetDescription>
                </SheetHeader>

                <!-- Form Scroll Area -->
                <form
                    @submit.prevent="submitForm"
                    class="flex-1 scrollbar-thin space-y-6 overflow-y-auto py-5 pr-1"
                >
                    <!-- Section 1: Client Identity -->
                    <div class="space-y-4">
                        <div>
                            <h4
                                class="flex items-center gap-2 text-sm font-semibold text-foreground"
                            >
                                <Users class="size-4 text-primary" />
                                Client Identity
                            </h4>
                            <p class="mt-0.5 text-[11px] text-muted-foreground">
                                Enter the client's name and company details.
                            </p>
                        </div>

                        <div class="space-y-4">
                            <!-- Name -->
                            <div class="space-y-1.5">
                                <Label
                                    for="name"
                                    class="text-xs font-medium text-foreground"
                                >
                                    Client Name
                                    <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="e.g., John Doe"
                                    class="h-9"
                                    required
                                />
                                <InputError :message="form.errors.name" />
                            </div>

                            <!-- Company -->
                            <div class="space-y-1.5">
                                <Label
                                    for="company"
                                    class="text-xs font-medium text-foreground"
                                    >Company Name</Label
                                >
                                <Input
                                    id="company"
                                    v-model="form.company"
                                    placeholder="e.g., Acme Corp"
                                    class="h-9"
                                />
                                <InputError :message="form.errors.company" />
                            </div>
                        </div>
                    </div>

                    <Separator class="bg-border/60" />

                    <!-- Section 2: Contact & Billing -->
                    <div class="space-y-4">
                        <div>
                            <h4
                                class="flex items-center gap-2 text-sm font-semibold text-foreground"
                            >
                                <Mail class="size-4 text-primary" />
                                Contact & Billing Info
                            </h4>
                            <p class="mt-0.5 text-[11px] text-muted-foreground">
                                Primary communication details and billing
                                address.
                            </p>
                        </div>

                        <div class="space-y-4">
                            <!-- Email -->
                            <div class="space-y-1.5">
                                <Label
                                    for="email"
                                    class="text-xs font-medium text-foreground"
                                    >Email Address</Label
                                >
                                <Input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    placeholder="e.g., client@company.com"
                                    class="h-9"
                                />
                                <InputError :message="form.errors.email" />
                            </div>

                            <!-- Grid Phone & Tax ID -->
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="space-y-1.5">
                                    <Label
                                        for="phone"
                                        class="text-xs font-medium text-foreground"
                                        >Phone Number</Label
                                    >
                                    <Input
                                        id="phone"
                                        v-model="form.phone"
                                        placeholder="e.g., +1-555-0199"
                                        class="h-9"
                                    />
                                    <InputError :message="form.errors.phone" />
                                </div>

                                <div class="space-y-1.5">
                                    <Label
                                        for="tax_id"
                                        class="text-xs font-medium text-foreground"
                                        >Tax ID / VAT</Label
                                    >
                                    <Input
                                        id="tax_id"
                                        v-model="form.tax_id"
                                        placeholder="e.g., TX-987654-32"
                                        class="h-9"
                                    />
                                    <InputError :message="form.errors.tax_id" />
                                </div>
                            </div>

                            <!-- Billing Address -->
                            <div class="space-y-1.5">
                                <Label
                                    for="billing_address"
                                    class="text-xs font-medium text-foreground"
                                    >Billing Address</Label
                                >
                                <textarea
                                    id="billing_address"
                                    v-model="form.billing_address"
                                    placeholder="e.g., Wayne Manor, Gotham City"
                                    rows="3"
                                    class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 md:text-sm dark:aria-invalid:ring-destructive/40"
                                ></textarea>
                                <InputError
                                    :message="form.errors.billing_address"
                                />
                            </div>
                        </div>
                    </div>

                    <Separator class="bg-border/60" />

                    <!-- Section 3: Notes -->
                    <div class="space-y-4">
                        <div>
                            <h4
                                class="flex items-center gap-2 text-sm font-semibold text-foreground"
                            >
                                <FileText class="size-4 text-primary" />
                                Additional Notes
                            </h4>
                            <p class="mt-0.5 text-[11px] text-muted-foreground">
                                Private notes, terms, or internal agreements.
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label
                                for="notes"
                                class="text-xs font-medium text-foreground"
                                >Internal Notes</Label
                            >
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                placeholder="Add private comments, billing preferences, special agreements..."
                                rows="4"
                                class="flex min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 md:text-sm dark:aria-invalid:ring-destructive/40"
                            ></textarea>
                            <InputError :message="form.errors.notes" />
                        </div>
                    </div>

                    <!-- Submit trigger (hidden, triggers on form submit) -->
                    <input type="submit" class="hidden" />
                </form>

                <!-- Footer -->
                <SheetFooter
                    class="flex shrink-0 flex-row items-center justify-end gap-3 border-t pt-4"
                >
                    <Button
                        type="button"
                        variant="outline"
                        class="h-9 px-4 text-xs font-medium"
                        @click="isSheetOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        class="h-9 px-4 text-xs font-medium"
                        :disabled="form.processing"
                        @click="submitForm"
                    >
                        {{
                            form.processing
                                ? 'Saving...'
                                : isEditing
                                  ? 'Save Changes'
                                  : 'Create Client'
                        }}
                    </Button>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    </div>
</template>

<style scoped>
/* Thin Scrollbar Styling for Premium feel */
.scrollbar-thin::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background: rgba(100, 116, 139, 0.2);
    border-radius: 4px;
}
.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: rgba(100, 116, 139, 0.4);
}
</style>

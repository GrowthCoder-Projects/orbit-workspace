<script setup lang="ts">
import { Search, ChevronRight, BookOpen, KeyRound, CheckCircle2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Input } from '@/components/ui/input';

interface Endpoint {
    id: string;
    module: string;
    title: string;
    method: 'GET' | 'POST' | 'PATCH' | 'DELETE';
    path: string;
}

const props = defineProps<{
    endpoints: Endpoint[];
    selectedEndpointId: string;
}>();

const emit = defineEmits<{
    (e: 'select', id: string): void;
}>();

const searchQuery = ref('');

const filteredEndpoints = computed(() => {
    if (!searchQuery.value.trim()) return props.endpoints;
    const q = searchQuery.value.toLowerCase();
    return props.endpoints.filter(
        (e) =>
            e.title.toLowerCase().includes(q) ||
            e.path.toLowerCase().includes(q) ||
            e.module.toLowerCase().includes(q)
    );
});

const groupedEndpoints = computed(() => {
    const groups: Record<string, Endpoint[]> = {};
    for (const ep of filteredEndpoints.value) {
        if (!groups[ep.module]) {
            groups[ep.module] = [];
        }
        groups[ep.module].push(ep);
    }
    return groups;
});

const getBadgeStyle = (method: string) => {
    switch (method) {
        case 'GET':
            return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20';
        case 'POST':
            return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20';
        case 'PATCH':
            return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20';
        case 'DELETE':
            return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20';
        default:
            return 'bg-muted text-muted-foreground';
    }
};
</script>

<template>
    <div class="flex flex-col h-full space-y-4 pr-2">
        <!-- Search Input -->
        <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input
                v-model="searchQuery"
                type="text"
                placeholder="Search endpoints..."
                class="pl-9 bg-card/60 backdrop-blur"
            />
        </div>

        <!-- Sidebar Navigation Items -->
        <div class="flex-1 overflow-y-auto space-y-6 pr-1">
            <div v-for="(eps, module) in groupedEndpoints" :key="module" class="space-y-2">
                <div class="text-xs font-semibold uppercase tracking-wider text-muted-foreground px-2 flex items-center gap-1.5">
                    <BookOpen class="h-3.5 w-3.5" />
                    <span>{{ module }}</span>
                </div>

                <div class="space-y-1">
                    <div
                        v-for="ep in eps"
                        :key="ep.id"
                        role="button"
                        :class="[
                            'w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium transition-all duration-150 text-left border cursor-pointer select-none',
                            selectedEndpointId === ep.id
                                ? 'bg-accent border-accent text-accent-foreground shadow-sm font-semibold'
                                : 'border-transparent text-muted-foreground hover:bg-muted/50 hover:text-foreground'
                        ]"
                        @click="emit('select', ep.id)"
                    >
                        <span class="truncate pr-2">{{ ep.title }}</span>
                        <span
                            :class="[
                                'px-1.5 py-0.5 rounded text-[10px] font-mono font-bold border uppercase shrink-0',
                                getBadgeStyle(ep.method)
                            ]"
                        >
                            {{ ep.method }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

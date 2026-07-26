<script setup lang="ts">
import { Code2, Database, ListFilter } from '@lucide/vue';

interface Param {
    name: string;
    type: string;
    required: boolean;
    description: string;
}

interface Endpoint {
    id: string;
    module: string;
    title: string;
    method: 'GET' | 'POST' | 'PATCH' | 'DELETE';
    path: string;
    description: string;
    params: Param[];
    requestBody: any;
    response: any;
}

const props = defineProps<{
    endpoint: Endpoint;
}>();

const getMethodBadgeClass = (method: string) => {
    switch (method) {
        case 'GET':
            return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30';
        case 'POST':
            return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/30';
        case 'PATCH':
            return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30';
        case 'DELETE':
            return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/30';
        default:
            return 'bg-muted text-muted-foreground';
    }
};
</script>

<template>
    <div class="space-y-8">
        <!-- Header Info -->
        <div class="space-y-3 pb-6 border-b">
            <div class="flex items-center gap-3">
                <span
                    :class="[
                        'px-2.5 py-1 rounded-md text-xs font-mono font-bold border uppercase tracking-wider',
                        getMethodBadgeClass(endpoint.method)
                    ]"
                >
                    {{ endpoint.method }}
                </span>
                <code class="font-mono text-sm sm:text-base font-semibold text-foreground px-3 py-1 rounded bg-muted/60 border">
                    {{ endpoint.path }}
                </code>
            </div>

            <h2 class="text-2xl font-bold tracking-tight text-foreground">
                {{ endpoint.title }}
            </h2>

            <p class="text-sm text-muted-foreground leading-relaxed">
                {{ endpoint.description }}
            </p>
        </div>

        <!-- Parameters Table -->
        <div v-if="endpoint.params && endpoint.params.length > 0" class="space-y-4">
            <h3 class="text-sm font-semibold flex items-center gap-2 text-foreground">
                <ListFilter class="h-4 w-4 text-primary" />
                <span>Parameters</span>
            </h3>

            <div class="rounded-xl border overflow-hidden bg-card">
                <table class="w-full text-left text-xs">
                    <thead class="bg-muted/50 border-b text-muted-foreground font-semibold">
                        <tr>
                            <th class="py-2.5 px-4">Parameter</th>
                            <th class="py-2.5 px-4">Type</th>
                            <th class="py-2.5 px-4">Status</th>
                            <th class="py-2.5 px-4">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="param in endpoint.params" :key="param.name" class="hover:bg-muted/30">
                            <td class="py-3 px-4 font-mono font-semibold text-primary">
                                {{ param.name }}
                            </td>
                            <td class="py-3 px-4 font-mono text-muted-foreground">
                                {{ param.type }}
                            </td>
                            <td class="py-3 px-4">
                                <span
                                    :class="[
                                        'px-1.5 py-0.5 rounded text-[10px] font-medium border',
                                        param.required
                                            ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20'
                                            : 'bg-muted text-muted-foreground border-transparent'
                                    ]"
                                >
                                    {{ param.required ? 'required' : 'optional' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-muted-foreground">
                                {{ param.description }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Request Body Spec -->
        <div v-if="endpoint.requestBody" class="space-y-3">
            <h3 class="text-sm font-semibold flex items-center gap-2 text-foreground">
                <Code2 class="h-4 w-4 text-primary" />
                <span>Request Body (JSON)</span>
            </h3>

            <pre class="rounded-xl border bg-slate-950 p-4 font-mono text-xs text-slate-100 overflow-x-auto"><code>{{ JSON.stringify(endpoint.requestBody, null, 2) }}</code></pre>
        </div>

        <!-- Response Spec -->
        <div class="space-y-3">
            <h3 class="text-sm font-semibold flex items-center gap-2 text-foreground">
                <Database class="h-4 w-4 text-emerald-500" />
                <span>Expected Response (200 OK / 201 Created)</span>
            </h3>

            <pre class="rounded-xl border bg-slate-950 p-4 font-mono text-xs text-emerald-400 overflow-x-auto"><code>{{ JSON.stringify(endpoint.response, null, 2) }}</code></pre>
        </div>
    </div>
</template>

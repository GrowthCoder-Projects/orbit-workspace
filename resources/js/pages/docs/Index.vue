<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { KeyRound, Sparkles } from '@lucide/vue';
import { computed, ref } from 'vue';
import DocsContent from '@/components/docs/DocsContent.vue';
import DocsPlayground from '@/components/docs/DocsPlayground.vue';
import DocsSidebar from '@/components/docs/DocsSidebar.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';

interface Endpoint {
    id: string;
    module: string;
    title: string;
    method: 'GET' | 'POST' | 'PATCH' | 'DELETE';
    path: string;
    description: string;
    params: any[];
    requestBody: any;
    response: any;
}

const props = defineProps<{
    endpoints: Endpoint[];
    baseUrl: string;
    isInApp?: boolean;
}>();

const selectedId = ref(props.endpoints[0]?.id || '');

const currentEndpoint = computed(() => {
    return props.endpoints.find((ep) => ep.id === selectedId.value) || props.endpoints[0];
});

const breadcrumbs = [
    {
        title: 'API Documentation',
        href: '/app/docs',
    },
];
</script>

<template>
    <Head title="REST API Reference - GrowthCoder" />

    <!-- IN-APP MODE (/app/docs): Uses AppLayout -->
    <AppLayout v-if="props.isInApp" :breadcrumbs="breadcrumbs">
        <div class="h-[calc(100vh-5.5rem)] overflow-hidden p-6 lg:p-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 h-full min-h-0">
                <!-- Column 1: Sidebar Nav (3 Cols on lg) -->
                <div class="lg:col-span-3 h-full overflow-hidden pr-2">
                    <DocsSidebar
                        :endpoints="props.endpoints"
                        :selected-endpoint-id="selectedId"
                        @select="(id) => (selectedId = id)"
                    />
                </div>

                <!-- Column 2: Content (5 Cols on lg) -->
                <div class="lg:col-span-5 h-full overflow-y-auto pr-4 min-w-0 scrollbar-thin">
                    <DocsContent v-if="currentEndpoint" :endpoint="currentEndpoint" />
                </div>

                <!-- Column 3: Interactive Playground (4 Cols on lg) -->
                <div class="lg:col-span-4 h-full overflow-y-auto pr-2 scrollbar-thin">
                    <DocsPlayground
                        v-if="currentEndpoint"
                        :endpoint="currentEndpoint"
                        :base-url="props.baseUrl"
                    />
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- PUBLIC MODE (/docs): Standalone Full Page without App Layout -->
    <div v-else class="min-h-screen bg-background text-foreground flex flex-col font-sans selection:bg-primary/20 selection:text-primary">
        <!-- Top Navigation Header -->
        <header class="sticky top-0 z-40 w-full border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-primary to-indigo-600 flex items-center justify-center text-primary-foreground shadow-md">
                        <Sparkles class="h-5 w-5" />
                    </div>
                    <div>
                        <span class="font-bold text-base tracking-tight block">GrowthCoder API</span>
                        <span class="text-[11px] text-muted-foreground font-mono">REST API Documentation v1.0</span>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <Button variant="outline" size="sm" as-child class="text-xs">
                        <Link href="/app/settings/api-tokens">
                            <KeyRound class="mr-1.5 h-3.5 w-3.5" />
                            Get API Token
                        </Link>
                    </Button>
                    <Button variant="default" size="sm" as-child class="text-xs">
                        <Link href="/app/dashboard">
                            Workspace Dashboard
                        </Link>
                    </Button>
                </div>
            </div>
        </header>

        <!-- Main 3-Column Body -->
        <div class="flex-1 container mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Column 1: Sidebar Nav (3 Cols on lg) -->
                <div class="lg:col-span-3 lg:sticky lg:top-22 lg:max-h-[calc(100vh-6rem)]">
                    <DocsSidebar
                        :endpoints="props.endpoints"
                        :selected-endpoint-id="selectedId"
                        @select="(id) => (selectedId = id)"
                    />
                </div>

                <!-- Column 2: Content (5 Cols on lg) -->
                <div class="lg:col-span-5 min-w-0">
                    <DocsContent v-if="currentEndpoint" :endpoint="currentEndpoint" />
                </div>

                <!-- Column 3: Interactive Playground (4 Cols on lg) -->
                <div class="lg:col-span-4 lg:sticky lg:top-22">
                    <DocsPlayground
                        v-if="currentEndpoint"
                        :endpoint="currentEndpoint"
                        :base-url="props.baseUrl"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

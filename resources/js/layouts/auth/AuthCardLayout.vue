<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Sun, Moon } from '@lucide/vue';
import { ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useAppearance } from '@/composables/useAppearance';
import AppLogo from '@/components/AppLogo.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const { resolvedAppearance, updateAppearance } = useAppearance();

const isMounted = ref(false);
onMounted(() => {
    isMounted.value = true;
});

const toggleAppearance = () => {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
};
</script>

<template>
    <div
        class="auth-bg-pattern relative flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden p-6 md:p-10"
    >
        <!-- Floating Theme Toggle -->
        <div class="absolute top-6 right-6 z-50">
            <Button
                variant="ghost"
                size="icon"
                class="h-9 w-9 rounded-lg transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-800"
                @click="toggleAppearance"
                title="Toggle appearance"
            >
                <template v-if="isMounted">
                    <Sun
                        v-if="resolvedAppearance === 'dark'"
                        class="h-5 w-5 text-yellow-500"
                    />
                    <Moon v-else class="h-5 w-5 text-neutral-600" />
                </template>
                <div v-else class="h-5 w-5" />
                <span class="sr-only">Toggle theme</span>
            </Button>
        </div>

        <div class="relative z-10 flex w-full max-w-md flex-col gap-6">
            <div class="flex flex-col gap-6">
                <Card class="rounded-xl border border-border bg-card shadow-md">
                    <CardHeader class="px-6 pt-8 pb-0 text-center sm:px-10">
                        <div class="mb-6 flex items-center justify-center">
                            <Link
                                :href="home()"
                                class="inline-flex items-center justify-center"
                            >
                                <AppLogo size="xl" />
                            </Link>
                        </div>
                        <CardTitle
                            class="text-xl font-semibold tracking-tight"
                            >{{ title }}</CardTitle
                        >
                        <CardDescription
                            class="mt-1.5 text-sm text-muted-foreground"
                        >
                            {{ description }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="px-6 py-8 sm:px-10">
                        <slot />
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>

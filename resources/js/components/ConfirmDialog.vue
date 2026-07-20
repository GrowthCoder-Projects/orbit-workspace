<script setup lang="ts">
import { AlertTriangle, Trash2, Info } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useConfirm } from '@/composables/useConfirm';

const { isOpen, options, handleConfirm, handleCancel } = useConfirm();

const iconComponent = computed(() => {
    switch (options.value.variant) {
        case 'destructive':
            return Trash2;
        case 'warning':
            return AlertTriangle;
        default:
            return Info;
    }
});

const themeClasses = computed(() => {
    switch (options.value.variant) {
        case 'destructive':
            return {
                iconBg: 'bg-red-500/10 text-red-600 dark:bg-red-500/15 dark:text-red-400',
                glow: 'shadow-red-500/10 dark:shadow-red-950/20',
                confirmBtn: 'destructive',
            };
        case 'warning':
            return {
                iconBg: 'bg-amber-500/10 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400',
                glow: 'shadow-amber-500/10 dark:shadow-amber-950/20',
                confirmBtn: 'default', // Fallback or warning style if button supports it
            };
        default:
            return {
                iconBg: 'bg-blue-500/10 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400',
                glow: 'shadow-blue-500/10 dark:shadow-blue-950/20',
                confirmBtn: 'default',
            };
    }
});
</script>

<template>
    <Dialog :open="isOpen" @update:open="(val) => !val && handleCancel()">
        <DialogContent 
            class="glass-panel max-w-[400px] border border-border shadow-2xl rounded-xl p-6 gap-6"
            :show-close-button="false"
        >
            <div class="flex flex-col items-center text-center gap-4">
                <!-- Icon container with glowing & pulsing animation -->
                <div 
                    class="p-4 rounded-full transition-all duration-300 shadow-lg flex items-center justify-center"
                    :class="[themeClasses.iconBg, themeClasses.glow]"
                >
                    <component 
                        :is="iconComponent" 
                        class="h-7 w-7 animate-duration-1000"
                        :class="options.variant === 'destructive' ? 'animate-pulse' : ''"
                    />
                </div>

                <div class="flex flex-col gap-2">
                    <DialogTitle class="text-xl font-bold tracking-tight text-foreground">
                        {{ options.title }}
                    </DialogTitle>
                    <DialogDescription class="text-sm text-muted-foreground leading-relaxed px-2">
                        {{ options.message }}
                    </DialogDescription>
                </div>
            </div>

            <DialogFooter class="flex flex-row items-center justify-center gap-3 sm:justify-center w-full">
                <Button 
                    variant="outline" 
                    class="flex-1 rounded-lg border-border hover:bg-muted text-foreground transition-all duration-200"
                    @click="handleCancel"
                >
                    {{ options.cancelText }}
                </Button>
                <Button 
                    :variant="themeClasses.confirmBtn as any" 
                    class="flex-1 rounded-lg font-semibold shadow-sm hover:shadow-md transition-all duration-200"
                    @click="handleConfirm"
                >
                    {{ options.confirmText }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

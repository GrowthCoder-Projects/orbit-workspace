<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { KeyRound, Trash2, Copy, Check, AlertCircle } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface ApiToken {
    id: number;
    name: string;
    abilities: string[];
    last_used_at: string;
    created_at: string;
}

const props = defineProps<{
    tokens: ApiToken[];
    plainTextToken?: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'API Tokens',
                href: '/app/settings/api-tokens',
            },
        ],
    },
});

const form = useForm({
    name: '',
});

const copied = ref(false);

const createToken = () => {
    form.post('/app/settings/api-tokens', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};

const deleteToken = (tokenId: number) => {
    if (confirm('Are you sure you want to delete this API token? Any applications using it will lose access.')) {
        router.delete(`/app/settings/api-tokens/${tokenId}`, {
            preserveScroll: true,
        });
    }
};

const copyToken = () => {
    if (props.plainTextToken) {
        navigator.clipboard.writeText(props.plainTextToken);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    }
};
</script>

<template>
    <Head title="API Tokens" />

    <h1 class="sr-only">API Tokens</h1>

    <div class="space-y-8">
        <!-- Banner display for newly created token -->
        <div
            v-if="props.plainTextToken"
            class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-5 space-y-3"
        >
            <div class="flex items-center space-x-2 text-emerald-600 dark:text-emerald-400 font-semibold">
                <AlertCircle class="h-5 w-5" />
                <span>API Token Created Successfully</span>
            </div>
            <p class="text-sm text-muted-foreground">
                Please copy your new API token now. For your security, it won't be shown again.
            </p>
            <div class="flex items-center space-x-2">
                <code class="flex-1 rounded bg-muted px-3 py-2 font-mono text-sm break-all border">
                    {{ props.plainTextToken }}
                </code>
                <Button type="button" variant="outline" size="sm" @click="copyToken">
                    <Check v-if="copied" class="mr-1 h-4 w-4 text-emerald-500" />
                    <Copy v-else class="mr-1 h-4 w-4" />
                    {{ copied ? 'Copied' : 'Copy' }}
                </Button>
            </div>
        </div>

        <!-- Create Token Section -->
        <div class="space-y-6">
            <Heading
                variant="small"
                title="Create API Token"
                description="API tokens allow external services (like Telegram/WhatsApp bots or custom apps) to authenticate with this workspace."
            />

            <form @submit.prevent="createToken" class="space-y-4 max-w-xl">
                <div class="grid gap-2">
                    <Label for="token_name">Token Name</Label>
                    <Input
                        id="token_name"
                        v-model="form.name"
                        placeholder="e.g. Telegram Bot, Zapier Integration"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <Button type="submit" :disabled="form.processing">
                    <KeyRound class="mr-2 h-4 w-4" />
                    Generate Token
                </Button>
            </form>
        </div>

        <!-- Manage Tokens Section -->
        <div class="space-y-6 pt-4 border-t">
            <Heading
                variant="small"
                title="Active API Tokens"
                description="Manage existing API tokens that have access to your account."
            />

            <div v-if="props.tokens.length === 0" class="text-sm text-muted-foreground py-4">
                You have not created any API tokens yet.
            </div>

            <div v-else class="space-y-3 max-w-2xl">
                <div
                    v-for="token in props.tokens"
                    :key="token.id"
                    class="flex items-center justify-between p-4 rounded-lg border bg-card"
                >
                    <div class="space-y-1">
                        <div class="font-medium flex items-center gap-2">
                            <KeyRound class="h-4 w-4 text-muted-foreground" />
                            <span>{{ token.name }}</span>
                        </div>
                        <div class="text-xs text-muted-foreground space-x-2">
                            <span>Created: {{ token.created_at }}</span>
                            <span>•</span>
                            <span>Last used: {{ token.last_used_at }}</span>
                        </div>
                    </div>

                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="text-destructive hover:text-destructive hover:bg-destructive/10"
                        @click="deleteToken(token.id)"
                    >
                        <Trash2 class="h-4 w-4" />
                        <span class="sr-only">Delete Token</span>
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>

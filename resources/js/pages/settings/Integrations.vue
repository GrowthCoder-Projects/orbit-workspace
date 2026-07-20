<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Send } from '@lucide/vue';
import { ref } from 'vue';
import IntegrationController from '@/actions/App/Http/Controllers/Settings/IntegrationController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { test } from '@/routes/integrations/telegram';

const props = defineProps<{
    telegramBotToken?: string;
    telegramChatId?: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Integrations',
                href: '/app/settings/integrations',
            },
        ],
    },
});

const isTesting = ref(false);

const testConnection = () => {
    isTesting.value = true;
    router.post(
        test.url(),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isTesting.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="Integrations" />

    <h1 class="sr-only">Integrations</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Telegram Notification Bot"
            description="Configure your Telegram bot credentials to receive real-time build and task reminders"
        />

        <Form
            v-bind="
                IntegrationController.updateTelegram.form({
                    telegram_bot_token: props.telegramBotToken ?? '',
                    telegram_chat_id: props.telegramChatId ?? '',
                })
            "
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="telegram_bot_token">Telegram Bot Token</Label>
                <Input
                    id="telegram_bot_token"
                    type="password"
                    class="mt-1 block w-full"
                    name="telegram_bot_token"
                    :default-value="props.telegramBotToken"
                    placeholder="e.g. 123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ"
                    autocomplete="off"
                />
                <InputError class="mt-2" :message="errors.telegram_bot_token" />
            </div>

            <div class="grid gap-2">
                <Label for="telegram_chat_id">Telegram Chat ID</Label>
                <Input
                    id="telegram_chat_id"
                    class="mt-1 block w-full"
                    name="telegram_chat_id"
                    :default-value="props.telegramChatId"
                    placeholder="e.g. -100123456789 or 987654321"
                    autocomplete="off"
                />
                <InputError class="mt-2" :message="errors.telegram_chat_id" />
                <p class="mt-1 text-xs text-muted-foreground">
                    You can obtain your chat ID by messaging
                    <strong>@userinfobot</strong> or adding your bot to a group
                    and using a bot info reader.
                </p>
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing">Save settings</Button>

                <Button
                    type="button"
                    variant="outline"
                    :disabled="
                        isTesting ||
                        !props.telegramBotToken ||
                        !props.telegramChatId
                    "
                    @click="testConnection"
                >
                    <span
                        v-if="isTesting"
                        class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"
                    />
                    <Send v-else class="mr-2 h-4 w-4" />
                    Send Test Notification
                </Button>
            </div>
        </Form>
    </div>
</template>

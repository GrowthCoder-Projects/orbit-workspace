<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Appearance settings',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
// Access the globally shared settings
const defaultEditor = computed(
    () => (page.props.settings as any)?.notes_editor || 'tiptap',
);

const form = useForm({
    editor: defaultEditor.value,
});

watch(defaultEditor, (newVal) => {
    form.editor = newVal;
});

const submitEditorSetting = () => {
    form.patch('/settings/editor', {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Default notes editor updated successfully.');
        },
        onError: () => {
            toast.error('Failed to update editor preference.');
        },
    });
};
</script>

<template>
    <Head title="Appearance settings" />

    <h1 class="sr-only">Appearance settings</h1>

    <div class="space-y-8">
        <div>
            <Heading
                variant="small"
                title="Appearance settings"
                description="Update the appearance settings for your account"
            />
            <div class="mt-4">
                <AppearanceTabs />
            </div>
        </div>

        <Separator class="my-6" />

        <div class="space-y-6">
            <Heading
                variant="small"
                title="Notes Editor Preference"
                description="Choose which rich text editor you prefer to use by default in the Notes workspace"
            />

            <form
                @submit.prevent="submitEditorSetting"
                class="max-w-md space-y-4"
            >
                <div class="space-y-2">
                    <Label for="default-editor">Default Notes Editor</Label>
                    <Select v-model="form.editor">
                        <SelectTrigger
                            id="default-editor"
                            class="w-full border bg-white dark:border-neutral-800 dark:bg-neutral-900"
                        >
                            <SelectValue placeholder="Select editor" />
                        </SelectTrigger>
                        <SelectContent
                            class="border bg-white dark:border-neutral-800 dark:bg-neutral-900"
                        >
                            <SelectItem value="tiptap"
                                >TipTap (Modern, customizable,
                                markdown-feel)</SelectItem
                            >
                            <SelectItem value="ckeditor"
                                >CKEditor 5 (Classic, feature-rich word
                                processor)</SelectItem
                            >
                        </SelectContent>
                    </Select>
                </div>

                <Button type="submit" :disabled="form.processing">
                    Save Preference
                </Button>
            </form>
        </div>
    </div>
</template>

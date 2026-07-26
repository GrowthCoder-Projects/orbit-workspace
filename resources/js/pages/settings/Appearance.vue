<script setup lang="ts">
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Upload, RotateCcw, Image as ImageIcon } from '@lucide/vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
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

// Shared settings
const defaultEditor = computed(
    () => (page.props.settings as any)?.notes_editor || 'tiptap',
);
const appLogo = computed(
    () => (page.props.settings as any)?.app_logo || '/storage/logo/logo-orbit.png',
);
const isCustomLogo = computed(
    () => !!(page.props.settings as any)?.is_custom_logo,
);

// Editor Form
const editorForm = useForm({
    editor: defaultEditor.value,
});

watch(defaultEditor, (newVal) => {
    editorForm.editor = newVal;
});

const submitEditorSetting = () => {
    editorForm.patch('/app/settings/editor', {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Default notes editor updated successfully.');
        },
        onError: () => {
            toast.error('Failed to update editor preference.');
        },
    });
};

// Logo Form & Preview
const logoForm = useForm({
    logo: null as File | null,
});
const previewUrl = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        logoForm.logo = file;
        previewUrl.value = URL.createObjectURL(file);
    }
};

const submitLogoSetting = () => {
    if (!logoForm.logo) {
        toast.error('Please select an image file first.');
        return;
    }

    logoForm.post('/app/settings/logo', {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('App logo updated successfully.');
            previewUrl.value = null;
            if (fileInputRef.value) {
                fileInputRef.value.value = '';
            }
        },
        onError: (errors) => {
            toast.error(errors.logo || 'Failed to update app logo.');
        },
    });
};

const resetLogo = () => {
    if (confirm('Are you sure you want to reset the logo to the default logo-orbit.png?')) {
        router.delete('/app/settings/logo', {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('App logo reset to default.');
                previewUrl.value = null;
                if (fileInputRef.value) {
                    fileInputRef.value.value = '';
                }
            },
            onError: () => {
                toast.error('Failed to reset app logo.');
            },
        });
    }
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
                description="Update the theme and visual identity settings for your workspace"
            />
            <div class="mt-4">
                <AppearanceTabs />
            </div>
        </div>

        <Separator class="my-6" />

        <!-- Logo Setting Section -->
        <div class="space-y-6">
            <Heading
                variant="small"
                title="App / Company Logo"
                description="Upload a custom logo for your workspace. By default, logo-orbit.png is used."
            />

            <div class="max-w-md space-y-6">
                <!-- Preview -->
                <div class="space-y-2">
                    <Label>Current Logo Preview</Label>
                    <div class="flex items-center gap-4 p-4 rounded-lg border bg-neutral-50 dark:bg-neutral-900 dark:border-neutral-800">
                        <div class="p-3 bg-white dark:bg-neutral-950 rounded border dark:border-neutral-800 flex items-center justify-center min-w-[120px] h-16">
                            <img
                                :src="previewUrl || appLogo"
                                alt="Logo Preview"
                                class="max-h-12 w-auto object-contain"
                            />
                        </div>
                        <div class="text-xs text-muted-foreground space-y-1">
                            <p class="font-medium text-foreground">
                                {{ previewUrl ? 'New Logo Selected' : (isCustomLogo ? 'Custom Logo Active' : 'Default (logo-orbit.png)') }}
                            </p>
                            <p>Recommended format: PNG, SVG, WEBP (Max: 2MB)</p>
                        </div>
                    </div>
                </div>

                <!-- Upload Form -->
                <form @submit.prevent="submitLogoSetting" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="logo-upload">Choose New Logo File</Label>
                        <Input
                            id="logo-upload"
                            type="file"
                            ref="fileInputRef"
                            accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml"
                            @change="handleFileChange"
                            class="cursor-pointer bg-white dark:bg-neutral-900"
                        />
                    </div>

                    <div class="flex items-center gap-3">
                        <Button
                            type="submit"
                            :disabled="logoForm.processing || !logoForm.logo"
                            class="gap-2"
                        >
                            <Upload class="h-4 w-4" />
                            Upload Logo
                        </Button>

                        <Button
                            v-if="isCustomLogo"
                            type="button"
                            variant="outline"
                            @click="resetLogo"
                            class="gap-2 text-destructive hover:text-destructive"
                        >
                            <RotateCcw class="h-4 w-4" />
                            Reset to Default
                        </Button>
                    </div>
                </form>
            </div>
        </div>

        <Separator class="my-6" />

        <!-- Notes Editor Preference -->
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
                    <Select v-model="editorForm.editor">
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

                <Button type="submit" :disabled="editorForm.processing">
                    Save Preference
                </Button>
            </form>
        </div>
    </div>
</template>

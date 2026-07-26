<script setup lang="ts">
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import { Ckeditor as CkeditorComponent } from '@ckeditor/ckeditor5-vue';
import { ref, watch, onMounted } from 'vue';

const props = defineProps<{
    modelValue: string;
    placeholder?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'saving'): void;
    (e: 'saved'): void;
}>();

const editorData = ref(props.modelValue);
const editor = ClassicEditor;

// Custom Upload Adapter for CKEditor 5
class ImageUploadAdapter {
    constructor(private loader: any) {}

    upload() {
        return this.loader.file.then((file: File) => {
            return new Promise((resolve, reject) => {
                emit('saving');
                const formData = new FormData();
                formData.append('image', file);

                fetch('/app/notes/upload-image', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN':
                            document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute('content') || '',
                    },
                })
                    .then((response) => response.json())
                    .then((result) => {
                        emit('saved');

                        if (result.url) {
                            resolve({ default: result.url });
                        } else {
                            reject(result.error || 'Upload failed');
                        }
                    })
                    .catch((err) => {
                        emit('saved');
                        reject(err);
                    });
            });
        });
    }

    abort() {
        // Handle abort
    }
}

// Register custom upload adapter plugin
function CustomUploadAdapterPlugin(editorInstance: any) {
    editorInstance.plugins.get('FileRepository').createUploadAdapter = (
        loader: any,
    ) => {
        return new ImageUploadAdapter(loader);
    };
}

const editorConfig = {
    placeholder: props.placeholder || 'Start writing your note...',
    extraPlugins: [CustomUploadAdapterPlugin],
    toolbar: {
        items: [
            'heading',
            '|',
            'bold',
            'italic',
            'link',
            'bulletedList',
            'numberedList',
            '|',
            'imageUpload',
            'insertTable',
            'blockQuote',
            '|',
            'undo',
            'redo',
        ],
    },
    table: {
        contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'],
    },
};

// Sync internal editorData when modelValue changes
watch(
    () => props.modelValue,
    (newValue) => {
        if (newValue !== editorData.value) {
            editorData.value = newValue;
        }
    },
);

// Emit update when internal data changes
watch(editorData, (newValue) => {
    emit('update:modelValue', newValue);
});
</script>

<template>
    <div
        class="ck-editor-container overflow-hidden rounded-lg border bg-white dark:bg-neutral-900"
    >
        <ckeditor-component
            :editor="editor"
            v-model="editorData"
            :config="editorConfig"
        />
    </div>
</template>

<style>
/* Custom styled styling to match dark/light mode for CKEditor */
.ck-editor-container .ck-editor__editable {
    min-height: 400px;
    padding: 1rem 1.5rem !important;
    border: none !important;
    background-color: transparent !important;
}

.ck-editor-container .ck-toolbar {
    background-color: var(--color-neutral-50) !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
    border-bottom: 1px solid var(--color-neutral-200) !important;
}

.dark .ck-editor-container .ck-toolbar {
    background-color: #171717 !important;
    border-color: #262626 !important;
}

.dark .ck-editor-container .ck-toolbar__items button:hover {
    background-color: #262626 !important;
}

.dark .ck-editor-container .ck-button {
    color: #e5e5e5 !important;
}

.dark .ck-editor-container .ck-button:hover {
    background-color: #262626 !important;
}

.dark .ck-editor-container .ck-button.ck-on {
    background-color: #262626 !important;
    color: #ffffff !important;
}

.dark .ck-editor-container .ck-dropdown__panel {
    background-color: #171717 !important;
    border-color: #262626 !important;
}

.dark .ck-editor-container .ck-list {
    background-color: #171717 !important;
}

.dark .ck-editor-container .ck-list__item button {
    color: #e5e5e5 !important;
}

.dark .ck-editor-container .ck-list__item button:hover {
    background-color: #262626 !important;
}

.dark .ck-editor-container {
    border-color: #262626 !important;
    background-color: #0a0a0a !important;
}

.dark .ck-editor__editable {
    color: #f5f5f5 !important;
}
</style>

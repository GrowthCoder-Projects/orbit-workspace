<script setup lang="ts">
import {
    Bold,
    Italic,
    List,
    ListOrdered,
    SquareCheck,
    Code,
    Table as TableIcon,
    Image as ImageIcon,
    Undo,
    Redo,
    Heading1,
    Heading2,
    Heading3,
    Trash2,
} from '@lucide/vue';
import { CodeBlockLowlight } from '@tiptap/extension-code-block-lowlight';
import { Image } from '@tiptap/extension-image';
import { Table } from '@tiptap/extension-table';
import { TableCell } from '@tiptap/extension-table-cell';
import { TableHeader } from '@tiptap/extension-table-header';
import { TableRow } from '@tiptap/extension-table-row';
import { TaskItem } from '@tiptap/extension-task-item';
import { TaskList } from '@tiptap/extension-task-list';
import StarterKit from '@tiptap/starter-kit';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import { common, createLowlight } from 'lowlight';
import { onBeforeUnmount, watch } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    modelValue: string;
    placeholder?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'saving'): void;
    (e: 'saved'): void;
}>();

// Initialize syntax highlighter
const lowlight = createLowlight(common);

// Handle image upload helper
const uploadImageFile = async (file: File): Promise<string | null> => {
    const formData = new FormData();
    formData.append('image', file);

    try {
        emit('saving');
        const response = await fetch('/app/notes/upload-image', {
            method: 'POST',
            body: formData,
            headers: {
                // CSRF Token is fetched from meta tag if available
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') || '',
            },
        });
        const data = await response.json();
        emit('saved');

        return data.url || null;
    } catch (error) {
        console.error('Image upload failed:', error);
        emit('saved');

        return null;
    }
};

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            codeBlock: false, // disabled in favor of codeBlockLowlight
        }),
        Table.configure({
            resizable: true,
        }),
        TableRow,
        TableHeader,
        TableCell,
        TaskList,
        TaskItem.configure({
            nested: true,
        }),
        Image.configure({
            allowBase64: false,
            HTMLAttributes: {
                class: 'max-w-full h-auto rounded-lg border my-4',
            },
        }),
        CodeBlockLowlight.configure({
            lowlight,
            HTMLAttributes: {
                class: 'rounded-md bg-neutral-900 text-neutral-100 p-4 font-mono text-sm my-4 overflow-x-auto',
            },
        }),
    ],
    editorProps: {
        attributes: {
            class: 'prose dark:prose-invert max-w-none focus:outline-none min-h-[400px] px-6 py-4',
        },
        handleDrop(view, event) {
            if (
                event.dataTransfer &&
                event.dataTransfer.files &&
                event.dataTransfer.files.length
            ) {
                const file = event.dataTransfer.files[0];

                if (file.type.startsWith('image/')) {
                    event.preventDefault();
                    uploadImageFile(file).then((url) => {
                        if (url && editor.value) {
                            editor.value
                                .chain()
                                .focus()
                                .setImage({ src: url })
                                .run();
                        }
                    });

                    return true;
                }
            }

            return false;
        },
        handlePaste(view, event) {
            if (
                event.clipboardData &&
                event.clipboardData.files &&
                event.clipboardData.files.length
            ) {
                const file = event.clipboardData.files[0];

                if (file.type.startsWith('image/')) {
                    event.preventDefault();
                    uploadImageFile(file).then((url) => {
                        if (url && editor.value) {
                            editor.value
                                .chain()
                                .focus()
                                .setImage({ src: url })
                                .run();
                        }
                    });

                    return true;
                }
            }

            return false;
        },
    },
    onUpdate: () => {
        if (editor.value) {
            emit('update:modelValue', editor.value.getHTML());
        }
    },
});

// Sync modelValue from parent to editor (for note switches)
watch(
    () => props.modelValue,
    (newValue) => {
        if (editor.value && editor.value.getHTML() !== newValue) {
            editor.value.commands.setContent(newValue, false);
        }
    },
);

onBeforeUnmount(() => {
    if (editor.value) {
        editor.value.destroy();
    }
});

// Toolbar helper actions
const triggerImageUpload = () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = async () => {
        if (input.files && input.files[0]) {
            const url = await uploadImageFile(input.files[0]);

            if (url && editor.value) {
                editor.value.chain().focus().setImage({ src: url }).run();
            }
        }
    };
    input.click();
};

const insertTable = () => {
    if (editor.value) {
        editor.value
            .chain()
            .focus()
            .insertTable({ rows: 3, cols: 3, withHeaderRow: true })
            .run();
    }
};
</script>

<template>
    <div
        v-if="editor"
        class="dark:bg-neutral-905 flex flex-col overflow-hidden rounded-lg border bg-white"
    >
        <!-- Toolbar -->
        <div
            class="flex flex-wrap items-center gap-1 border-b bg-neutral-50 p-2 select-none dark:bg-neutral-900"
        >
            <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                :class="{
                    'bg-neutral-200 dark:bg-neutral-800':
                        editor.isActive('bold'),
                }"
                @click="editor.chain().focus().toggleBold().run()"
                title="Bold"
            >
                <Bold class="h-4 w-4" />
            </Button>
            <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                :class="{
                    'bg-neutral-200 dark:bg-neutral-800':
                        editor.isActive('italic'),
                }"
                @click="editor.chain().focus().toggleItalic().run()"
                title="Italic"
            >
                <Italic class="h-4 w-4" />
            </Button>

            <span
                class="mx-1 h-4 w-[1px] bg-neutral-300 dark:bg-neutral-700"
            ></span>

            <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                :class="{
                    'bg-neutral-200 dark:bg-neutral-800': editor.isActive(
                        'heading',
                        { level: 1 },
                    ),
                }"
                @click="
                    editor.chain().focus().toggleHeading({ level: 1 }).run()
                "
                title="Heading 1"
            >
                <Heading1 class="h-4 w-4" />
            </Button>
            <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                :class="{
                    'bg-neutral-200 dark:bg-neutral-800': editor.isActive(
                        'heading',
                        { level: 2 },
                    ),
                }"
                @click="
                    editor.chain().focus().toggleHeading({ level: 2 }).run()
                "
                title="Heading 2"
            >
                <Heading2 class="h-4 w-4" />
            </Button>
            <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                :class="{
                    'bg-neutral-200 dark:bg-neutral-800': editor.isActive(
                        'heading',
                        { level: 3 },
                    ),
                }"
                @click="
                    editor.chain().focus().toggleHeading({ level: 3 }).run()
                "
                title="Heading 3"
            >
                <Heading3 class="h-4 w-4" />
            </Button>

            <span
                class="mx-1 h-4 w-[1px] bg-neutral-300 dark:bg-neutral-700"
            ></span>

            <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                :class="{
                    'bg-neutral-200 dark:bg-neutral-800':
                        editor.isActive('bulletList'),
                }"
                @click="editor.chain().focus().toggleBulletList().run()"
                title="Bullet List"
            >
                <List class="h-4 w-4" />
            </Button>
            <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                :class="{
                    'bg-neutral-200 dark:bg-neutral-800':
                        editor.isActive('orderedList'),
                }"
                @click="editor.chain().focus().toggleOrderedList().run()"
                title="Ordered List"
            >
                <ListOrdered class="h-4 w-4" />
            </Button>
            <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                :class="{
                    'bg-neutral-200 dark:bg-neutral-800':
                        editor.isActive('taskList'),
                }"
                @click="editor.chain().focus().toggleTaskList().run()"
                title="Task/Checklist"
            >
                <SquareCheck class="h-4 w-4" />
            </Button>

            <span
                class="mx-1 h-4 w-[1px] bg-neutral-300 dark:bg-neutral-700"
            ></span>

            <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                :class="{
                    'bg-neutral-200 dark:bg-neutral-800':
                        editor.isActive('codeBlock'),
                }"
                @click="editor.chain().focus().toggleCodeBlock().run()"
                title="Code Block"
            >
                <Code class="h-4 w-4" />
            </Button>
            <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                @click="insertTable"
                title="Insert Table"
            >
                <TableIcon class="h-4 w-4" />
            </Button>
            <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                @click="triggerImageUpload"
                title="Insert Image"
            >
                <ImageIcon class="h-4 w-4" />
            </Button>

            <span
                class="mx-1 h-4 w-[1px] bg-neutral-300 dark:bg-neutral-700"
            ></span>

            <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                @click="editor.chain().focus().undo().run()"
                :disabled="!editor.can().undo()"
                title="Undo"
            >
                <Undo class="h-4 w-4" />
            </Button>
            <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                @click="editor.chain().focus().redo().run()"
                :disabled="!editor.can().redo()"
                title="Redo"
            >
                <Redo class="h-4 w-4" />
            </Button>

            <div
                v-if="editor.isActive('table')"
                class="ml-auto flex items-center gap-1 border-l pl-2"
            >
                <Button
                    variant="ghost"
                    size="sm"
                    class="h-7 px-2 text-xs text-red-500 hover:bg-red-50 hover:text-red-700"
                    @click="editor.chain().focus().deleteTable().run()"
                >
                    <Trash2 class="mr-1 h-3 w-3" /> Table
                </Button>
                <Button
                    variant="ghost"
                    size="sm"
                    class="h-7 px-2 text-xs"
                    @click="editor.chain().focus().addColumnAfter().run()"
                >
                    + Col
                </Button>
                <Button
                    variant="ghost"
                    size="sm"
                    class="h-7 px-2 text-xs"
                    @click="editor.chain().focus().addRowAfter().run()"
                >
                    + Row
                </Button>
            </div>
        </div>

        <!-- Editor Content -->
        <div
            class="flex-1 overflow-y-auto bg-neutral-50/50 dark:bg-neutral-900/10"
        >
            <EditorContent :editor="editor" />
        </div>
    </div>
</template>

<style>
/* Additional TipTap styling details */
.ProseMirror p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    color: #adb5bd;
    pointer-events: none;
    height: 0;
}

.ProseMirror table {
    border-collapse: collapse;
    table-layout: fixed;
    width: 100%;
    margin: 1.5rem 0;
    overflow: hidden;
}

.ProseMirror td,
.ProseMirror th {
    min-width: 1em;
    border: 1px solid #ced4da;
    padding: 8px 12px;
    vertical-align: top;
    box-sizing: border-box;
    position: relative;
}

.ProseMirror th {
    font-weight: bold;
    text-align: left;
    background-color: #f1f3f5;
}

.dark .ProseMirror th {
    background-color: #262626;
    border-color: #404040;
}

.dark .ProseMirror td {
    border-color: #404040;
}

.ProseMirror ul[data-type='taskList'] {
    list-style: none;
    padding: 0;
}

.ProseMirror ul[data-type='taskList'] li {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.ProseMirror ul[data-type='taskList'] input[type='checkbox'] {
    margin-top: 0.25rem;
    cursor: pointer;
}
</style>

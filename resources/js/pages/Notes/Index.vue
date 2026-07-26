<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import {
    Folder,
    FileText,
    Plus,
    Search,
    Star,
    Archive,
    Trash2,
    Edit2,
    ChevronRight,
    Settings,
    MoreVertical,
    FileEdit,
    Link2,
    Calendar,
    ArrowLeft,
    Check,
} from '@lucide/vue';
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { toast } from 'vue-sonner';
import CkEditor from '@/components/CkEditor.vue';
import TiptapEditor from '@/components/TiptapEditor.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { useConfirm } from '@/composables/useConfirm';

const { confirm } = useConfirm();

type FolderType = {
    id: number;
    name: string;
    parent_id: number | null;
    color: string | null;
    notes_count?: number;
    created_at: string;
};

type BacklinkType = {
    id: number;
    title: string;
    updated_at: string;
};

type NoteType = {
    id: number;
    title: string;
    content: string | null;
    folder_id: number | null;
    is_favorite: boolean;
    is_archived: boolean;
    snippet?: string;
    updated_at: string;
    backlinks?: BacklinkType[];
    outgoingLinks?: NoteType[];
};

const props = defineProps<{
    folders: FolderType[];
    notes: NoteType[];
    defaultEditor: string;
    selectedNoteId?: number | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Notes', href: '/app/notes' }],
    },
});

// State variables
const selectedNoteId = ref<number | null>(props.selectedNoteId || null);
const activeFilter = ref<'all' | 'favorite' | 'archive' | number>('all'); // number represents folder_id
const searchQuery = ref('');
const sortBy = ref<'updated' | 'title'>('updated');
const editorType = ref(props.defaultEditor || 'tiptap');

// Modal / Dialog States
const isFolderDialogOpen = ref(false);
const isEditingFolder = ref(false);
const editingFolderId = ref<number | null>(null);
const folderForm = ref({
    name: '',
    color: '',
});

// Auto-save & Status States
const isSaving = ref(false);
const saveStatusText = ref('All changes saved');
let saveTimeout: any = null;

// Active selected Note object
const activeNote = computed(() => {
    if (selectedNoteId.value === null) {
return null;
}

    return props.notes.find((n) => n.id === selectedNoteId.value) || null;
});

// Sync selection when initial prop load happens
onMounted(() => {
    if (selectedNoteId.value === null && props.notes.length > 0) {
        // Select the first active/non-archived note by default
        const defaultNote = props.notes.find((n) => !n.is_archived);

        if (defaultNote) {
            selectedNoteId.value = defaultNote.id;
        }
    }
});

// Watch URL parameter changes to set selection
watch(
    () => props.selectedNoteId,
    (newVal) => {
        if (newVal !== undefined) {
            selectedNoteId.value = newVal;
        }
    },
);

// Filtered and Sorted Note list
const filteredNotes = computed(() => {
    let result = props.notes;

    // Filter by Folder/Tags
    if (activeFilter.value === 'favorite') {
        result = result.filter((n) => n.is_favorite);
    } else if (activeFilter.value === 'archive') {
        result = result.filter((n) => n.is_archived);
    } else if (typeof activeFilter.value === 'number') {
        result = result.filter((n) => n.folder_id === activeFilter.value);
    } else {
        // 'all' -> show only non-archived notes by default
        result = result.filter((n) => !n.is_archived);
    }

    // Filter by Search Query
    if (searchQuery.value.trim() !== '') {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (n) =>
                n.title.toLowerCase().includes(query) ||
                (n.content && n.content.toLowerCase().includes(query)),
        );
    }

    // Sorting
    return result.sort((a, b) => {
        if (sortBy.value === 'title') {
            return a.title.localeCompare(b.title);
        }

        return (
            new Date(b.updated_at).getTime() - new Date(a.updated_at).getTime()
        );
    });
});

// Selection handlers
const selectNote = (noteId: number) => {
    selectedNoteId.value = noteId;
    router.replace({
        url: `/app/notes?id=${noteId}`,
        preserveState: true,
        preserveScroll: true,
    });
};

const filterBy = (filter: 'all' | 'favorite' | 'archive' | number) => {
    activeFilter.value = filter;
};

// CRUD Operations: Note
const createNote = () => {
    const folderId =
        typeof activeFilter.value === 'number' ? activeFilter.value : null;

    router.post(
        '/app/notes',
        {
            folder_id: folderId,
            title: 'Untitled Note',
        },
        {
            onSuccess: (page) => {
                toast.success('Note created successfully.');

                // Auto-select the newly created note (returned in props.selectedNoteId via controller)
                if (props.selectedNoteId) {
                    selectedNoteId.value = props.selectedNoteId;
                }
            },
        },
    );
};

const deleteNote = async (note: NoteType) => {
    const isConfirmed = await confirm({
        title: 'Delete Note',
        message: `Are you sure you want to delete "${note.title || 'Untitled'}"?`,
        confirmText: 'Delete',
        cancelText: 'Cancel',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(`/app/notes/${note.id}`, {
            onSuccess: () => {
                toast.success('Note deleted successfully.');
                selectedNoteId.value = null;

                // Try selecting next note
                if (filteredNotes.value.length > 0) {
                    selectedNoteId.value = filteredNotes.value[0].id;
                }
            },
        });
    }
};

const toggleFavorite = async (note: NoteType) => {
    note.is_favorite = !note.is_favorite;

    try {
        await fetch(`/app/notes/${note.id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                title: note.title,
                is_favorite: note.is_favorite,
            }),
        });
        toast.success(
            note.is_favorite ? 'Added to favorites' : 'Removed from favorites',
        );
    } catch (e) {
        note.is_favorite = !note.is_favorite; // Rollback
        toast.error('Failed to update favorite status.');
    }
};

const toggleArchive = async (note: NoteType) => {
    note.is_archived = !note.is_archived;

    try {
        await fetch(`/app/notes/${note.id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                title: note.title,
                is_archived: note.is_archived,
            }),
        });
        toast.success(note.is_archived ? 'Note archived' : 'Note restored');

        // If note is archived and we are in 'all' filter, select another note
        if (note.is_archived && activeFilter.value === 'all') {
            selectedNoteId.value = null;

            if (filteredNotes.value.length > 0) {
                selectedNoteId.value = filteredNotes.value[0].id;
            }
        }
    } catch (e) {
        note.is_archived = !note.is_archived; // Rollback
        toast.error('Failed to update archive status.');
    }
};

// Editor Preference Switcher
const updateEditorType = async (type: 'tiptap' | 'ckeditor') => {
    editorType.value = type;

    try {
        await fetch('/app/settings/editor', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') || '',
            },
            body: JSON.stringify({ editor: type }),
        });
        toast.success(
            `Switched default editor to ${type === 'tiptap' ? 'TipTap' : 'CKEditor 5'}`,
        );
    } catch (e) {
        console.error('Failed to save editor preference.', e);
    }
};

// Debounced Auto-Save
const handleNoteChange = () => {
    if (!activeNote.value) {
return;
}

    saveStatusText.value = 'Saving changes...';
    isSaving.value = true;

    if (saveTimeout) {
clearTimeout(saveTimeout);
}

    const noteId = activeNote.value.id;
    const title = activeNote.value.title;
    const content = activeNote.value.content;

    saveTimeout = setTimeout(async () => {
        try {
            await fetch(`/app/notes/${noteId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || '',
                },
                body: JSON.stringify({ title, content }),
            });

            // Update snippet locally in UI
            const localNote = props.notes.find((n) => n.id === noteId);

            if (localNote) {
                localNote.title = title;
                localNote.content = content;
                localNote.snippet = (content ?? '')
                    .replace(/<[^>]*>/g, '')
                    .substring(0, 120);
                localNote.updated_at = new Date().toISOString();
            }

            saveStatusText.value = 'All changes saved';

            // Reload notes list silently to update backlinks relationships
            router.reload({
                only: ['notes'],
                preserveScroll: true,
                preserveState: true,
            });
        } catch (error) {
            console.error('Auto-save error:', error);
            saveStatusText.value = 'Failed to auto-save';
        } finally {
            isSaving.value = false;
        }
    }, 800);
};

// Watch content / title edits inside editor
watch(
    () => activeNote.value?.content,
    () => {
        handleNoteChange();
    },
);

watch(
    () => activeNote.value?.title,
    () => {
        handleNoteChange();
    },
);

// Folder Operations
const openNewFolderDialog = () => {
    isEditingFolder.value = false;
    folderForm.value = { name: '', color: '#3b82f6' };
    isFolderDialogOpen.value = true;
};

const openEditFolderDialog = (folder: FolderType) => {
    isEditingFolder.value = true;
    editingFolderId.value = folder.id;
    folderForm.value = { name: folder.name, color: folder.color || '#3b82f6' };
    isFolderDialogOpen.value = true;
};

const saveFolder = () => {
    if (isEditingFolder.value && editingFolderId.value) {
        router.patch(`/app/folders/${editingFolderId.value}`, folderForm.value, {
            onSuccess: () => {
                toast.success('Folder updated successfully.');
                isFolderDialogOpen.value = false;
            },
        });
    } else {
        router.post('/app/folders', folderForm.value, {
            onSuccess: () => {
                toast.success('Folder created successfully.');
                isFolderDialogOpen.value = false;
            },
        });
    }
};

const deleteFolder = async (folder: FolderType) => {
    const isConfirmed = await confirm({
        title: 'Delete Folder',
        message: `Are you sure you want to delete folder "${folder.name}"? All notes inside will be Uncategorized.`,
        confirmText: 'Delete',
        cancelText: 'Cancel',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(`/app/folders/${folder.id}`, {
            onSuccess: () => {
                toast.success('Folder deleted successfully.');

                if (activeFilter.value === folder.id) {
                    activeFilter.value = 'all';
                }
            },
        });
    }
};

// Helper: format date
const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);

    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Notes" />

    <div
        class="flex h-[calc(100vh-4rem)] w-full overflow-hidden bg-neutral-50 dark:bg-neutral-950"
    >
        <!-- 3-Pane Layout -->

        <!-- Left Pane: Folders & Sidebar (Width: 64) -->
        <aside
            class="hidden w-64 flex-col overflow-y-auto border-r border-neutral-200 bg-white md:flex dark:border-neutral-800 dark:bg-neutral-900"
        >
            <div class="flex items-center justify-between p-4">
                <h2
                    class="text-xs font-semibold tracking-wider text-neutral-400 uppercase dark:text-neutral-500"
                >
                    Notes Workspace
                </h2>
                <div class="flex gap-1">
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-6 w-6"
                        @click="openNewFolderDialog"
                        title="New Folder"
                    >
                        <Plus class="h-3.5 w-3.5" />
                    </Button>
                </div>
            </div>

            <!-- Quick Navigation Filters -->
            <div class="space-y-0.5 px-2">
                <button
                    @click="filterBy('all')"
                    class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition-colors"
                    :class="[
                        activeFilter === 'all'
                            ? 'bg-neutral-100 text-neutral-900 dark:bg-neutral-800 dark:text-neutral-50'
                            : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800/50 dark:hover:text-neutral-50',
                    ]"
                >
                    <div class="flex items-center">
                        <FileText
                            class="mr-2.5 h-4 w-4 text-neutral-400 dark:text-neutral-500"
                        />
                        All Notes
                    </div>
                    <Badge
                        variant="secondary"
                        class="h-5 bg-neutral-100 text-xs font-normal dark:bg-neutral-800"
                    >
                        {{ notes.filter((n) => !n.is_archived).length }}
                    </Badge>
                </button>

                <button
                    @click="filterBy('favorite')"
                    class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition-colors"
                    :class="[
                        activeFilter === 'favorite'
                            ? 'bg-neutral-100 text-neutral-900 dark:bg-neutral-800 dark:text-neutral-50'
                            : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800/50 dark:hover:text-neutral-50',
                    ]"
                >
                    <div class="flex items-center">
                        <Star
                            class="mr-2.5 h-4 w-4 text-amber-400 dark:text-amber-500"
                        />
                        Favorites
                    </div>
                    <Badge
                        variant="secondary"
                        class="h-5 bg-neutral-100 text-xs font-normal dark:bg-neutral-800"
                    >
                        {{ notes.filter((n) => n.is_favorite).length }}
                    </Badge>
                </button>

                <button
                    @click="filterBy('archive')"
                    class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition-colors"
                    :class="[
                        activeFilter === 'archive'
                            ? 'bg-neutral-100 text-neutral-900 dark:bg-neutral-800 dark:text-neutral-50'
                            : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800/50 dark:hover:text-neutral-50',
                    ]"
                >
                    <div class="flex items-center">
                        <Archive
                            class="mr-2.5 h-4 w-4 text-neutral-400 dark:text-neutral-500"
                        />
                        Archived
                    </div>
                    <Badge
                        variant="secondary"
                        class="h-5 bg-neutral-100 text-xs font-normal dark:bg-neutral-800"
                    >
                        {{ notes.filter((n) => n.is_archived).length }}
                    </Badge>
                </button>
            </div>

            <Separator class="my-4" />

            <!-- Folders List -->
            <div class="flex-1 space-y-4 px-2">
                <div>
                    <div class="mb-1.5 flex items-center justify-between px-3">
                        <span
                            class="text-[10px] font-bold tracking-widest text-neutral-400 uppercase dark:text-neutral-500"
                            >Folders</span
                        >
                    </div>

                    <div class="space-y-0.5">
                        <div
                            v-for="folder in folders"
                            :key="folder.id"
                            class="group relative flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition-colors"
                            :class="[
                                activeFilter === folder.id
                                    ? 'bg-neutral-100 text-neutral-900 dark:bg-neutral-800 dark:text-neutral-50'
                                    : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800/50 dark:hover:text-neutral-50',
                            ]"
                        >
                            <button
                                @click="filterBy(folder.id)"
                                class="flex min-w-0 flex-1 items-center text-left"
                            >
                                <Folder
                                    class="mr-2.5 h-4 w-4 shrink-0"
                                    :style="
                                        folder.color
                                            ? { color: folder.color }
                                            : { color: '#3b82f6' }
                                    "
                                />
                                <span class="truncate">{{ folder.name }}</span>
                            </button>

                            <div class="flex items-center gap-1">
                                <Badge
                                    variant="secondary"
                                    class="h-5 bg-neutral-100 text-xs font-normal group-hover:hidden dark:bg-neutral-800"
                                >
                                    {{ folder.notes_count || 0 }}
                                </Badge>
                                <!-- Dropdown actions -->
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="h-6 w-6 opacity-0 transition-opacity group-hover:opacity-100"
                                        >
                                            <MoreVertical class="h-3.5 w-3.5" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem
                                            @click="
                                                openEditFolderDialog(folder)
                                            "
                                        >
                                            <Edit2 class="mr-2 h-3.5 w-3.5" />
                                            Rename
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="deleteFolder(folder)"
                                            class="text-red-500 hover:text-red-600"
                                        >
                                            <Trash2 class="mr-2 h-3.5 w-3.5" />
                                            Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t bg-neutral-50 p-3 dark:bg-neutral-900/50">
                <Button class="w-full justify-start" @click="createNote">
                    <Plus class="mr-2 h-4 w-4" /> New Note
                </Button>
            </div>
        </aside>

        <!-- Middle Pane: Notes list (Width: 80) -->
        <section
            class="flex w-full shrink-0 flex-col border-r border-neutral-200 bg-white md:w-80 dark:border-neutral-800 dark:bg-neutral-900"
        >
            <!-- Search & Filter Controls -->
            <div class="space-y-3 border-b p-4">
                <div class="relative">
                    <Search
                        class="absolute top-2.5 left-3 h-4 w-4 text-neutral-400 dark:text-neutral-500"
                    />
                    <Input
                        type="text"
                        placeholder="Search notes..."
                        v-model="searchQuery"
                        class="pl-9"
                    />
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-neutral-400 dark:text-neutral-500">
                        {{ filteredNotes.length }} catatans
                    </span>
                    <div class="flex items-center gap-2">
                        <span class="text-neutral-400 dark:text-neutral-500"
                            >Sort by:</span
                        >
                        <select
                            v-model="sortBy"
                            class="cursor-pointer border-none bg-transparent font-medium text-neutral-700 focus:ring-0 dark:text-neutral-300"
                        >
                            <option value="updated">Recent</option>
                            <option value="title">Title</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Notes Cards Loop -->
            <div
                class="flex-1 divide-y divide-neutral-100 overflow-y-auto dark:divide-neutral-800"
            >
                <div
                    v-for="note in filteredNotes"
                    :key="note.id"
                    @click="selectNote(note.id)"
                    class="cursor-pointer p-4 transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800/30"
                    :class="[
                        selectedNoteId === note.id
                            ? 'border-l-2 border-blue-500 bg-neutral-50 dark:bg-neutral-800/50'
                            : '',
                    ]"
                >
                    <div class="mb-1 flex items-start justify-between gap-2">
                        <h3
                            class="truncate text-sm font-semibold text-neutral-800 dark:text-neutral-200"
                            :class="[
                                !note.title
                                    ? 'text-neutral-400 italic dark:text-neutral-500'
                                    : '',
                            ]"
                        >
                            {{ note.title || 'Untitled Note' }}
                        </h3>
                        <div class="flex shrink-0 items-center gap-1">
                            <Star
                                v-if="note.is_favorite"
                                class="h-3 w-3 fill-amber-400 text-amber-400"
                            />
                            <Archive
                                v-if="note.is_archived"
                                class="h-3 w-3 text-neutral-400 dark:text-neutral-500"
                            />
                        </div>
                    </div>
                    <p
                        class="mb-2 line-clamp-2 text-xs text-neutral-500 dark:text-neutral-400"
                    >
                        {{ note.snippet || 'No content yet...' }}
                    </p>
                    <div
                        class="flex items-center justify-between text-[10px] text-neutral-400 dark:text-neutral-500"
                    >
                        <span class="flex items-center">
                            <Calendar class="mr-1 h-3 w-3" />
                            {{ formatDate(note.updated_at) }}
                        </span>
                        <span
                            v-if="note.folder"
                            class="max-w-[100px] truncate rounded-full bg-neutral-100 px-1.5 py-0.5 dark:bg-neutral-800"
                        >
                            {{ note.folder.name }}
                        </span>
                    </div>
                </div>

                <div
                    v-if="filteredNotes.length === 0"
                    class="space-y-2 p-8 text-center text-neutral-400 dark:text-neutral-500"
                >
                    <FileText class="mx-auto h-10 w-10 stroke-1" />
                    <p class="text-sm font-medium">No notes found</p>
                </div>
            </div>
        </section>

        <!-- Right Pane: Note Editor (Width: Flex) -->
        <main
            class="flex h-full flex-1 flex-col overflow-hidden bg-white dark:bg-neutral-900"
        >
            <!-- Empty state if no note is selected -->
            <div
                v-if="!activeNote"
                class="flex flex-1 flex-col items-center justify-center p-8 text-center text-neutral-400 dark:text-neutral-500"
            >
                <FileText
                    class="mb-4 h-16 w-16 stroke-1 text-neutral-300 dark:text-neutral-700"
                />
                <h3 class="mb-1 text-lg font-medium">Workspace Notes</h3>
                <p class="mb-4 max-w-xs text-sm">
                    Pilih catatan di panel samping atau buat catatan baru untuk
                    memulai.
                </p>
                <Button @click="createNote">
                    <Plus class="mr-2 h-4 w-4" /> Buat Catatan Baru
                </Button>
            </div>

            <!-- Editor View -->
            <div v-else class="flex h-full flex-1 flex-col overflow-hidden">
                <!-- Editor Header Toolbar -->
                <div
                    class="flex shrink-0 items-center justify-between border-b px-6 py-3"
                >
                    <div class="flex items-center gap-3">
                        <!-- Auto-save state feedback -->
                        <span
                            class="flex items-center gap-1.5 text-xs font-medium text-neutral-400 dark:text-neutral-500"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full"
                                :class="[
                                    isSaving
                                        ? 'animate-pulse bg-amber-400'
                                        : 'bg-emerald-500',
                                ]"
                            ></span>
                            {{ saveStatusText }}
                        </span>
                    </div>

                    <!-- Editor Controls -->
                    <div class="flex items-center gap-1.5">
                        <!-- Editor Toggle -->
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-8 gap-1.5 font-normal"
                                >
                                    <FileEdit
                                        class="h-3.5 w-3.5 text-neutral-500"
                                    />
                                    <span
                                        >Editor:
                                        {{
                                            editorType === 'tiptap'
                                                ? 'TipTap'
                                                : 'CKEditor 5'
                                        }}</span
                                    >
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem
                                    @click="updateEditorType('tiptap')"
                                >
                                    <div
                                        class="flex w-full items-center justify-between"
                                    >
                                        <span>TipTap (Modern)</span>
                                        <Check
                                            v-if="editorType === 'tiptap'"
                                            class="ml-2 h-3.5 w-3.5"
                                        />
                                    </div>
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    @click="updateEditorType('ckeditor')"
                                >
                                    <div
                                        class="flex w-full items-center justify-between"
                                    >
                                        <span>CKEditor 5 (Classic)</span>
                                        <Check
                                            v-if="editorType === 'ckeditor'"
                                            class="ml-2 h-3.5 w-3.5"
                                        />
                                    </div>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <!-- Favorite Toggle -->
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-8 w-8 text-neutral-500"
                            :class="{
                                'text-amber-500 hover:text-amber-600':
                                    activeNote.is_favorite,
                            }"
                            @click="toggleFavorite(activeNote)"
                            title="Favorite"
                        >
                            <Star
                                class="h-4 w-4"
                                :class="{
                                    'fill-amber-500': activeNote.is_favorite,
                                }"
                            />
                        </Button>

                        <!-- Archive Toggle -->
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-8 w-8 text-neutral-500"
                            :class="{
                                'text-blue-500 hover:text-blue-600':
                                    activeNote.is_archived,
                            }"
                            @click="toggleArchive(activeNote)"
                            title="Archive"
                        >
                            <Archive class="h-4 w-4" />
                        </Button>

                        <!-- Delete Note -->
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-8 w-8 text-red-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/20"
                            @click="deleteNote(activeNote)"
                            title="Delete"
                        >
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <!-- Editor Workspace Area -->
                <div class="flex-1 space-y-4 overflow-y-auto px-6 py-6">
                    <!-- Title Input Field -->
                    <div>
                        <input
                            type="text"
                            v-model="activeNote.title"
                            placeholder="Judul Catatan..."
                            class="w-full border-none bg-transparent text-2xl font-bold text-neutral-900 placeholder-neutral-300 focus:outline-none dark:text-neutral-100 dark:placeholder-neutral-600"
                        />
                        <div
                            v-if="activeNote.folder"
                            class="mt-1 flex items-center gap-1.5"
                        >
                            <span
                                class="flex items-center text-xs text-neutral-400 dark:text-neutral-500"
                            >
                                <Folder class="mr-1 h-3 w-3" />
                                {{ activeNote.folder.name }}
                            </span>
                        </div>
                    </div>

                    <!-- Dynamic Editor -->
                    <div class="flex-1">
                        <TiptapEditor
                            v-if="editorType === 'tiptap'"
                            v-model="activeNote.content"
                            placeholder="Mulai menulis catatan..."
                            @saving="isSaving = true"
                            @saved="isSaving = false"
                        />
                        <CkEditor
                            v-else-if="editorType === 'ckeditor'"
                            v-model="activeNote.content"
                            placeholder="Mulai menulis catatan..."
                            @saving="isSaving = true"
                            @saved="isSaving = false"
                        />
                    </div>

                    <!-- Backlinks Footer Section -->
                    <div
                        class="mt-8 border-t border-neutral-100 pt-6 dark:border-neutral-800"
                    >
                        <h4
                            class="mb-3 flex items-center gap-1.5 text-xs font-semibold tracking-wider text-neutral-400 uppercase dark:text-neutral-500"
                        >
                            <Link2 class="h-3.5 w-3.5" /> Backlinks
                        </h4>

                        <div
                            v-if="
                                activeNote.backlinks &&
                                activeNote.backlinks.length > 0
                            "
                            class="flex flex-wrap gap-2"
                        >
                            <button
                                v-for="link in activeNote.backlinks"
                                :key="link.id"
                                @click="selectNote(link.id)"
                                class="inline-flex items-center rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium text-neutral-700 transition-colors hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700"
                            >
                                [[{{ link.title || 'Untitled Note' }}]]
                            </button>
                        </div>
                        <p
                            v-else
                            class="text-xs text-neutral-400 italic dark:text-neutral-500"
                        >
                            No links pointing to this note yet. Use [[Note
                            Title]] inside another note to create one.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Folder Modal (Create/Rename) -->
    <Dialog v-model:open="isFolderDialogOpen">
        <DialogContent
            class="border bg-white sm:max-w-md dark:border-neutral-800 dark:bg-neutral-900"
        >
            <DialogHeader>
                <DialogTitle class="text-neutral-900 dark:text-neutral-50">
                    {{ isEditingFolder ? 'Rename Folder' : 'New Folder' }}
                </DialogTitle>
            </DialogHeader>
            <div class="space-y-4 py-2">
                <div class="space-y-2">
                    <Label
                        for="folder-name"
                        class="text-neutral-700 dark:text-neutral-300"
                        >Folder Name</Label
                    >
                    <Input
                        id="folder-name"
                        v-model="folderForm.name"
                        placeholder="Work, Personal, References, etc."
                        class="col-span-3"
                    />
                </div>
                <!-- Optional color selector -->
                <div class="space-y-2">
                    <Label class="text-neutral-700 dark:text-neutral-300"
                        >Color</Label
                    >
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="color in [
                                '#3b82f6',
                                '#10b981',
                                '#f59e0b',
                                '#ef4444',
                                '#8b5cf6',
                                '#ec4899',
                                '#64748b',
                            ]"
                            :key="color"
                            type="button"
                            class="h-7 w-7 cursor-pointer rounded-full border border-neutral-300 transition-transform dark:border-neutral-700"
                            :class="[
                                folderForm.color === color
                                    ? 'scale-110 ring-2 ring-neutral-400 dark:ring-neutral-500'
                                    : 'hover:scale-105',
                            ]"
                            :style="{ backgroundColor: color }"
                            @click="folderForm.color = color"
                        ></button>
                    </div>
                </div>
            </div>
            <DialogFooter>
                <Button variant="ghost" @click="isFolderDialogOpen = false">
                    Cancel
                </Button>
                <Button @click="saveFolder" :disabled="!folderForm.name">
                    Save
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Folder,
    FolderPlus,
    Plus,
    Search,
    Trash2,
    Edit2,
    Download,
    Eye,
    MoreVertical,
    UploadCloud,
    ChevronRight,
    Loader2,
    FileText,
    FileImage,
    FileVideo,
    FileAudio,
    File,
    Grid,
    List,
    FolderOpen,
    ExternalLink,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetDescription,
} from '@/components/ui/sheet';
import { useConfirm } from '@/composables/useConfirm';
import { store as storeFolder, update as updateFolder, destroy as destroyFolder } from '@/routes/document-folders';
import { index as documentsIndex, store as storeDoc, update as updateDoc, destroy as destroyDoc, preview as previewDoc, download as downloadDoc } from '@/routes/documents';
import versionsRoute from '@/routes/documents/versions';

const { confirm } = useConfirm();

// Type definitions
interface Project {
    id: number;
    name: string;
}

interface DocumentFolder {
    id: number;
    name: string;
    parent_id: number | null;
    project_id: number | null;
    created_at: string;
}

interface DocumentVersion {
    id: number;
    document_id: number;
    version: number;
    file_path: string;
    file_name: string;
    file_size: number;
    formatted_size: string;
    mime_type: string;
    created_at: string;
}

interface DocumentItem {
    id: number;
    folder_id: number | null;
    project_id: number | null;
    name: string;
    description: string | null;
    created_at: string;
    latest_version?: DocumentVersion | null;
    project?: Project | null;
    folder?: DocumentFolder | null;
}

const props = defineProps<{
    folders: DocumentFolder[];
    documents: DocumentItem[];
    breadcrumbs: { id: number; name: string }[];
    projects: Project[];
    allFolders: DocumentFolder[];
    currentFolderId: number | null;
    currentProjectId: number | null;
    filters: {
        search: string | null;
        project_id: number | null;
    };
}>();

// Persistent Layout Setup
defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Documents', href: '/app/documents' }],
    },
});

// UI State
const viewMode = ref<'grid' | 'list'>('grid');
const searchQuery = ref(props.filters.search || '');
const selectedProjectId = ref<string>(props.filters.project_id ? String(props.filters.project_id) : 'all');

// Modals & Sheets
const isCreateFolderOpen = ref(false);
const isRenameFolderOpen = ref(false);
const isUploadDocOpen = ref(false);
const isEditDocOpen = ref(false);
const isDetailSheetOpen = ref(false);

const activeFolder = ref<DocumentFolder | null>(null);
const activeDocument = ref<DocumentItem | null>(null);

// Forms
const folderForm = ref({
    name: '',
    parent_id: props.currentFolderId,
    project_id: props.currentProjectId ? String(props.currentProjectId) : 'none',
});

const editFolderForm = ref({
    id: 0,
    name: '',
});

const docForm = ref({
    name: '',
    description: '',
    project_id: props.currentProjectId ? String(props.currentProjectId) : 'none',
    folder_id: props.currentFolderId ? String(props.currentFolderId) : 'root',
    file: null as File | null,
});

const editDocForm = ref({
    id: 0,
    name: '',
    description: '',
    project_id: 'none',
    folder_id: 'root',
});

const newVersionFile = ref<File | null>(null);

watch(() => props.currentFolderId, (newFolderId) => {
    docForm.value.folder_id = newFolderId ? String(newFolderId) : 'root';
});

// Loading states
const isSubmitting = ref(false);
const isDragging = ref(false);

// Filter handling with watch
let filterTimeout: any = null;
watch([searchQuery, selectedProjectId], () => {
    if (filterTimeout) {
clearTimeout(filterTimeout);
}

    filterTimeout = setTimeout(() => {
        handleFilterSubmit();
    }, 400);
});

const handleFilterSubmit = () => {
    router.visit(documentsIndex.url({
        query: {
            search: searchQuery.value || undefined,
            project_id: selectedProjectId.value === 'all' ? undefined : selectedProjectId.value,
            folder_id: props.currentFolderId || undefined,
        }
    }), {
        preserveState: true,
        replace: true,
    });
};

const navigateToFolder = (folderId: number | null) => {
    router.visit(documentsIndex.url({
        query: {
            search: searchQuery.value || undefined,
            project_id: selectedProjectId.value === 'all' ? undefined : selectedProjectId.value,
            folder_id: folderId || undefined,
        }
    }), {
        preserveState: true,
    });
};

// Create Folder
const submitCreateFolder = () => {
    if (!folderForm.value.name.trim()) {
return;
}

    isSubmitting.value = true;

    router.post(storeFolder().url, {
        name: folderForm.value.name,
        parent_id: props.currentFolderId,
        project_id: folderForm.value.project_id === 'none' ? null : Number(folderForm.value.project_id),
    }, {
        onSuccess: () => {
            isCreateFolderOpen.value = false;
            folderForm.value.name = '';
            toast.success('Folder created successfully');
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};

// Rename Folder
const openRenameFolder = (folder: DocumentFolder) => {
    activeFolder.value = folder;
    editFolderForm.value.id = folder.id;
    editFolderForm.value.name = folder.name;
    isRenameFolderOpen.value = true;
};

const submitRenameFolder = () => {
    if (!editFolderForm.value.name.trim()) {
return;
}

    isSubmitting.value = true;

    router.patch(updateFolder(editFolderForm.value.id).url, {
        name: editFolderForm.value.name,
    }, {
        onSuccess: () => {
            isRenameFolderOpen.value = false;
            toast.success('Folder renamed successfully');
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};

// Delete Folder
const handleDeleteFolder = async (folder: DocumentFolder) => {
    const isConfirmed = await confirm({
        title: 'Delete Folder',
        message: `Are you sure you want to delete folder "${folder.name}" and all its contents?`,
        confirmText: 'Delete',
        cancelText: 'Cancel',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(destroyFolder(folder.id).url, {
            onSuccess: () => {
                toast.success('Folder deleted successfully');
            }
        });
    }
};

// File Upload Form Submit
const submitUploadDoc = () => {
    if (!docForm.value.file) {
        toast.error('Please select a file');

        return;
    }

    isSubmitting.value = true;

    const data = new FormData();
    data.append('file', docForm.value.file);

    if (docForm.value.name) {
data.append('name', docForm.value.name);
}

    if (docForm.value.description) {
data.append('description', docForm.value.description);
}

    if (docForm.value.folder_id !== 'root') {
        data.append('folder_id', String(docForm.value.folder_id));
    }

    if (docForm.value.project_id !== 'none') {
data.append('project_id', docForm.value.project_id);
}

    router.post(storeDoc().url, data as any, {
        onSuccess: () => {
            isUploadDocOpen.value = false;
            docForm.value.file = null;
            docForm.value.name = '';
            docForm.value.description = '';
            toast.success('Document uploaded successfully');
        },
        onError: (errors) => {
            toast.error(errors.file || 'Failed to upload document');
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};

// File Select Handlers
const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;

    if (target.files && target.files[0]) {
        docForm.value.file = target.files[0];

        if (!docForm.value.name) {
            docForm.value.name = target.files[0].name.replace(/\.[^/.]+$/, "");
        }
    }
};

// Drag and drop handlers
const handleDragOver = (e: DragEvent) => {
    e.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = () => {
    isDragging.value = false;
};

const handleDrop = (e: DragEvent) => {
    e.preventDefault();
    isDragging.value = false;

    if (e.dataTransfer?.files && e.dataTransfer.files[0]) {
        docForm.value.file = e.dataTransfer.files[0];
        docForm.value.name = e.dataTransfer.files[0].name.replace(/\.[^/.]+$/, "");
        isUploadDocOpen.value = true;
    }
};

// Detail Sheet & Preview
const viewDocumentDetails = (doc: DocumentItem) => {
    activeDocument.value = doc;
    isDetailSheetOpen.value = true;
};

const getFileIcon = (mimeType: string | undefined) => {
    if (!mimeType) {
return File;
}

    if (mimeType.startsWith('image/')) {
return FileImage;
}

    if (mimeType.startsWith('video/')) {
return FileVideo;
}

    if (mimeType.startsWith('audio/')) {
return FileAudio;
}

    if (mimeType === 'application/pdf') {
return FileText;
}

    if (mimeType.includes('spreadsheet') || mimeType.includes('excel') || mimeType.includes('csv')) {
return FileText;
}

    return File;
};

const isPreviewable = (mimeType: string | undefined) => {
    if (!mimeType) {
return false;
}

    return mimeType.startsWith('image/') ||
           mimeType.startsWith('video/') ||
           mimeType.startsWith('audio/') ||
           mimeType === 'application/pdf' ||
           mimeType.startsWith('text/') ||
           mimeType === 'application/json';
};

// File Download/Preview Urls
const getPreviewUrl = (versionId: number) => {
    return previewDoc(versionId).url;
};

const getDownloadUrl = (versionId: number) => {
    return downloadDoc(versionId).url;
};

// Upload New Version
const handleNewVersionChange = (e: Event) => {
    const target = e.target as HTMLInputElement;

    if (target.files && target.files[0] && activeDocument.value) {
        newVersionFile.value = target.files[0];
        submitNewVersion();
    }
};

const submitNewVersion = () => {
    if (!newVersionFile.value || !activeDocument.value) {
return;
}

    isSubmitting.value = true;

    const data = new FormData();
    data.append('file', newVersionFile.value);

    router.post(versionsRoute.store(activeDocument.value.id).url, data as any, {
        onSuccess: () => {
            newVersionFile.value = null;
            toast.success('Uploaded new version successfully');
            // Update active document in local state if refreshed
            const updatedDoc = props.documents.find(d => d.id === activeDocument.value?.id);

            if (updatedDoc) {
activeDocument.value = updatedDoc;
}
        },
        onError: (errors) => {
            toast.error(errors.file || 'Failed to upload version');
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};

// Edit Doc Metadata
const openEditDoc = (doc: DocumentItem) => {
    activeDocument.value = doc;
    editDocForm.value.id = doc.id;
    editDocForm.value.name = doc.name;
    editDocForm.value.description = doc.description || '';
    editDocForm.value.project_id = doc.project_id ? String(doc.project_id) : 'none';
    editDocForm.value.folder_id = doc.folder_id ? String(doc.folder_id) : 'root';
    isEditDocOpen.value = true;
};

const submitEditDoc = () => {
    if (!editDocForm.value.name.trim()) {
        return;
    }

    isSubmitting.value = true;

    router.patch(updateDoc(editDocForm.value.id).url, {
        name: editDocForm.value.name,
        description: editDocForm.value.description,
        project_id: editDocForm.value.project_id === 'none' ? null : Number(editDocForm.value.project_id),
        folder_id: editDocForm.value.folder_id === 'root' ? null : Number(editDocForm.value.folder_id),
    }, {
        onSuccess: () => {
            isEditDocOpen.value = false;
            toast.success('Document updated successfully');

            // Refresh detailed sheet if open
            if (activeDocument.value) {
                const updatedDoc = props.documents.find(d => d.id === activeDocument.value?.id);

                if (updatedDoc) {
                    activeDocument.value = updatedDoc;
                }
            }
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};

// Delete Doc
const handleDeleteDoc = async (doc: DocumentItem) => {
    const isConfirmed = await confirm({
        title: 'Delete Document',
        message: `Are you sure you want to delete document "${doc.name}"?`,
        confirmText: 'Delete',
        cancelText: 'Cancel',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(destroyDoc(doc.id).url, {
            onSuccess: () => {
                isDetailSheetOpen.value = false;
                toast.success('Document deleted successfully');
            }
        });
    }
};
</script>

<template>
    <Head title="Documents" />

    <div
        class="flex flex-1 flex-col gap-6 p-6 h-full overflow-y-auto"
        @dragover="handleDragOver"
        @dragleave="handleDragLeave"
        @drop="handleDrop"
    >
        <!-- Drag Over Overlay -->
        <div
            v-if="isDragging"
            class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-background/80 backdrop-blur-sm border-4 border-dashed border-primary m-4 rounded-xl pointer-events-none transition-all duration-300"
        >
            <UploadCloud class="h-16 w-16 text-primary animate-bounce mb-4" />
            <h3 class="text-xl font-bold text-neutral-800 dark:text-neutral-200">
                Drop your file here to upload
            </h3>
            <p class="text-sm text-neutral-500 mt-1">
                Files will be saved in the current folder.
            </p>
        </div>

        <!-- Upper Bar: Actions, Search, Filter -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-card/60 backdrop-blur border border-neutral-200/50 dark:border-neutral-800/50 p-4 rounded-xl">
            <div class="flex flex-1 flex-col sm:flex-row sm:items-center gap-3">
                <!-- Search bar -->
                <div class="relative flex-1 max-w-sm">
                    <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="searchQuery"
                        type="search"
                        placeholder="Search folders or files..."
                        class="pl-9 h-9"
                    />
                </div>

                <!-- Project Filter -->
                <Select v-model="selectedProjectId">
                    <SelectTrigger class="w-full sm:w-48 h-9 text-xs">
                        <SelectValue placeholder="All Projects" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Projects</SelectItem>
                        <SelectItem
                            v-for="project in projects"
                            :key="project.id"
                            :value="String(project.id)"
                        >
                            {{ project.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- View Switcher & Actions -->
            <div class="flex items-center gap-3">
                <div class="flex items-center border border-neutral-200/80 dark:border-neutral-800/80 rounded-lg p-0.5 bg-neutral-100/50 dark:bg-neutral-900/50">
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-7 w-7"
                        :class="{ 'bg-background shadow-sm text-primary': viewMode === 'grid' }"
                        @click="viewMode = 'grid'"
                    >
                        <Grid class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-7 w-7"
                        :class="{ 'bg-background shadow-sm text-primary': viewMode === 'list' }"
                        @click="viewMode = 'list'"
                    >
                        <List class="h-4 w-4" />
                    </Button>
                </div>

                <Button
                    variant="outline"
                    size="sm"
                    class="h-9 gap-1.5"
                    @click="isCreateFolderOpen = true"
                >
                    <FolderPlus class="h-4 w-4 text-indigo-500" />
                    <span>New Folder</span>
                </Button>

                <Button
                    size="sm"
                    class="h-9 gap-1.5"
                    @click="isUploadDocOpen = true"
                >
                    <Plus class="h-4 w-4" />
                    <span>Upload File</span>
                </Button>
            </div>
        </div>

        <!-- Custom Directory Navigation Breadcrumbs -->
        <div class="flex items-center flex-wrap gap-2 text-sm text-neutral-500 font-medium">
            <span
                class="hover:text-primary cursor-pointer transition-colors"
                @click="navigateToFolder(null)"
            >
                All Files
            </span>
            <template v-for="(crumb, idx) in breadcrumbs" :key="crumb.id">
                <ChevronRight class="h-4 w-4 text-neutral-400" />
                <span
                    class="hover:text-primary cursor-pointer transition-colors"
                    :class="{ 'text-neutral-900 dark:text-neutral-100 font-semibold cursor-default': idx === breadcrumbs.length - 1 }"
                    @click="idx === breadcrumbs.length - 1 ? null : navigateToFolder(crumb.id)"
                >
                    {{ crumb.name }}
                </span>
            </template>
        </div>

        <!-- Folders Section -->
        <div v-if="folders.length > 0" class="flex flex-col gap-3">
            <h3 class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                Folders ({{ folders.length }})
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <div
                    v-for="folder in folders"
                    :key="folder.id"
                    class="group relative flex flex-col justify-between p-4 bg-card/40 border border-neutral-200/50 dark:border-neutral-800/50 rounded-xl hover:bg-card hover:border-primary/50 transition-all duration-200 cursor-pointer select-none"
                    @dblclick="navigateToFolder(folder.id)"
                >
                    <div class="flex items-start justify-between">
                        <div class="p-2 bg-indigo-500/10 text-indigo-500 rounded-lg group-hover:scale-105 transition-transform">
                            <Folder class="h-6 w-6" />
                        </div>

                        <!-- Dropdown Actions -->
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-7 w-7 rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-850 opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <MoreVertical class="h-4 w-4 text-muted-foreground" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-32">
                                <DropdownMenuItem @click="openRenameFolder(folder)">
                                    <Edit2 class="mr-2 h-3.5 w-3.5" />
                                    Rename
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    class="text-red-600 focus:text-red-650"
                                    @click="handleDeleteFolder(folder)"
                                >
                                    <Trash2 class="mr-2 h-3.5 w-3.5" />
                                    Delete
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <div class="mt-4">
                        <h4 class="font-semibold text-neutral-800 dark:text-neutral-200 truncate pr-4 text-sm" :title="folder.name">
                            {{ folder.name }}
                        </h4>
                        <span class="text-[10px] text-muted-foreground mt-0.5 block">
                            {{ new Date(folder.created_at).toLocaleDateString() }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents Section -->
        <div class="flex flex-col gap-3 flex-1 min-h-[250px]">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                    Files ({{ documents.length }})
                </h3>
            </div>

            <!-- Empty State -->
            <div
                v-if="folders.length === 0 && documents.length === 0"
                class="flex flex-col items-center justify-center flex-1 bg-card/25 border border-dashed border-neutral-200 dark:border-neutral-800 rounded-xl p-8 text-center"
            >
                <div class="p-3 bg-neutral-100 dark:bg-neutral-900 rounded-full text-neutral-400 mb-4">
                    <FolderOpen class="h-10 w-10" />
                </div>
                <h4 class="text-base font-bold text-neutral-800 dark:text-neutral-200">
                    No files or folders here
                </h4>
                <p class="text-sm text-neutral-500 max-w-sm mt-1 mb-4">
                    Drag and drop files on this page or use the buttons above to get started.
                </p>
            </div>

            <!-- Grid View -->
            <div
                v-else-if="viewMode === 'grid'"
                class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4"
            >
                <div
                    v-for="doc in documents"
                    :key="doc.id"
                    class="group relative flex flex-col justify-between bg-card/40 border border-neutral-200/50 dark:border-neutral-800/50 rounded-xl hover:bg-card hover:border-primary/50 transition-all duration-200 cursor-pointer overflow-hidden"
                    @click="viewDocumentDetails(doc)"
                >
                    <!-- Thumbnail/Icon Header -->
                    <div class="relative aspect-video flex items-center justify-center bg-neutral-50 dark:bg-neutral-950 border-b border-neutral-200/30 dark:border-neutral-800/30 overflow-hidden">
                        <!-- Image Preview Thumbnail -->
                        <img
                            v-if="doc.latest_version?.mime_type.startsWith('image/')"
                            :src="getPreviewUrl(doc.latest_version.id)"
                            class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            alt="thumbnail"
                        />
                        <div v-else class="flex flex-col items-center gap-2 text-muted-foreground group-hover:scale-105 transition-transform duration-300">
                            <component
                                :is="getFileIcon(doc.latest_version?.mime_type)"
                                class="h-10 w-10 text-neutral-400 group-hover:text-primary transition-colors"
                            />
                            <span class="text-[10px] uppercase font-bold text-neutral-500 px-1.5 py-0.5 bg-neutral-200/40 dark:bg-neutral-800/40 rounded">
                                {{ doc.latest_version?.mime_type.split('/').pop() }}
                            </span>
                        </div>

                        <!-- Dropdown Actions -->
                        <div class="absolute top-2 right-2 z-10" @click.stop>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-7 w-7 rounded-lg bg-background/80 hover:bg-background border shadow-sm opacity-0 group-hover:opacity-100 transition-opacity"
                                    >
                                        <MoreVertical class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-32">
                                    <DropdownMenuItem @click="viewDocumentDetails(doc)">
                                        <Eye class="mr-2 h-3.5 w-3.5" />
                                        Preview
                                    </DropdownMenuItem>
                                    <DropdownMenuItem @click="openEditDoc(doc)">
                                        <Edit2 class="mr-2 h-3.5 w-3.5" />
                                        Edit
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        v-if="doc.latest_version"
                                        as="a"
                                        :href="getDownloadUrl(doc.latest_version.id)"
                                        download
                                    >
                                        <Download class="mr-2 h-3.5 w-3.5" />
                                        Download
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        class="text-red-600 focus:text-red-650"
                                        @click="handleDeleteDoc(doc)"
                                    >
                                        <Trash2 class="mr-2 h-3.5 w-3.5" />
                                        Delete
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>

                    <!-- Details Card Footer -->
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-1.5">
                            <h4 class="font-semibold text-neutral-800 dark:text-neutral-200 truncate text-sm flex-1" :title="doc.name">
                                {{ doc.name }}
                            </h4>
                            <Badge v-if="doc.latest_version" variant="outline" class="h-5 px-1 py-0 text-[9px] font-medium border-neutral-300/50">
                                v{{ doc.latest_version.version }}
                            </Badge>
                        </div>
                        <p v-if="doc.description" class="text-xs text-neutral-500 truncate mt-1">
                            {{ doc.description }}
                        </p>
                        <div class="flex items-center justify-between text-[10px] text-neutral-400 mt-3 border-t border-neutral-100/50 dark:border-neutral-900/50 pt-2">
                            <span>{{ doc.latest_version?.formatted_size || '0 B' }}</span>
                            <span v-if="doc.project" class="text-indigo-500 dark:text-indigo-400 font-semibold max-w-[80px] truncate">
                                {{ doc.project.name }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- List View (Table Layout) -->
            <div v-else-if="documents.length > 0" class="border border-neutral-200/50 dark:border-neutral-800/50 rounded-xl bg-card/30 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-neutral-100/50 dark:bg-neutral-900/50 text-[11px] font-bold uppercase tracking-wider text-muted-foreground border-b border-neutral-200/50 dark:border-neutral-800/50">
                            <th class="p-4">Name</th>
                            <th class="p-4">Project</th>
                            <th class="p-4">Version</th>
                            <th class="p-4">Size</th>
                            <th class="p-4">Last Updated</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200/30 dark:divide-neutral-800/30">
                        <tr
                            v-for="doc in documents"
                            :key="doc.id"
                            class="hover:bg-neutral-100/30 dark:hover:bg-neutral-900/20 cursor-pointer text-sm group"
                            @click="viewDocumentDetails(doc)"
                        >
                            <td class="p-4 flex items-center gap-3">
                                <div class="p-1.5 bg-neutral-100 dark:bg-neutral-900 rounded-lg text-neutral-400 group-hover:text-primary transition-colors">
                                    <component :is="getFileIcon(doc.latest_version?.mime_type)" class="h-5 w-5" />
                                </div>
                                <div class="max-w-[200px] sm:max-w-xs truncate">
                                    <span class="font-semibold text-neutral-800 dark:text-neutral-200 block truncate" :title="doc.name">
                                        {{ doc.name }}
                                    </span>
                                    <span class="text-xs text-neutral-500 block truncate" v-if="doc.latest_version">
                                        {{ doc.latest_version.file_name }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-4">
                                <Badge v-if="doc.project" variant="secondary" class="bg-indigo-500/10 text-indigo-500 dark:text-indigo-400">
                                    {{ doc.project.name }}
                                </Badge>
                                <span v-else class="text-neutral-400 text-xs">General</span>
                            </td>
                            <td class="p-4">
                                <span class="text-xs font-semibold">
                                    v{{ doc.latest_version?.version || 1 }}
                                </span>
                            </td>
                            <td class="p-4 text-xs font-medium">
                                {{ doc.latest_version?.formatted_size || '0 B' }}
                            </td>
                            <td class="p-4 text-xs text-muted-foreground">
                                {{ new Date(doc.created_at).toLocaleString() }}
                            </td>
                            <td class="p-4 text-right" @click.stop>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon" class="h-8 w-8 rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-850">
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-32">
                                        <DropdownMenuItem @click="viewDocumentDetails(doc)">
                                            <Eye class="mr-2 h-3.5 w-3.5" />
                                            Preview
                                        </DropdownMenuItem>
                                        <DropdownMenuItem @click="openEditDoc(doc)">
                                            <Edit2 class="mr-2 h-3.5 w-3.5" />
                                            Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-if="doc.latest_version"
                                            as="a"
                                            :href="getDownloadUrl(doc.latest_version.id)"
                                            download
                                        >
                                            <Download class="mr-2 h-3.5 w-3.5" />
                                            Download
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            class="text-red-600 focus:text-red-650"
                                            @click="handleDeleteDoc(doc)"
                                        >
                                            <Trash2 class="mr-2 h-3.5 w-3.5" />
                                            Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================= DIALOGS & SHEET MODALS ================= -->

        <!-- Create Folder Dialog -->
        <Dialog v-model:open="isCreateFolderOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Create Folder</DialogTitle>
                    <DialogDescription>
                        Add a new subdirectory to organize your files.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="folder_name" class="text-right">Name</Label>
                        <Input
                            id="folder_name"
                            v-model="folderForm.name"
                            class="col-span-3"
                            placeholder="e.g. Design Assets"
                            @keyup.enter="submitCreateFolder"
                        />
                    </div>

                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="folder_project" class="text-right">Project</Label>
                        <Select v-model="folderForm.project_id" class="col-span-3">
                            <SelectTrigger class="col-span-3">
                                <SelectValue placeholder="General / Standalone" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">General (No Project)</SelectItem>
                                <SelectItem
                                    v-for="project in projects"
                                    :key="project.id"
                                    :value="String(project.id)"
                                >
                                    {{ project.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="ghost" @click="isCreateFolderOpen = false">Cancel</Button>
                    <Button :disabled="isSubmitting" @click="submitCreateFolder">
                        <Loader2 v-if="isSubmitting" class="mr-2 h-4 w-4 animate-spin" />
                        Create
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Rename Folder Dialog -->
        <Dialog v-model:open="isRenameFolderOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Rename Folder</DialogTitle>
                    <DialogDescription>
                        Give this folder a new name.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="rename_folder_name" class="text-right">Name</Label>
                        <Input
                            id="rename_folder_name"
                            v-model="editFolderForm.name"
                            class="col-span-3"
                            @keyup.enter="submitRenameFolder"
                        />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="ghost" @click="isRenameFolderOpen = false">Cancel</Button>
                    <Button :disabled="isSubmitting" @click="submitRenameFolder">
                        <Loader2 v-if="isSubmitting" class="mr-2 h-4 w-4 animate-spin" />
                        Rename
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Upload Document Dialog -->
        <Dialog v-model:open="isUploadDocOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Upload Document</DialogTitle>
                    <DialogDescription>
                        Choose or drag & drop a file to store in private workspace.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <!-- Drop area -->
                    <div
                        class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-neutral-300 dark:border-neutral-800 rounded-lg text-center cursor-pointer bg-neutral-50/50 dark:bg-neutral-900/50 hover:bg-neutral-50 dark:hover:bg-neutral-900 transition-colors"
                        @click="$refs.fileInput.click()"
                    >
                        <UploadCloud class="h-10 w-10 text-neutral-400 mb-2" />
                        <span class="text-xs font-semibold text-neutral-600 dark:text-neutral-400">
                            {{ docForm.file ? docForm.file.name : 'Click to select or drag file here' }}
                        </span>
                        <span class="text-[10px] text-neutral-400 mt-1" v-if="docForm.file">
                            {{ (docForm.file.size / 1024 / 1024).toFixed(2) }} MB
                        </span>
                        <input
                            ref="fileInput"
                            type="file"
                            class="hidden"
                            @change="handleFileChange"
                        />
                    </div>

                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="doc_name" class="text-right">Name</Label>
                        <Input
                            id="doc_name"
                            v-model="docForm.name"
                            class="col-span-3"
                            placeholder="Optional display name"
                        />
                    </div>

                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="doc_desc" class="text-right">Description</Label>
                        <Input
                            id="doc_desc"
                            v-model="docForm.description"
                            class="col-span-3"
                            placeholder="Optional description"
                        />
                    </div>

                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="doc_project" class="text-right">Project</Label>
                        <Select v-model="docForm.project_id" class="col-span-3">
                            <SelectTrigger class="col-span-3">
                                <SelectValue placeholder="General / Standalone" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">General (No Project)</SelectItem>
                                <SelectItem
                                    v-for="project in projects"
                                    :key="project.id"
                                    :value="String(project.id)"
                                >
                                    {{ project.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="doc_folder" class="text-right">Folder</Label>
                        <Select v-model="docForm.folder_id" class="col-span-3">
                            <SelectTrigger class="col-span-3">
                                <SelectValue placeholder="Root Folder" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="root">Root Folder ( / )</SelectItem>
                                <SelectItem
                                    v-for="folder in allFolders"
                                    :key="folder.id"
                                    :value="String(folder.id)"
                                >
                                    {{ folder.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="ghost" @click="isUploadDocOpen = false">Cancel</Button>
                    <Button :disabled="isSubmitting || !docForm.file" @click="submitUploadDoc">
                        <Loader2 v-if="isSubmitting" class="mr-2 h-4 w-4 animate-spin" />
                        Upload
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Edit Document Dialog -->
        <Dialog v-model:open="isEditDocOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Edit Document Metadata</DialogTitle>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="edit_doc_name" class="text-right">Name</Label>
                        <Input
                            id="edit_doc_name"
                            v-model="editDocForm.name"
                            class="col-span-3"
                        />
                    </div>

                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="edit_doc_desc" class="text-right">Description</Label>
                        <Input
                            id="edit_doc_desc"
                            v-model="editDocForm.description"
                            class="col-span-3"
                        />
                    </div>

                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="edit_doc_project" class="text-right">Project</Label>
                        <Select v-model="editDocForm.project_id" class="col-span-3">
                            <SelectTrigger class="col-span-3">
                                <SelectValue placeholder="General / Standalone" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">General (No Project)</SelectItem>
                                <SelectItem
                                    v-for="project in projects"
                                    :key="project.id"
                                    :value="String(project.id)"
                                >
                                    {{ project.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="edit_doc_folder" class="text-right">Folder</Label>
                        <Select v-model="editDocForm.folder_id" class="col-span-3">
                            <SelectTrigger class="col-span-3">
                                <SelectValue placeholder="Root Folder" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="root">Root Folder ( / )</SelectItem>
                                <SelectItem
                                    v-for="folder in allFolders"
                                    :key="folder.id"
                                    :value="String(folder.id)"
                                >
                                    {{ folder.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="ghost" @click="isEditDocOpen = false">Cancel</Button>
                    <Button :disabled="isSubmitting" @click="submitEditDoc">
                        <Loader2 v-if="isSubmitting" class="mr-2 h-4 w-4 animate-spin" />
                        Save Changes
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Detailed File Information Drawer Sheet -->
        <Sheet v-model:open="isDetailSheetOpen">
            <SheetContent
                class="w-full sm:max-w-xl h-full overflow-y-auto flex flex-col gap-6 p-6"
                side="right"
            >
                <SheetHeader class="text-left">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 bg-neutral-100 dark:bg-neutral-900 rounded-lg text-neutral-400">
                            <component :is="getFileIcon(activeDocument?.latest_version?.mime_type)" class="h-6 w-6 text-primary" />
                        </div>
                        <div>
                            <SheetTitle class="text-base truncate max-w-sm md:max-w-md" :title="activeDocument?.name">
                                {{ activeDocument?.name }}
                            </SheetTitle>
                            <SheetDescription class="text-xs">
                                Manage details, previews, and versions.
                            </SheetDescription>
                        </div>
                    </div>
                </SheetHeader>

                <!-- Document Details Box -->
                <div class="grid grid-cols-2 gap-4 text-xs bg-neutral-50/50 dark:bg-neutral-900/40 p-4 rounded-xl border border-neutral-200/50 dark:border-neutral-800/50">
                    <div>
                        <span class="text-muted-foreground block mb-0.5">Project</span>
                        <Badge v-if="activeDocument?.project" variant="outline" class="font-bold text-indigo-500 dark:text-indigo-400">
                            {{ activeDocument.project.name }}
                        </Badge>
                        <span v-else class="font-semibold text-neutral-600 dark:text-neutral-400">General (No Project)</span>
                    </div>

                    <div>
                        <span class="text-muted-foreground block mb-0.5">Size</span>
                        <span class="font-semibold">{{ activeDocument?.latest_version?.formatted_size || '0 B' }}</span>
                    </div>

                    <div>
                        <span class="text-muted-foreground block mb-0.5">Current Version</span>
                        <Badge class="bg-primary/10 text-primary font-bold">
                            v{{ activeDocument?.latest_version?.version || 1 }}
                        </Badge>
                    </div>

                    <div>
                        <span class="text-muted-foreground block mb-0.5">Uploaded</span>
                        <span class="font-semibold">{{ activeDocument ? new Date(activeDocument.created_at).toLocaleDateString() : '' }}</span>
                    </div>

                    <div v-if="activeDocument?.description" class="col-span-2 border-t border-neutral-200/30 dark:border-neutral-800/30 pt-2">
                        <span class="text-muted-foreground block mb-0.5">Description</span>
                        <p class="font-medium text-neutral-700 dark:text-neutral-300">
                            {{ activeDocument.description }}
                        </p>
                    </div>
                </div>

                <!-- Preview Box -->
                <div class="flex-1 flex flex-col gap-2">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Document Preview
                    </h3>

                    <!-- Stream previews -->
                    <div
                        v-if="activeDocument?.latest_version && isPreviewable(activeDocument.latest_version.mime_type)"
                        class="flex-1 min-h-[300px] border border-neutral-200/50 dark:border-neutral-800/50 rounded-xl bg-neutral-950/20 overflow-hidden flex items-center justify-center relative group/preview"
                    >
                        <!-- Images -->
                        <img
                            v-if="activeDocument.latest_version.mime_type.startsWith('image/')"
                            :src="getPreviewUrl(activeDocument.latest_version.id)"
                            class="max-w-full max-h-72 object-contain"
                            alt="preview"
                        />

                        <!-- Video -->
                        <video
                            v-else-if="activeDocument.latest_version.mime_type.startsWith('video/')"
                            :src="getPreviewUrl(activeDocument.latest_version.id)"
                            controls
                            class="w-full rounded max-h-72"
                        />

                        <!-- Audio -->
                        <audio
                            v-else-if="activeDocument.latest_version.mime_type.startsWith('audio/')"
                            :src="getPreviewUrl(activeDocument.latest_version.id)"
                            controls
                            class="w-4/5"
                        />

                        <!-- PDF & Code/Text inside iframe -->
                        <iframe
                            v-else
                            :src="getPreviewUrl(activeDocument.latest_version.id)"
                            class="w-full h-80 bg-background"
                            frameborder="0"
                        />
                        
                        <a
                            :href="getPreviewUrl(activeDocument.latest_version.id)"
                            target="_blank"
                            class="absolute top-2 right-2 p-1.5 bg-background/80 hover:bg-background rounded-lg border shadow-sm opacity-0 group-hover/preview:opacity-100 transition-opacity flex items-center gap-1 text-[10px] font-semibold text-neutral-700 dark:text-neutral-300"
                        >
                            <ExternalLink class="h-3 w-3" /> Open new tab
                        </a>
                    </div>

                    <!-- Non previewable fallback -->
                    <div
                        v-else
                        class="flex flex-col items-center justify-center p-6 border border-neutral-200/50 dark:border-neutral-800/50 rounded-xl bg-neutral-50/50 dark:bg-neutral-900/40 text-center"
                    >
                        <File class="h-12 w-12 text-neutral-400 mb-2" />
                        <span class="text-xs text-neutral-500 font-semibold mb-3">
                            No inline preview available for this file type
                        </span>
                        <a
                            v-if="activeDocument?.latest_version"
                            :href="getDownloadUrl(activeDocument.latest_version.id)"
                            download
                            class="inline-flex items-center justify-center px-4 py-2 text-xs font-semibold bg-primary text-primary-foreground rounded-lg hover:bg-primary/95 shadow-sm"
                        >
                            <Download class="mr-2 h-4 w-4" /> Download File
                        </a>
                    </div>
                </div>

                <!-- Version History list -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Version History
                        </h3>

                        <!-- Version Upload trigger button -->
                        <Button
                            variant="outline"
                            size="xs"
                            class="h-7 text-[10px] gap-1"
                            @click="$refs.newVersionInput.click()"
                        >
                            <UploadCloud class="h-3 w-3" />
                            <span>Upload New Version</span>
                        </Button>
                        <input
                            ref="newVersionInput"
                            type="file"
                            class="hidden"
                            @change="handleNewVersionChange"
                        />
                    </div>

                    <div
                        v-if="activeDocument?.versions"
                        class="divide-y divide-neutral-200/50 dark:divide-neutral-800/50 border border-neutral-200/50 dark:border-neutral-800/50 rounded-xl overflow-hidden bg-card/10"
                    >
                        <div
                            v-for="ver in activeDocument.versions"
                            :key="ver.id"
                            class="flex items-center justify-between p-3 text-xs"
                        >
                            <div class="flex flex-col gap-0.5">
                                <span class="font-bold text-neutral-800 dark:text-neutral-200 flex items-center gap-1.5">
                                    Version {{ ver.version }}
                                    <Badge v-if="ver.version === activeDocument.latest_version?.version" class="h-4 text-[8px] bg-green-500/10 text-green-500 border-none font-extrabold uppercase">
                                        Current
                                    </Badge>
                                </span>
                                <span class="text-[10px] text-neutral-400 truncate max-w-[200px]" :title="ver.file_name">
                                    {{ ver.file_name }} ({{ ver.formatted_size }})
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="text-[10px] text-neutral-500">
                                    {{ new Date(ver.created_at).toLocaleDateString() }}
                                </span>
                                <a
                                    :href="getDownloadUrl(ver.id)"
                                    download
                                    class="p-1.5 hover:bg-neutral-100 dark:hover:bg-neutral-900 rounded text-neutral-500 hover:text-primary transition-colors"
                                    title="Download this version"
                                >
                                    <Download class="h-4 w-4" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Drawer Actions -->
                <div class="flex items-center gap-3 border-t border-neutral-200/50 dark:border-neutral-800/50 pt-4 mt-auto">
                    <Button
                        variant="outline"
                        class="flex-1 gap-1.5"
                        @click="activeDocument ? openEditDoc(activeDocument) : null"
                    >
                        <Edit2 class="h-4 w-4" />
                        <span>Edit Details</span>
                    </Button>
                    <Button
                        variant="destructive"
                        class="flex-1 gap-1.5"
                        @click="activeDocument ? handleDeleteDoc(activeDocument) : null"
                    >
                        <Trash2 class="h-4 w-4" />
                        <span>Delete Document</span>
                    </Button>
                </div>
            </SheetContent>
        </Sheet>
    </div>
</template>

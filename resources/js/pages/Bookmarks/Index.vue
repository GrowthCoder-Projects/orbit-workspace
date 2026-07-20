<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Bookmark,
    Trash2,
    Edit2,
    Plus,
    Search,
    Star,
    ExternalLink,
    Globe,
    Tag as TagIcon,
    Copy,
    Check,
    MoreVertical,
    Folder,
    FolderPlus,
    AlertTriangle,
    Loader2,
} from '@lucide/vue';
import { ref, computed, watch, onUnmounted } from 'vue';
import { toast } from 'vue-sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardHeader,
    CardContent,
    CardFooter,
} from '@/components/ui/card';
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
import { Skeleton } from '@/components/ui/skeleton';

// Type definitions
interface Category {
    id: number;
    name: string;
    color: string | null;
    icon: string | null;
    bookmarks_count?: number;
}

interface Tag {
    id: number;
    name: string;
    slug: string;
}

interface BookmarkItem {
    id: number;
    url: string;
    title: string | null;
    description: string | null;
    favicon_url: string | null;
    preview_image_url: string | null;
    is_favorite: boolean;
    status: 'pending' | 'fetching' | 'success' | 'failed';
    category_id: number | null;
    category?: Category | null;
    tags: Tag[];
    created_at: string;
}

const props = defineProps<{
    categories: Category[];
    bookmarks: BookmarkItem[];
    tags: Tag[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Bookmarks', href: '/app/bookmarks' }],
    },
});

// Presets for Category Customization
const PRESET_COLORS = [
    { name: 'Red', value: '#ef4444', class: 'bg-red-500' },
    { name: 'Orange', value: '#f97316', class: 'bg-orange-500' },
    { name: 'Yellow', value: '#eab308', class: 'bg-yellow-500' },
    { name: 'Green', value: '#22c55e', class: 'bg-green-500' },
    { name: 'Teal', value: '#14b8a6', class: 'bg-teal-500' },
    { name: 'Blue', value: '#3b82f6', class: 'bg-blue-500' },
    { name: 'Indigo', value: '#6366f1', class: 'bg-indigo-500' },
    { name: 'Purple', value: '#a855f7', class: 'bg-purple-500' },
    { name: 'Pink', value: '#ec4899', class: 'bg-pink-500' },
    { name: 'Slate', value: '#64748b', class: 'bg-slate-500' },
];

const PRESET_ICONS = [
    { name: 'Bookmark', value: 'Bookmark' },
    { name: 'Folder', value: 'Folder' },
    { name: 'Globe', value: 'Globe' },
    { name: 'Tag', value: 'Tag' },
];

// Active State Filters
const activeFilter = ref<'all' | 'favorites' | 'uncategorized' | number>('all');
const searchQuery = ref('');
const selectedTag = ref<string | null>(null);

// Dialog/Modal States
const isBookmarkDialogOpen = ref(false);
const isEditingBookmark = ref(false);
const currentBookmarkId = ref<number | null>(null);

const isCategoryDialogOpen = ref(false);
const isEditingCategory = ref(false);
const currentCategoryId = ref<number | null>(null);

const isDeleteCategoryDialogOpen = ref(false);
const isDeleteBookmarkDialogOpen = ref(false);

// Form States
const bookmarkForm = ref({
    url: '',
    category_id: '' as string | number,
    title: '',
    description: '',
    tagsString: '',
});

const categoryForm = ref({
    name: '',
    color: '#3b82f6',
    icon: 'Bookmark',
});

// UI helpers
const copiedId = ref<number | null>(null);

const copyUrl = (id: number, url: string) => {
    navigator.clipboard.writeText(url).then(() => {
        copiedId.value = id;
        toast.success('URL copied to clipboard!');
        setTimeout(() => {
            if (copiedId.value === id) {
                copiedId.value = null;
            }
        }, 2000);
    });
};

// Filtering Logic
const filteredBookmarks = computed(() => {
    let result = props.bookmarks;

    // Filter by search query
    if (searchQuery.value.trim() !== '') {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (b) =>
                (b.title && b.title.toLowerCase().includes(query)) ||
                (b.description &&
                    b.description.toLowerCase().includes(query)) ||
                b.url.toLowerCase().includes(query) ||
                b.tags.some((t) => t.name.toLowerCase().includes(query)),
        );
    }

    // Filter by active category / filter
    if (activeFilter.value === 'favorites') {
        result = result.filter((b) => b.is_favorite);
    } else if (activeFilter.value === 'uncategorized') {
        result = result.filter((b) => !b.category_id);
    } else if (typeof activeFilter.value === 'number') {
        result = result.filter((b) => b.category_id === activeFilter.value);
    }

    // Filter by active tag
    if (selectedTag.value) {
        result = result.filter((b) =>
            b.tags.some((t) => t.slug === selectedTag.value),
        );
    }

    return result;
});

// Active category helper for forms
const activeCategoryInForm = computed(() => {
    const catId = bookmarkForm.value.category_id;

    if (!catId || catId === 'uncategorized') {
return null;
}

    return props.categories.find((c) => c.id === Number(catId)) || null;
});

// Icon component helper
const getIconComponent = (name: string | null) => {
    switch (name) {
        case 'Folder':
            return Folder;
        case 'Globe':
            return Globe;
        case 'Tag':
            return TagIcon;
        default:
            return Bookmark;
    }
};

// Polling for background metadata fetch
let pollInterval: any = null;

const startPolling = () => {
    if (pollInterval) {
return;
}

    pollInterval = setInterval(() => {
        router.reload({
            only: ['bookmarks'],
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                const hasPending = props.bookmarks.some(
                    (b) => b.status === 'pending' || b.status === 'fetching',
                );

                if (!hasPending) {
                    stopPolling();
                }
            },
        } as any);
    }, 3000);
};

const stopPolling = () => {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
};

watch(
    () => props.bookmarks,
    (newBookmarks) => {
        const hasPending = newBookmarks.some(
            (b) => b.status === 'pending' || b.status === 'fetching',
        );

        if (hasPending) {
            startPolling();
        } else {
            stopPolling();
        }
    },
    { immediate: true, deep: true },
);

onUnmounted(() => {
    stopPolling();
});

// Operations: Bookmarks
const openAddBookmarkDialog = () => {
    isEditingBookmark.value = false;
    currentBookmarkId.value = null;
    bookmarkForm.value = {
        url: '',
        category_id: 'uncategorized',
        title: '',
        description: '',
        tagsString: '',
    };
    isBookmarkDialogOpen.value = true;
};

const openEditBookmarkDialog = (bookmark: BookmarkItem) => {
    isEditingBookmark.value = true;
    currentBookmarkId.value = bookmark.id;
    bookmarkForm.value = {
        url: bookmark.url,
        category_id: bookmark.category_id
            ? bookmark.category_id.toString()
            : 'uncategorized',
        title: bookmark.title || '',
        description: bookmark.description || '',
        tagsString: bookmark.tags.map((t) => t.name).join(', '),
    };
    isBookmarkDialogOpen.value = true;
};

const submitBookmarkForm = () => {
    const tags = bookmarkForm.value.tagsString
        ? bookmarkForm.value.tagsString
              .split(',')
              .map((t) => t.trim())
              .filter((t) => t !== '')
        : [];

    const payload = {
        url: bookmarkForm.value.url,
        category_id:
            bookmarkForm.value.category_id === '' ||
            bookmarkForm.value.category_id === 'uncategorized'
                ? null
                : Number(bookmarkForm.value.category_id),
        tags: tags,
        title: bookmarkForm.value.title,
        description: bookmarkForm.value.description,
    };

    if (isEditingBookmark.value && currentBookmarkId.value) {
        router.put(`/app/bookmarks/${currentBookmarkId.value}`, payload, {
            onSuccess: () => {
                isBookmarkDialogOpen.value = false;
                toast.success('Bookmark updated successfully.');
            },
            onError: (errors) => {
                Object.values(errors).forEach((err) =>
                    toast.error(err as string),
                );
            },
        });
    } else {
        router.post('/app/bookmarks', payload, {
            onSuccess: () => {
                isBookmarkDialogOpen.value = false;
                toast.success(
                    'Bookmark added. Scraper started in the background.',
                );
            },
            onError: (errors) => {
                Object.values(errors).forEach((err) =>
                    toast.error(err as string),
                );
            },
        });
    }
};

const toggleFavorite = (bookmark: BookmarkItem) => {
    router.patch(
        `/app/bookmarks/${bookmark.id}/favorite`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                // Toast handled by server flash message listener
            },
        },
    );
};

const confirmDeleteBookmark = (bookmark: BookmarkItem) => {
    currentBookmarkId.value = bookmark.id;
    isDeleteBookmarkDialogOpen.value = true;
};

const deleteBookmark = () => {
    if (!currentBookmarkId.value) {
return;
}

    router.delete(`/app/bookmarks/${currentBookmarkId.value}`, {
        onSuccess: () => {
            isDeleteBookmarkDialogOpen.value = false;
            toast.success('Bookmark deleted.');
        },
    });
};

// Operations: Categories
const openAddCategoryDialog = () => {
    isEditingCategory.value = false;
    currentCategoryId.value = null;
    categoryForm.value = {
        name: '',
        color: '#3b82f6',
        icon: 'Bookmark',
    };
    isCategoryDialogOpen.value = true;
};

const openEditCategoryDialog = (category: Category) => {
    isEditingCategory.value = true;
    currentCategoryId.value = category.id;
    categoryForm.value = {
        name: category.name,
        color: category.color || '#3b82f6',
        icon: category.icon || 'Bookmark',
    };
    isCategoryDialogOpen.value = true;
};

const submitCategoryForm = () => {
    const payload = { ...categoryForm.value };

    if (isEditingCategory.value && currentCategoryId.value) {
        router.put(`/app/bookmark-categories/${currentCategoryId.value}`, payload, {
            onSuccess: () => {
                isCategoryDialogOpen.value = false;
                toast.success('Category updated successfully.');
            },
            onError: (errors) => {
                Object.values(errors).forEach((err) =>
                    toast.error(err as string),
                );
            },
        });
    } else {
        router.post('/app/bookmark-categories', payload, {
            onSuccess: () => {
                isCategoryDialogOpen.value = false;
                toast.success('Category created successfully.');
            },
            onError: (errors) => {
                Object.values(errors).forEach((err) =>
                    toast.error(err as string),
                );
            },
        });
    }
};

const confirmDeleteCategory = (category: Category) => {
    currentCategoryId.value = category.id;
    isDeleteCategoryDialogOpen.value = true;
};

const deleteCategory = () => {
    if (!currentCategoryId.value) {
return;
}

    router.delete(`/app/bookmark-categories/${currentCategoryId.value}`, {
        onSuccess: () => {
            isDeleteCategoryDialogOpen.value = false;

            if (
                typeof activeFilter.value === 'number' &&
                activeFilter.value === currentCategoryId.value
            ) {
                activeFilter.value = 'all';
            }

            toast.success('Category deleted.');
        },
    });
};

// Hostname parser for aesthetic link tags
const getDomainName = (urlStr: string) => {
    try {
        const domain = parse_url_domain(urlStr);

        return domain;
    } catch {
        return urlStr;
    }
};

const parse_url_domain = (urlStr: string) => {
    const match = urlStr.match(
        /^(?:https?:\/\/)?(?:[^@\n]+@)?(?:www\.)?([^:\/\n?]+)/im,
    );

    return match ? match[1] : urlStr;
};

// Preset gradient builder for fallback card banner
const getFallbackGradient = (title: string | null) => {
    const str = title || 'Default';
    let hash = 0;

    for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
    }

    const color1 = `hsl(${Math.abs(hash % 360)}, 65%, 45%)`;
    const color2 = `hsl(${Math.abs((hash + 60) % 360)}, 70%, 35%)`;

    return `linear-gradient(135deg, ${color1}, ${color2})`;
};
</script>

<template>
    <div class="flex min-h-[calc(100vh-6rem)] flex-col gap-6 p-6 lg:flex-row">
        <Head title="Bookmarks" />

        <!-- LEFT SIDEBAR PANEL: Categories & Filters -->
        <div class="flex w-full shrink-0 flex-col gap-6 lg:w-64">
            <!-- Sidebar Navigation -->
            <Card class="border-sidebar-border bg-sidebar">
                <CardHeader
                    class="flex flex-row items-center justify-between px-4 pt-4 pb-3"
                >
                    <h3
                        class="text-sm font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Quick Filters
                    </h3>
                </CardHeader>
                <CardContent class="flex flex-col gap-1 p-2">
                    <!-- All Bookmarks -->
                    <button
                        @click="
                            activeFilter = 'all';
                            selectedTag = null;
                        "
                        class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors"
                        :class="
                            activeFilter === 'all' && !selectedTag
                                ? 'bg-primary/10 font-semibold text-primary'
                                : 'text-foreground/75 hover:bg-muted/50'
                        "
                    >
                        <Globe class="h-4 w-4" />
                        <span>All Bookmarks</span>
                        <span
                            class="ml-auto rounded-full bg-muted px-1.5 py-0.5 text-xs font-normal text-muted-foreground"
                            >{{ bookmarks.length }}</span
                        >
                    </button>

                    <!-- Favorites -->
                    <button
                        @click="
                            activeFilter = 'favorites';
                            selectedTag = null;
                        "
                        class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors"
                        :class="
                            activeFilter === 'favorites'
                                ? 'bg-primary/10 font-semibold text-primary'
                                : 'text-foreground/75 hover:bg-muted/50'
                        "
                    >
                        <Star
                            class="h-4 w-4"
                            :class="
                                activeFilter === 'favorites'
                                    ? 'fill-yellow-500 text-yellow-500'
                                    : ''
                            "
                        />
                        <span>Favorites</span>
                        <span
                            class="ml-auto rounded-full bg-muted px-1.5 py-0.5 text-xs font-normal text-muted-foreground"
                        >
                            {{ bookmarks.filter((b) => b.is_favorite).length }}
                        </span>
                    </button>

                    <!-- Uncategorized -->
                    <button
                        @click="
                            activeFilter = 'uncategorized';
                            selectedTag = null;
                        "
                        class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors"
                        :class="
                            activeFilter === 'uncategorized'
                                ? 'bg-primary/10 font-semibold text-primary'
                                : 'text-foreground/75 hover:bg-muted/50'
                        "
                    >
                        <Folder class="h-4 w-4" />
                        <span>Uncategorized</span>
                        <span
                            class="ml-auto rounded-full bg-muted px-1.5 py-0.5 text-xs font-normal text-muted-foreground"
                        >
                            {{ bookmarks.filter((b) => !b.category_id).length }}
                        </span>
                    </button>
                </CardContent>
            </Card>

            <!-- Categories Panel -->
            <Card class="flex-1 border-sidebar-border bg-sidebar">
                <CardHeader
                    class="flex flex-row items-center justify-between px-4 pt-4 pb-2"
                >
                    <h3
                        class="text-sm font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Categories
                    </h3>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8 text-primary hover:bg-primary/10"
                        @click="openAddCategoryDialog"
                    >
                        <FolderPlus class="h-4 w-4" />
                    </Button>
                </CardHeader>

                <CardContent class="flex flex-col gap-1 p-2">
                    <!-- Categories List -->
                    <div
                        v-if="categories.length === 0"
                        class="px-4 py-6 text-center"
                    >
                        <Folder
                            class="mx-auto mb-2 h-8 w-8 text-muted-foreground/30"
                        />
                        <p class="text-xs text-muted-foreground">
                            No categories yet
                        </p>
                    </div>

                    <div
                        v-else
                        class="flex max-h-[350px] flex-col gap-1 overflow-y-auto"
                    >
                        <div
                            v-for="category in categories"
                            :key="category.id"
                            class="group flex w-full items-center justify-between rounded-md px-3 py-1.5 text-sm transition-colors"
                            :class="
                                activeFilter === category.id
                                    ? 'bg-primary/10 font-semibold text-primary'
                                    : 'text-foreground/75 hover:bg-muted/50'
                            "
                        >
                            <button
                                @click="
                                    activeFilter = category.id;
                                    selectedTag = null;
                                "
                                class="mr-2 flex flex-1 items-center gap-3 overflow-hidden text-left"
                            >
                                <span
                                    class="h-3 w-3 shrink-0 rounded-full border border-black/10"
                                    :style="{
                                        backgroundColor:
                                            category.color || '#3b82f6',
                                    }"
                                ></span>
                                <span class="truncate">{{
                                    category.name
                                }}</span>
                                <span
                                    class="ml-auto rounded-full bg-muted px-1.5 py-0.5 text-xs font-normal text-muted-foreground"
                                >
                                    {{ category.bookmarks_count ?? 0 }}
                                </span>
                            </button>

                            <!-- Category Options -->
                            <div
                                class="opacity-0 transition-opacity group-hover:opacity-100"
                            >
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="h-6 w-6"
                                        >
                                            <MoreVertical class="h-3.5 w-3.5" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent
                                        align="end"
                                        class="w-36"
                                    >
                                        <DropdownMenuItem
                                            @click="
                                                openEditCategoryDialog(category)
                                            "
                                        >
                                            <Edit2 class="mr-2 h-3.5 w-3.5" />
                                            Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            class="text-destructive hover:bg-destructive/10"
                                            @click="
                                                confirmDeleteCategory(category)
                                            "
                                        >
                                            <Trash2 class="mr-2 h-3.5 w-3.5" />
                                            Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Tags Filter Panel -->
            <Card
                v-if="tags.length > 0"
                class="border-sidebar-border bg-sidebar"
            >
                <CardHeader class="px-4 pt-4 pb-2">
                    <h3
                        class="text-sm font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Tags
                    </h3>
                </CardHeader>
                <CardContent
                    class="flex max-h-[180px] flex-wrap gap-1.5 overflow-y-auto p-4 pt-1"
                >
                    <Badge
                        v-for="tag in tags"
                        :key="tag.id"
                        variant="secondary"
                        class="cursor-pointer transition-colors"
                        :class="
                            selectedTag === tag.slug
                                ? 'bg-primary text-primary-foreground hover:bg-primary/95'
                                : 'hover:bg-muted-foreground/15'
                        "
                        @click="
                            selectedTag =
                                selectedTag === tag.slug ? null : tag.slug
                        "
                    >
                        <TagIcon class="mr-1 h-3 w-3" />
                        {{ tag.name }}
                    </Badge>
                </CardContent>
            </Card>
        </div>

        <!-- RIGHT MAIN PANEL: Search & Bookmark Cards Grid -->
        <div class="flex flex-1 flex-col gap-6">
            <!-- Header Search Bar & Add Button -->
            <div
                class="flex flex-col items-center justify-between gap-4 sm:flex-row"
            >
                <div class="relative w-full sm:max-w-md">
                    <Search
                        class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="searchQuery"
                        placeholder="Search bookmarks by URL, title, tags..."
                        class="h-10 w-full bg-card pl-9"
                    />
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="absolute top-1/2 right-3 -translate-y-1/2 text-xs text-muted-foreground hover:text-foreground"
                    >
                        Clear
                    </button>
                </div>

                <div
                    class="flex w-full shrink-0 items-center justify-end gap-2 sm:w-auto"
                >
                    <Badge
                        v-if="selectedTag"
                        variant="outline"
                        class="flex h-10 items-center gap-1.5 border-dashed px-3"
                    >
                        <span>Tag: #{{ selectedTag }}</span>
                        <button
                            class="text-muted-foreground hover:text-foreground"
                            @click="selectedTag = null"
                        >
                            &times;
                        </button>
                    </Badge>
                    <Button
                        class="flex h-10 w-full items-center gap-2 px-4 shadow-md sm:w-auto"
                        @click="openAddBookmarkDialog"
                    >
                        <Plus class="h-4 w-4" />
                        <span>Add Bookmark</span>
                    </Button>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="filteredBookmarks.length === 0"
                class="flex flex-1 flex-col items-center justify-center rounded-xl border-2 border-dashed border-muted bg-card p-12 text-center shadow-sm"
            >
                <div
                    class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10"
                >
                    <Bookmark class="h-8 w-8 text-primary" />
                </div>
                <h3 class="mb-2 text-lg font-semibold">No bookmarks found</h3>
                <p class="mb-6 max-w-sm text-sm text-muted-foreground">
                    We couldn't find any bookmarks. Add some bookmark links, or
                    change your search/filter settings.
                </p>
                <Button
                    variant="outline"
                    class="flex items-center gap-2"
                    @click="openAddBookmarkDialog"
                >
                    <Plus class="h-4 w-4" />
                    Create First Bookmark
                </Button>
            </div>

            <!-- Cards Grid -->
            <div
                v-else
                class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3"
            >
                <Card
                    v-for="bookmark in filteredBookmarks"
                    :key="bookmark.id"
                    class="group relative flex flex-col justify-between overflow-hidden rounded-xl border-border bg-card transition-all duration-300 hover:shadow-lg"
                >
                    <!-- Loading / Fetching Skeleton state -->
                    <template
                        v-if="
                            bookmark.status === 'pending' ||
                            bookmark.status === 'fetching'
                        "
                    >
                        <div
                            class="flex h-36 animate-pulse items-center justify-center bg-muted"
                        >
                            <Loader2
                                class="h-8 w-8 animate-spin text-primary"
                            />
                        </div>
                        <CardHeader class="pt-4 pb-2">
                            <div class="mb-2 flex items-center gap-2">
                                <Skeleton class="h-4 w-4 rounded-full" />
                                <Skeleton class="h-4 w-28" />
                            </div>
                            <Skeleton class="mb-2 h-6 w-full" />
                        </CardHeader>
                        <CardContent class="pb-4">
                            <Skeleton class="mb-1.5 h-4 w-full" />
                            <Skeleton class="h-4 w-4/5" />
                        </CardContent>
                        <CardFooter
                            class="flex gap-2 border-t border-muted/50 py-3"
                        >
                            <Skeleton class="h-8 w-1/3" />
                            <Skeleton class="ml-auto h-8 w-8" />
                            <Skeleton class="h-8 w-8" />
                        </CardFooter>
                    </template>

                    <!-- Regular Card Content -->
                    <template v-else>
                        <!-- Top Image Banner -->
                        <div
                            class="relative h-36 w-full shrink-0 overflow-hidden border-b border-muted/30"
                        >
                            <!-- OpenGraph Image fallback if failed or missing -->
                            <img
                                v-if="
                                    bookmark.status === 'success' &&
                                    bookmark.preview_image_url
                                "
                                :src="bookmark.preview_image_url"
                                :alt="bookmark.title || 'Preview'"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                @error="
                                    (e: any) =>
                                        (e.target.style.display = 'none')
                                "
                            />
                            <!-- Beautiful Abstract Gradient Banner if missing og:image -->
                            <div
                                class="h-full w-full opacity-80"
                                :style="{
                                    background: getFallbackGradient(
                                        bookmark.title,
                                    ),
                                }"
                            ></div>

                            <!-- Floating Badges -->
                            <div
                                class="absolute top-3 left-3 flex flex-wrap gap-1"
                            >
                                <Badge
                                    v-if="bookmark.category"
                                    class="border border-black/10 text-xs font-semibold text-white shadow-sm"
                                    :style="{
                                        backgroundColor:
                                            bookmark.category.color ||
                                            '#3b82f6',
                                    }"
                                >
                                    {{ bookmark.category.name }}
                                </Badge>
                                <Badge
                                    v-if="bookmark.status === 'failed'"
                                    variant="destructive"
                                    class="text-xs"
                                >
                                    <AlertTriangle class="mr-1 h-3 w-3" />
                                    Failed Fetch
                                </Badge>
                            </div>
                        </div>

                        <!-- Card Header & Content -->
                        <div class="flex flex-1 flex-col justify-between">
                            <CardHeader class="px-5 pt-4 pb-2">
                                <!-- Favicon & Domain info -->
                                <div class="mb-1.5 flex items-center gap-2">
                                    <img
                                        v-if="bookmark.favicon_url"
                                        :src="bookmark.favicon_url"
                                        alt="favicon"
                                        class="h-4 w-4 rounded-sm object-contain"
                                        @error="
                                            (e: any) =>
                                                (e.target.style.display =
                                                    'none')
                                        "
                                    />
                                    <Globe
                                        v-else
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <span
                                        class="max-w-[180px] truncate font-mono text-xs text-muted-foreground"
                                    >
                                        {{ getDomainName(bookmark.url) }}
                                    </span>
                                </div>

                                <!-- Bookmark Title -->
                                <h4
                                    class="line-clamp-2 text-base font-bold text-foreground transition-colors hover:text-primary"
                                >
                                    <a
                                        :href="bookmark.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex items-start gap-1"
                                    >
                                        <span>{{
                                            bookmark.title || bookmark.url
                                        }}</span>
                                        <ExternalLink
                                            class="mt-1 h-3.5 w-3.5 shrink-0 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100"
                                        />
                                    </a>
                                </h4>
                            </CardHeader>

                            <!-- Excerpt Description -->
                            <CardContent class="flex-1 px-5 pb-4">
                                <p
                                    class="mb-4 line-clamp-3 text-sm text-muted-foreground"
                                >
                                    {{
                                        bookmark.description ||
                                        'No description available for this bookmark.'
                                    }}
                                </p>

                                <!-- Tags List -->
                                <div
                                    v-if="bookmark.tags.length > 0"
                                    class="flex flex-wrap gap-1.5"
                                >
                                    <Badge
                                        v-for="tag in bookmark.tags"
                                        :key="tag.id"
                                        variant="outline"
                                        class="bg-muted/40 text-xs"
                                    >
                                        {{ tag.name }}
                                    </Badge>
                                </div>
                            </CardContent>
                        </div>

                        <!-- Actions Footer -->
                        <CardFooter
                            class="flex items-center justify-between border-t border-muted/30 bg-muted/5 px-5 py-3"
                        >
                            <div class="flex items-center gap-1">
                                <!-- Copy Link -->
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8 text-muted-foreground hover:bg-muted hover:text-foreground"
                                    @click="copyUrl(bookmark.id, bookmark.url)"
                                >
                                    <Check
                                        v-if="copiedId === bookmark.id"
                                        class="h-4 w-4 text-green-600"
                                    />
                                    <Copy v-else class="h-4 w-4" />
                                </Button>

                                <!-- Edit Bookmark -->
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8 text-muted-foreground hover:bg-muted hover:text-foreground"
                                    @click="openEditBookmarkDialog(bookmark)"
                                >
                                    <Edit2 class="h-4 w-4" />
                                </Button>

                                <!-- Delete Bookmark -->
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                    @click="confirmDeleteBookmark(bookmark)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>

                            <!-- Favorite Toggle -->
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 text-muted-foreground hover:text-foreground"
                                @click="toggleFavorite(bookmark)"
                            >
                                <Star
                                    class="h-4 w-4 transition-colors"
                                    :class="
                                        bookmark.is_favorite
                                            ? 'scale-110 fill-yellow-500 text-yellow-500'
                                            : 'text-muted-foreground'
                                    "
                                />
                            </Button>
                        </CardFooter>
                    </template>
                </Card>
            </div>
        </div>

        <!-- MODAL: ADD / EDIT BOOKMARK -->
        <Dialog v-model:open="isBookmarkDialogOpen">
            <DialogContent class="sm:max-w-[480px]">
                <DialogHeader>
                    <DialogTitle>{{
                        isEditingBookmark ? 'Edit Bookmark' : 'Add Bookmark'
                    }}</DialogTitle>
                    <DialogDescription>
                        {{
                            isEditingBookmark
                                ? 'Modify the details of your saved bookmark link.'
                                : 'Paste a URL to create a bookmark. We will fetch its metadata in the background.'
                        }}
                    </DialogDescription>
                </DialogHeader>

                <form
                    @submit.prevent="submitBookmarkForm"
                    class="space-y-4 py-2"
                >
                    <!-- URL input -->
                    <div class="space-y-1.5">
                        <Label
                            for="bookmark-url"
                            class="text-xs font-semibold tracking-wide text-foreground/80 uppercase"
                            >URL Link</Label
                        >
                        <Input
                            id="bookmark-url"
                            v-model="bookmarkForm.url"
                            type="url"
                            placeholder="https://example.com"
                            required
                            :disabled="isEditingBookmark"
                            class="h-10 rounded-lg border border-input bg-card shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                    </div>

                    <!-- Category select -->
                    <div class="space-y-1.5">
                        <Label
                            for="bookmark-category"
                            class="text-xs font-semibold tracking-wide text-foreground/80 uppercase"
                            >Category</Label
                        >
                        <Select v-model="bookmarkForm.category_id">
                            <SelectTrigger
                                id="bookmark-category"
                                class="h-10 w-full rounded-lg border border-input bg-card shadow-sm transition-colors hover:bg-muted/50"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        v-if="activeCategoryInForm"
                                        class="h-2.5 w-2.5 shrink-0 rounded-full border border-black/10"
                                        :style="{
                                            backgroundColor:
                                                activeCategoryInForm.color ||
                                                '#3b82f6',
                                        }"
                                    ></span>
                                    <SelectValue placeholder="Uncategorized" />
                                </div>
                            </SelectTrigger>
                            <SelectContent
                                class="rounded-lg border border-border bg-popover shadow-lg"
                            >
                                <SelectItem
                                    value="uncategorized"
                                    class="transition-colors hover:bg-muted/50"
                                >
                                    <div class="flex items-center gap-2 py-0.5">
                                        <span
                                            class="h-2.5 w-2.5 shrink-0 rounded-full border border-dashed border-muted-foreground/50"
                                        ></span>
                                        <span class="text-sm font-medium"
                                            >Uncategorized</span
                                        >
                                    </div>
                                </SelectItem>
                                <SelectItem
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id.toString()"
                                    class="transition-colors hover:bg-muted/50"
                                >
                                    <div class="flex items-center gap-2 py-0.5">
                                        <span
                                            class="h-2.5 w-2.5 shrink-0 rounded-full border border-black/10"
                                            :style="{
                                                backgroundColor:
                                                    category.color || '#3b82f6',
                                            }"
                                        ></span>
                                        <span class="text-sm font-medium">{{
                                            category.name
                                        }}</span>
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Custom fields for Edit Mode -->
                    <template v-if="isEditingBookmark">
                        <!-- Custom Title -->
                        <div class="space-y-1.5">
                            <Label
                                for="bookmark-title"
                                class="text-xs font-semibold tracking-wide text-foreground/80 uppercase"
                                >Custom Title</Label
                            >
                            <Input
                                id="bookmark-title"
                                v-model="bookmarkForm.title"
                                type="text"
                                placeholder="Edit title"
                                required
                                class="h-10 rounded-lg border border-input bg-card shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            />
                        </div>

                        <!-- Custom Description -->
                        <div class="space-y-1.5">
                            <Label
                                for="bookmark-desc"
                                class="text-xs font-semibold tracking-wide text-foreground/80 uppercase"
                                >Custom Description</Label
                            >
                            <textarea
                                id="bookmark-desc"
                                v-model="bookmarkForm.description"
                                rows="3"
                                placeholder="Edit description..."
                                class="flex w-full resize-none rounded-lg border border-input bg-card px-3 py-2 text-sm shadow-sm transition-all placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            ></textarea>
                        </div>
                    </template>

                    <!-- Tags input -->
                    <div class="space-y-1.5">
                        <Label
                            for="bookmark-tags"
                            class="text-xs font-semibold tracking-wide text-foreground/80 uppercase"
                            >Tags (comma-separated)</Label
                        >
                        <Input
                            id="bookmark-tags"
                            v-model="bookmarkForm.tagsString"
                            type="text"
                            placeholder="e.g. design, tutorial, laravel"
                            class="h-10 rounded-lg border border-input bg-card shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                    </div>

                    <DialogFooter class="pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isBookmarkDialogOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button type="submit">
                            {{
                                isEditingBookmark
                                    ? 'Save Changes'
                                    : 'Add Bookmark'
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- MODAL: ADD / EDIT CATEGORY -->
        <Dialog v-model:open="isCategoryDialogOpen">
            <DialogContent class="sm:max-w-[420px]">
                <DialogHeader>
                    <DialogTitle>{{
                        isEditingCategory ? 'Edit Category' : 'Create Category'
                    }}</DialogTitle>
                    <DialogDescription>
                        Give your category a name, pick a color indicator, and
                        choose an icon.
                    </DialogDescription>
                </DialogHeader>

                <form
                    @submit.prevent="submitCategoryForm"
                    class="space-y-4 py-2"
                >
                    <!-- Name -->
                    <div class="space-y-1.5">
                        <Label
                            for="cat-name"
                            class="text-xs font-semibold tracking-wide text-foreground/80 uppercase"
                            >Category Name</Label
                        >
                        <Input
                            id="cat-name"
                            v-model="categoryForm.name"
                            placeholder="e.g. Design Inspiration"
                            required
                            class="h-10 rounded-lg border border-input bg-card shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                    </div>

                    <!-- Color Picker -->
                    <div class="space-y-2">
                        <Label
                            class="text-xs font-semibold tracking-wide text-foreground/80 uppercase"
                            >Color Indicator</Label
                        >
                        <div class="flex flex-wrap gap-2.5 pt-1">
                            <button
                                v-for="color in PRESET_COLORS"
                                :key="color.value"
                                type="button"
                                class="h-7 w-7 rounded-full border border-black/10 transition-transform duration-200"
                                :class="[
                                    color.class,
                                    categoryForm.color === color.value
                                        ? 'scale-125 ring-2 ring-ring ring-offset-2'
                                        : 'hover:scale-110',
                                ]"
                                @click="categoryForm.color = color.value"
                            ></button>
                        </div>
                    </div>

                    <!-- Icon Selector -->
                    <div class="space-y-1.5">
                        <Label
                            for="cat-icon"
                            class="text-xs font-semibold tracking-wide text-foreground/80 uppercase"
                            >Icon</Label
                        >
                        <Select v-model="categoryForm.icon">
                            <SelectTrigger
                                id="cat-icon"
                                class="h-10 w-full rounded-lg border border-input bg-card shadow-sm transition-colors hover:bg-muted/50"
                            >
                                <div class="flex items-center gap-2">
                                    <component
                                        :is="
                                            getIconComponent(categoryForm.icon)
                                        "
                                        class="h-4 w-4 shrink-0 text-muted-foreground"
                                    />
                                    <SelectValue placeholder="Select icon" />
                                </div>
                            </SelectTrigger>
                            <SelectContent
                                class="rounded-lg border border-border bg-popover shadow-lg"
                            >
                                <SelectItem
                                    v-for="icon in PRESET_ICONS"
                                    :key="icon.value"
                                    :value="icon.value"
                                    class="transition-colors hover:bg-muted/50"
                                >
                                    <div class="flex items-center gap-2 py-0.5">
                                        <component
                                            :is="getIconComponent(icon.value)"
                                            class="h-4 w-4 shrink-0 text-muted-foreground"
                                        />
                                        <span class="text-sm font-medium">{{
                                            icon.name
                                        }}</span>
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <DialogFooter class="pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isCategoryDialogOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button type="submit">
                            {{ isEditingCategory ? 'Save Changes' : 'Create' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- DELETE BOOKMARK CONFIRM DIALOG -->
        <Dialog v-model:open="isDeleteBookmarkDialogOpen">
            <DialogContent class="sm:max-w-[400px]">
                <DialogHeader>
                    <DialogTitle>Delete Bookmark</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this bookmark? This
                        action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 pt-4 sm:gap-0">
                    <Button
                        variant="outline"
                        @click="isDeleteBookmarkDialogOpen = false"
                        >Cancel</Button
                    >
                    <Button variant="destructive" @click="deleteBookmark"
                        >Delete</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- DELETE CATEGORY CONFIRM DIALOG -->
        <Dialog v-model:open="isDeleteCategoryDialogOpen">
            <DialogContent class="sm:max-w-[400px]">
                <DialogHeader>
                    <DialogTitle>Delete Category</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this category? The
                        bookmarks belonging to this category will become
                        <span class="font-semibold text-foreground"
                            >Uncategorized</span
                        >
                        but will NOT be deleted.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 pt-4 sm:gap-0">
                    <Button
                        variant="outline"
                        @click="isDeleteCategoryDialogOpen = false"
                        >Cancel</Button
                    >
                    <Button variant="destructive" @click="deleteCategory"
                        >Delete</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>

<style scoped>
/* Scoped custom styling if any */
</style>

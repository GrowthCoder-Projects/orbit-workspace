<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Plus,
    Search,
    Trash2,
    Edit2,
    Library,
    Copy,
    Check,
    Folder,
    X,
    ChevronRight,
    ChevronDown,
    ChevronLeft,
    FileText,
} from '@lucide/vue';
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { toast } from 'vue-sonner';
import TiptapEditor from '@/components/TiptapEditor.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useConfirm } from '@/composables/useConfirm';
import ArticleTreeNode from './ArticleTreeNode.vue';

const { confirm } = useConfirm();

type CategoryType = {
    id: number;
    name: string;
    slug: string;
    articles_count?: number;
    created_at: string;
};

type ArticleType = {
    id: number;
    title: string;
    slug: string;
    parent_article_id: number | null;
    content: string | null;
    categories: CategoryType[];
    updated_at: string;
};

const props = defineProps<{
    categories: CategoryType[];
    articles: ArticleType[];
    selectedArticleId?: number | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Knowledge Base', href: '/app/kb' }],
    },
});

// Selection & View States
const selectedArticleId = ref<number | null>(props.selectedArticleId || null);
const isEditing = ref(false);
const isCreating = ref(false);
const searchQuery = ref('');

// Modals/Dialogs
const isCategoryDialogOpen = ref(false);
const editingCategoryId = ref<number | null>(null);
const categoryForm = ref({ name: '' });

// Tree expanded states
const expandedCategories = ref<Record<number, boolean>>({});
const expandedArticles = ref<Record<number, boolean>>({});

// Editor Form State
const articleForm = ref({
    id: null as number | null,
    title: '',
    content: '',
    parent_article_id: null as number | null,
    category_ids: [] as number[],
});

// Reader Pane reference for injecting copy buttons
const readerPaneRef = ref<HTMLElement | null>(null);

// Get current active article
const activeArticle = computed(() => {
    if (selectedArticleId.value === null) {
return null;
}

    return props.articles.find((a) => a.id === selectedArticleId.value) || null;
});

// Auto-select first article if none selected and articles exist
onMounted(() => {
    if (selectedArticleId.value === null && props.articles.length > 0) {
        selectedArticleId.value = props.articles[0].id;
    }

    // Expand categories by default
    props.categories.forEach((cat) => {
        expandedCategories.value[cat.id] = true;
    });
    // Add copy buttons to code blocks
    addCopyButtons();
});

// Watch URL selected ID
watch(
    () => props.selectedArticleId,
    (newId) => {
        if (newId !== undefined) {
            selectedArticleId.value = newId;
        }
    },
);

// Watch active article changes to reset view mode and inject copy buttons
watch(selectedArticleId, () => {
    isEditing.value = false;
    isCreating.value = false;
    addCopyButtons();
});

// Watch edit mode toggle
watch(isEditing, (editing) => {
    if (!editing) {
        addCopyButtons();
    }
});

// Recursive search check: does parent contain this child?
const isDescendant = (childId: number, parentId: number): boolean => {
    const child = props.articles.find((a) => a.id === childId);

    if (!child || !child.parent_article_id) {
return false;
}

    if (child.parent_article_id === parentId) {
return true;
}

    return isDescendant(child.parent_article_id, parentId);
};

// Filtered articles list for parent selection in Editor (prevent circular dependencies)
const potentialParentArticles = computed(() => {
    if (isCreating.value) {
return props.articles;
}

    const currentId = articleForm.value.id;

    if (!currentId) {
return props.articles;
}

    return props.articles.filter((a) => {
        // Cannot select self
        if (a.id === currentId) {
return false;
}

        // Cannot select a child/descendant as parent
        if (isDescendant(a.id, currentId)) {
return false;
}

        return true;
    });
});

// Root articles grouped by Category (for tree display)
const getRootArticlesForCategory = (categoryId: number) => {
    const term = searchQuery.value.toLowerCase().trim();

    // Find all root articles belonging to this category
    const rootArticles = props.articles.filter(
        (art) =>
            art.parent_article_id === null &&
            art.categories.some((cat) => cat.id === categoryId),
    );

    if (!term) {
return rootArticles;
}

    // Filter helper: does article or its children match search term?
    const matchesSearch = (art: ArticleType): boolean => {
        if (art.title.toLowerCase().includes(term)) {
return true;
}

        const children = props.articles.filter(
            (child) => child.parent_article_id === art.id,
        );

        return children.some(matchesSearch);
    };

    return rootArticles.filter(matchesSearch);
};

// Handle article selection
const selectArticle = (id: number) => {
    selectedArticleId.value = id;
    isEditing.value = false;
    isCreating.value = false;

    // Maintain state in URL
    router.replace({
        url: `/kb?id=${id}`,
        preserveState: true,
        preserveScroll: true,
    });
};

// CRUD: Categories
const openNewCategoryDialog = () => {
    editingCategoryId.value = null;
    categoryForm.value.name = '';
    isCategoryDialogOpen.value = true;
};

const openEditCategoryDialog = (cat: CategoryType) => {
    editingCategoryId.value = cat.id;
    categoryForm.value.name = cat.name;
    isCategoryDialogOpen.value = true;
};

const saveCategory = () => {
    if (!categoryForm.value.name.trim()) {
return;
}

    if (editingCategoryId.value) {
        // Update
        router.patch(
            `/app/kb/categories/${editingCategoryId.value}`,
            {
                name: categoryForm.value.name,
            },
            {
                onSuccess: () => {
                    toast.success('Category updated successfully.');
                    isCategoryDialogOpen.value = false;
                },
            },
        );
    } else {
        // Create
        router.post(
            '/app/kb/categories',
            {
                name: categoryForm.value.name,
            },
            {
                onSuccess: () => {
                    toast.success('Category created successfully.');
                    isCategoryDialogOpen.value = false;
                },
            },
        );
    }
};

const deleteCategory = async (cat: CategoryType) => {
    const isConfirmed = await confirm({
        title: 'Delete Category',
        message: `Are you sure you want to delete Category "${cat.name}"? Articles will not be deleted, but they will be detached from this category.`,
        confirmText: 'Delete',
        cancelText: 'Cancel',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(`/app/kb/categories/${cat.id}`, {
            onSuccess: () => {
                toast.success('Category deleted successfully.');
            },
        });
    }
};

// CRUD: Articles
const startCreateArticle = () => {
    isCreating.value = true;
    isEditing.value = false;
    articleForm.value = {
        id: null,
        title: '',
        content: '',
        parent_article_id: null,
        category_ids:
            props.categories.length > 0 ? [props.categories[0].id] : [],
    };
};

const startEditArticle = () => {
    if (!activeArticle.value) {
return;
}

    isEditing.value = true;
    isCreating.value = false;
    articleForm.value = {
        id: activeArticle.value.id,
        title: activeArticle.value.title,
        content: activeArticle.value.content || '',
        parent_article_id: activeArticle.value.parent_article_id,
        category_ids: activeArticle.value.categories.map((c) => c.id),
    };
};

const cancelEdit = () => {
    isEditing.value = false;
    isCreating.value = false;
};

const saveArticle = () => {
    if (!articleForm.value.title.trim()) {
        toast.error('Title is required.');

        return;
    }

    if (isCreating.value) {
        router.post(
            '/app/kb/articles',
            {
                title: articleForm.value.title,
                parent_article_id: articleForm.value.parent_article_id,
                category_ids: articleForm.value.category_ids,
            },
            {
                onSuccess: (page) => {
                    toast.success('Article created successfully.');
                    isCreating.value = false;

                    // Grab selected id from props
                    if (props.selectedArticleId) {
                        selectedArticleId.value = props.selectedArticleId;
                    }
                },
            },
        );
    } else if (isEditing.value && activeArticle.value) {
        router.patch(
            `/app/kb/articles/${activeArticle.value.id}`,
            {
                title: articleForm.value.title,
                content: articleForm.value.content,
                parent_article_id: articleForm.value.parent_article_id,
                category_ids: articleForm.value.category_ids,
            },
            {
                onSuccess: () => {
                    toast.success('Article updated successfully.');
                    isEditing.value = false;
                },
            },
        );
    }
};

const deleteArticle = async () => {
    if (!activeArticle.value) {
return;
}

    const isConfirmed = await confirm({
        title: 'Delete Article',
        message: `Are you sure you want to delete "${activeArticle.value.title}"? All of its sub-articles will also be deleted recursively.`,
        confirmText: 'Delete',
        cancelText: 'Cancel',
        variant: 'destructive',
    });

    if (isConfirmed) {
        router.delete(`/app/kb/articles/${activeArticle.value.id}`, {
            onSuccess: () => {
                toast.success('Article deleted successfully.');
                selectedArticleId.value = null;

                if (props.articles.length > 0) {
                    selectedArticleId.value = props.articles[0].id;
                }
            },
        });
    }
};

// Dynamic injection of unique IDs for headings and scroll
const processedContent = computed(() => {
    if (!activeArticle.value || !activeArticle.value.content) {
return '';
}

    const html = activeArticle.value.content;
    const regex = /<h([1-4])([^>]*)>(.*?)<\/h\1>/gi;
    let index = 0;

    return html.replace(regex, (match, level, attrs, text) => {
        return `<h${level} id="heading-${index++}"${attrs}>${text}</h${level}>`;
    });
});

// Dynamic sticky Table of Contents computed
const tocItems = computed(() => {
    if (!activeArticle.value || !activeArticle.value.content) {
return [];
}

    const html = activeArticle.value.content;
    const regex = /<h([1-4])[^>]*>(.*?)<\/h\1>/gi;
    const items = [];
    let match;
    let index = 0;

    while ((match = regex.exec(html)) !== null) {
        const level = parseInt(match[1]);
        const text = match[2].replace(/<[^>]*>/g, '');
        items.push({ level, text, id: `heading-${index++}` });
    }

    return items;
});

const scrollToHeading = (id: string) => {
    const el = document.getElementById(id);

    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

// Copy to clipboard code snippet handler
const addCopyButtons = () => {
    nextTick(() => {
        if (!readerPaneRef.value) {
return;
}

        const preElements = readerPaneRef.value.querySelectorAll('pre');
        preElements.forEach((pre) => {
            if (pre.querySelector('.copy-btn')) {
return;
}

            pre.style.position = 'relative';

            const button = document.createElement('button');
            button.className =
                'copy-btn absolute top-2 right-2 p-1.5 rounded bg-neutral-800 hover:bg-neutral-700 text-neutral-400 hover:text-white transition-colors border border-neutral-700 text-xs font-medium cursor-pointer flex items-center gap-1 select-none z-10';
            button.innerHTML = `
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                </svg>
                <span>Copy</span>
            `;

            button.addEventListener('click', () => {
                const code =
                    pre.querySelector('code')?.innerText || pre.innerText;
                navigator.clipboard.writeText(code).then(() => {
                    button.innerHTML = `
                        <svg class="h-3.5 w-3.5 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span class="text-emerald-500">Copied!</span>
                    `;
                    setTimeout(() => {
                        button.innerHTML = `
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            <span>Copy</span>
                        `;
                    }, 2000);
                });
            });

            pre.appendChild(button);
        });
    });
};

const toggleCategorySelection = (catId: number) => {
    const idx = articleForm.value.category_ids.indexOf(catId);

    if (idx > -1) {
        articleForm.value.category_ids.splice(idx, 1);
    } else {
        articleForm.value.category_ids.push(catId);
    }
};
</script>

<template>
    <Head title="Knowledge Base" />

    <div
        class="flex h-[calc(100vh-4rem)] overflow-hidden border-t bg-white dark:bg-neutral-900"
    >
        <!-- LEFT SIDEBAR: Categories & Articles Tree -->
        <aside
            class="dark:bg-neutral-905 flex h-full w-72 shrink-0 flex-col border-r bg-neutral-50/50"
        >
            <!-- Search & Actions Header -->
            <div class="shrink-0 space-y-3 border-b p-4">
                <div class="relative flex items-center">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-neutral-400 select-none dark:text-neutral-500"
                    />
                    <Input
                        v-model="searchQuery"
                        placeholder="Search articles..."
                        class="h-9 w-full border-neutral-200 bg-white pl-9 text-sm dark:border-neutral-800 dark:bg-neutral-900"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        size="sm"
                        variant="outline"
                        class="h-8 flex-1 text-xs"
                        @click="openNewCategoryDialog"
                    >
                        <Plus class="mr-1 h-3.5 w-3.5" />
                        Category
                    </Button>
                    <Button
                        size="sm"
                        class="h-8 flex-1 bg-primary text-xs text-white hover:bg-primary/95"
                        @click="startCreateArticle"
                    >
                        <Plus class="mr-1 h-3.5 w-3.5" />
                        Article
                    </Button>
                </div>
            </div>

            <!-- Categories Tree Scrollable -->
            <div class="flex-1 space-y-4 overflow-y-auto p-3">
                <div v-if="categories.length === 0" class="py-6 text-center">
                    <p class="text-xs text-neutral-400 italic">
                        No categories yet. Click "Category" to create one.
                    </p>
                </div>

                <div v-for="cat in categories" :key="cat.id" class="space-y-1">
                    <!-- Category Header row -->
                    <div
                        class="group dark:hover:bg-neutral-850 flex items-center justify-between rounded-md px-2 py-1.5 hover:bg-neutral-100/50"
                    >
                        <button
                            type="button"
                            class="flex cursor-pointer items-center gap-1.5 text-xs font-semibold tracking-wider text-neutral-500 uppercase select-none hover:text-neutral-800 dark:text-neutral-400 dark:hover:text-neutral-200"
                            @click="
                                expandedCategories[cat.id] =
                                    !expandedCategories[cat.id]
                            "
                        >
                            <ChevronRight
                                v-if="!expandedCategories[cat.id]"
                                class="h-3 w-3 shrink-0"
                            />
                            <ChevronDown v-else class="h-3 w-3 shrink-0" />
                            <Folder
                                class="h-3.5 w-3.5 shrink-0 text-amber-500"
                            />
                            <span class="max-w-[120px] truncate">{{
                                cat.name
                            }}</span>
                        </button>

                        <!-- Mini Action Menu -->
                        <div
                            class="flex items-center gap-1.5 opacity-0 transition-opacity group-hover:opacity-100"
                        >
                            <button
                                type="button"
                                @click="openEditCategoryDialog(cat)"
                                class="rounded p-1 text-neutral-400 hover:bg-neutral-200 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300"
                                title="Edit Category"
                            >
                                <Edit2 class="h-3 w-3" />
                            </button>
                            <button
                                type="button"
                                @click="deleteCategory(cat)"
                                class="text-neutral-405 hover:text-red-650 rounded p-1 hover:bg-red-50 dark:hover:bg-red-950/20"
                                title="Delete Category"
                            >
                                <Trash2 class="h-3 w-3" />
                            </button>
                        </div>
                    </div>

                    <!-- Category Articles list -->
                    <div
                        v-if="expandedCategories[cat.id]"
                        class="mt-1 ml-3 space-y-0.5 border-l border-neutral-200 pl-2 dark:border-neutral-800"
                    >
                        <div
                            v-if="
                                getRootArticlesForCategory(cat.id).length === 0
                            "
                            class="px-3 py-1"
                        >
                            <span class="text-[11px] text-neutral-400 italic"
                                >Empty category</span
                            >
                        </div>
                        <ArticleTreeNode
                            v-for="art in getRootArticlesForCategory(cat.id)"
                            :key="art.id"
                            :article="art"
                            :all-articles="articles"
                            :selected-article-id="selectedArticleId"
                            :expanded-articles="expandedArticles"
                            :depth="0"
                            @select="selectArticle"
                        />
                    </div>
                </div>
            </div>
        </aside>

        <!-- CENTER PANEL: Reading or Editing View -->
        <main
            class="flex h-full flex-1 flex-col overflow-y-auto bg-white dark:bg-neutral-900"
        >
            <!-- Mode 1: Creating New Article -->
            <div
                v-if="isCreating"
                class="mx-auto w-full max-w-4xl space-y-6 p-6 md:p-8"
            >
                <div class="flex items-center gap-2 text-sm text-neutral-500">
                    <Library class="h-4 w-4" />
                    <span>Knowledge Base</span>
                    <span>/</span>
                    <span
                        class="font-medium text-neutral-900 dark:text-neutral-200"
                        >New Article</span
                    >
                </div>

                <div class="space-y-4">
                    <div class="space-y-1">
                        <Label for="new-art-title">Article Title</Label>
                        <Input
                            id="new-art-title"
                            v-model="articleForm.title"
                            placeholder="e.g. Server Setup, Deployment Pipeline, Git Workflow"
                            class="py-6 text-lg font-semibold"
                        />
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <!-- Parent selection -->
                        <div class="space-y-2">
                            <Label for="new-art-parent"
                                >Parent Article (Optional)</Label
                            >
                            <select
                                id="new-art-parent"
                                v-model="articleForm.parent_article_id"
                                class="h-10 w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none dark:border-neutral-800 dark:bg-neutral-900"
                            >
                                <option :value="null">
                                    None (Root Article)
                                </option>
                                <option
                                    v-for="art in potentialParentArticles"
                                    :key="art.id"
                                    :value="art.id"
                                >
                                    {{ art.title }}
                                </option>
                            </select>
                        </div>

                        <!-- Categories checkboxes (Only enabled if it is a Root Article) -->
                        <div
                            v-if="articleForm.parent_article_id === null"
                            class="space-y-2"
                        >
                            <Label>Categories</Label>
                            <div class="flex flex-wrap gap-3 py-1.5">
                                <label
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    class="flex cursor-pointer items-center gap-2 text-sm text-neutral-600 dark:text-neutral-300"
                                >
                                    <Checkbox
                                        :checked="
                                            articleForm.category_ids.includes(
                                                cat.id,
                                            )
                                        "
                                        @update:checked="
                                            toggleCategorySelection(cat.id)
                                        "
                                    />
                                    <span>{{ cat.name }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 border-t pt-4">
                    <Button
                        type="button"
                        class="bg-primary text-white hover:bg-primary/95"
                        @click="saveArticle"
                        :disabled="!articleForm.title"
                    >
                        Create Article
                    </Button>
                    <Button variant="ghost" type="button" @click="cancelEdit">
                        Cancel
                    </Button>
                </div>
            </div>

            <!-- Mode 2: Editing Existing Article -->
            <div
                v-else-if="isEditing && activeArticle"
                class="mx-auto w-full max-w-4xl space-y-6 p-6 md:p-8"
            >
                <div class="flex items-center justify-between">
                    <div
                        class="flex items-center gap-2 text-sm text-neutral-500"
                    >
                        <Library class="h-4 w-4" />
                        <span>Knowledge Base</span>
                        <span>/</span>
                        <span
                            class="font-medium text-neutral-900 dark:text-neutral-200"
                            >Edit Article</span
                        >
                    </div>
                    <Button variant="ghost" size="sm" @click="cancelEdit">
                        <X class="mr-1 h-4 w-4" />
                        Close Editor
                    </Button>
                </div>

                <div class="space-y-4">
                    <div class="space-y-1">
                        <Label for="edit-art-title">Article Title</Label>
                        <Input
                            id="edit-art-title"
                            v-model="articleForm.title"
                            placeholder="Article title..."
                            class="py-6 text-lg font-semibold"
                        />
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <!-- Parent selection -->
                        <div class="space-y-2">
                            <Label for="edit-art-parent"
                                >Parent Article (Optional)</Label
                            >
                            <select
                                id="edit-art-parent"
                                v-model="articleForm.parent_article_id"
                                class="h-10 w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none dark:border-neutral-800 dark:bg-neutral-900"
                            >
                                <option :value="null">
                                    None (Root Article)
                                </option>
                                <option
                                    v-for="art in potentialParentArticles"
                                    :key="art.id"
                                    :value="art.id"
                                >
                                    {{ art.title }}
                                </option>
                            </select>
                        </div>

                        <!-- Categories checkboxes (Only enabled if it is a Root Article) -->
                        <div
                            v-if="articleForm.parent_article_id === null"
                            class="space-y-2"
                        >
                            <Label>Categories</Label>
                            <div class="flex flex-wrap gap-3 py-1.5">
                                <label
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    class="flex cursor-pointer items-center gap-2 text-sm text-neutral-600 dark:text-neutral-300"
                                >
                                    <Checkbox
                                        :checked="
                                            articleForm.category_ids.includes(
                                                cat.id,
                                            )
                                        "
                                        @update:checked="
                                            toggleCategorySelection(cat.id)
                                        "
                                    />
                                    <span>{{ cat.name }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1 pt-2">
                        <Label>Content</Label>
                        <TiptapEditor
                            v-model="articleForm.content"
                            placeholder="Start writing technical documentation..."
                        />
                    </div>
                </div>

                <div class="flex items-center gap-3 border-t pt-4">
                    <Button
                        type="button"
                        class="bg-primary font-medium text-white hover:bg-primary/95"
                        @click="saveArticle"
                        :disabled="!articleForm.title"
                    >
                        Save Changes
                    </Button>
                    <Button variant="ghost" type="button" @click="cancelEdit">
                        Cancel
                    </Button>
                </div>
            </div>

            <!-- Mode 3: Reading Pane (Article Detail) -->
            <div v-else-if="activeArticle" class="flex flex-1 overflow-hidden">
                <!-- Center Reading Lane -->
                <div
                    ref="readerPaneRef"
                    class="mx-auto w-full max-w-3xl flex-1 overflow-y-auto px-6 py-8 md:px-12"
                >
                    <!-- Categories list -->
                    <div class="mb-4 flex flex-wrap gap-1.5">
                        <Badge
                            v-for="cat in activeArticle.categories"
                            :key="cat.id"
                            variant="secondary"
                            class="bg-neutral-100 text-neutral-700 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700"
                        >
                            {{ cat.name }}
                        </Badge>
                    </div>

                    <!-- Article Header Row -->
                    <div
                        class="mb-6 flex items-start justify-between gap-4 border-b pb-4"
                    >
                        <div>
                            <h1
                                class="text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-neutral-50"
                            >
                                {{ activeArticle.title }}
                            </h1>
                            <p
                                class="mt-2 text-xs text-neutral-400 dark:text-neutral-500"
                            >
                                Last updated:
                                {{
                                    new Date(
                                        activeArticle.updated_at,
                                    ).toLocaleDateString()
                                }}
                            </p>
                        </div>

                        <!-- Read actions -->
                        <div class="flex shrink-0 items-center gap-1.5">
                            <Button
                                size="sm"
                                variant="outline"
                                class="h-8"
                                @click="startEditArticle"
                            >
                                <Edit2 class="mr-1.5 h-3.5 w-3.5" />
                                Edit
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                class="text-neutral-405 hover:text-red-650 h-8 hover:bg-red-50 dark:hover:bg-red-950/20"
                                @click="deleteArticle"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>

                    <!-- Article Body content -->
                    <div
                        v-if="activeArticle.content"
                        class="prose dark:prose-invert prose-neutral dark:prose-neutral prose-sm md:prose-base prose-pre:p-0 max-w-none"
                        v-html="processedContent"
                    ></div>
                    <div v-else class="py-12 text-center">
                        <p class="text-neutral-400 italic">
                            This article has no content yet. Click "Edit" to
                            start documenting.
                        </p>
                        <Button
                            size="sm"
                            class="mt-4 bg-primary text-white hover:bg-primary/95"
                            @click="startEditArticle"
                        >
                            <Edit2 class="mr-1.5 h-3.5 w-3.5" />
                            Add Content
                        </Button>
                    </div>
                </div>

                <!-- RIGHT SIDEBAR: Table of Contents (TOC) -->
                <aside
                    v-if="tocItems.length > 0"
                    class="hidden w-56 shrink-0 overflow-y-auto border-l p-6 lg:block"
                >
                    <h3
                        class="mb-4 text-xs font-bold tracking-wider text-neutral-500 uppercase dark:text-neutral-400"
                    >
                        On this page
                    </h3>
                    <ul class="space-y-2.5">
                        <li
                            v-for="item in tocItems"
                            :key="item.id"
                            :style="{
                                paddingLeft: `${(item.level - 1) * 12}px`,
                            }"
                        >
                            <button
                                type="button"
                                @click="scrollToHeading(item.id)"
                                class="block max-w-full cursor-pointer truncate text-left text-xs leading-relaxed font-medium text-neutral-500 transition-colors select-none hover:text-primary dark:text-neutral-400 dark:hover:text-primary"
                            >
                                {{ item.text }}
                            </button>
                        </li>
                    </ul>
                </aside>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="flex flex-1 flex-col items-center justify-center bg-neutral-50/20 p-8 text-center dark:bg-neutral-900/10"
            >
                <div
                    class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-400 dark:bg-neutral-800 dark:text-neutral-500"
                >
                    <Library class="h-6 w-6" />
                </div>
                <h3
                    class="text-lg font-semibold text-neutral-900 dark:text-neutral-200"
                >
                    No article selected
                </h3>
                <p class="mt-1 max-w-sm text-sm text-neutral-400">
                    Select an article from the left navigation tree, or create a
                    new one to get started.
                </p>
                <div class="mt-6 flex items-center gap-3">
                    <Button
                        size="sm"
                        variant="outline"
                        @click="openNewCategoryDialog"
                    >
                        New Category
                    </Button>
                    <Button
                        size="sm"
                        class="bg-primary text-white hover:bg-primary/95"
                        @click="startCreateArticle"
                    >
                        Create Article
                    </Button>
                </div>
            </div>
        </main>
    </div>

    <!-- Category Modal dialog -->
    <Dialog v-model:open="isCategoryDialogOpen">
        <DialogContent
            class="border bg-white sm:max-w-md dark:border-neutral-800 dark:bg-neutral-900"
        >
            <DialogHeader>
                <DialogTitle class="text-neutral-900 dark:text-neutral-50">
                    {{ editingCategoryId ? 'Rename Category' : 'New Category' }}
                </DialogTitle>
            </DialogHeader>
            <div class="space-y-4 py-2">
                <div class="space-y-2">
                    <Label
                        for="cat-name"
                        class="text-neutral-700 dark:text-neutral-300"
                        >Category Name</Label
                    >
                    <Input
                        id="cat-name"
                        v-model="categoryForm.name"
                        placeholder="Deployment, Server, Logic, General..."
                        class="col-span-3 border-neutral-200 dark:border-neutral-800"
                        @keyup.enter="saveCategory"
                    />
                </div>
            </div>
            <DialogFooter>
                <Button variant="ghost" @click="isCategoryDialogOpen = false">
                    Cancel
                </Button>
                <Button
                    @click="saveCategory"
                    :disabled="!categoryForm.name.trim()"
                >
                    Save
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<style>
/* Smooth scrolling inside reader */
.scroll-smooth {
    scroll-behavior: smooth;
}

/* TipTap content custom code blocks styling override inside article viewer */
.prose pre {
    position: relative;
}

.prose code {
    background-color: transparent;
    color: inherit;
    padding: 0;
    font-size: inherit;
    border-radius: 0;
}
</style>

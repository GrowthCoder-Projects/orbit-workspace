<script setup lang="ts">
import { ChevronRight, ChevronDown, FileText } from '@lucide/vue';
import { computed } from 'vue';

type ArticleType = {
    id: number;
    title: string;
    parent_article_id: number | null;
    content: string | null;
    updated_at: string;
};

const props = defineProps<{
    article: ArticleType;
    allArticles: ArticleType[];
    selectedArticleId: number | null;
    expandedArticles: Record<number, boolean>;
    depth: number;
}>();

const emit = defineEmits<{
    (e: 'select', id: number): void;
}>();

// Get direct children of this article
const children = computed(() => {
    return props.allArticles.filter(
        (a) => a.parent_article_id === props.article.id,
    );
});

const hasChildren = computed(() => {
    return children.value.length > 0;
});

const isExpanded = computed(() => {
    return !!props.expandedArticles[props.article.id];
});

const toggleExpand = () => {
    props.expandedArticles[props.article.id] =
        !props.expandedArticles[props.article.id];
};

const select = () => {
    emit('select', props.article.id);
};
</script>

<template>
    <div class="space-y-0.5">
        <div
            class="group flex cursor-pointer items-center gap-1.5 rounded-md px-2 py-1.5 text-sm transition-colors select-none"
            :class="[
                selectedArticleId === article.id
                    ? 'dark:bg-neutral-850 bg-neutral-100 font-semibold text-neutral-900 dark:text-neutral-100'
                    : 'dark:hover:bg-neutral-850 text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-200',
            ]"
            :style="{ paddingLeft: depth * 16 + 8 + 'px' }"
            @click="select"
        >
            <button
                v-if="hasChildren"
                type="button"
                @click.stop="toggleExpand"
                class="rounded p-0.5 text-neutral-400 transition-colors hover:bg-neutral-200 hover:text-neutral-600 dark:hover:bg-neutral-700 dark:hover:text-neutral-300"
            >
                <ChevronRight
                    v-if="!isExpanded"
                    class="h-3.5 w-3.5 transition-transform"
                />
                <ChevronDown v-else class="h-3.5 w-3.5 transition-transform" />
            </button>
            <div v-else class="w-4.5"></div>

            <FileText
                class="h-4 w-4 shrink-0 text-neutral-400 dark:text-neutral-500"
            />
            <span class="flex-1 truncate text-left">{{
                article.title || 'Untitled Article'
            }}</span>
        </div>

        <!-- Recursive children rendering -->
        <div v-if="hasChildren && isExpanded" class="space-y-0.5">
            <ArticleTreeNode
                v-for="child in children"
                :key="child.id"
                :article="child"
                :all-articles="allArticles"
                :selected-article-id="selectedArticleId"
                :expanded-articles="expandedArticles"
                :depth="depth + 1"
                @select="$emit('select', $event)"
            />
        </div>
    </div>
</template>

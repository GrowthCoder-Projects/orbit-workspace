<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ChevronDown } from '@lucide/vue';
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useInitials } from '@/composables/useInitials';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { getInitials } = useInitials();
</script>

<template>
    <DropdownMenu v-if="user">
        <DropdownMenuTrigger :as-child="true">
            <Button
                variant="ghost"
                class="relative flex h-10 cursor-pointer items-center gap-2 rounded-full px-2 py-1.5 transition-colors focus-within:ring-2 focus-within:ring-primary focus-within:ring-offset-2 hover:bg-neutral-100 dark:hover:bg-neutral-800"
            >
                <Avatar
                    class="h-8 w-8 overflow-hidden rounded-full border border-neutral-200 shadow-sm dark:border-neutral-800"
                >
                    <AvatarImage
                        v-if="user.avatar"
                        :src="user.avatar"
                        :alt="user.name"
                    />
                    <AvatarFallback
                        class="rounded-full bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
                    >
                        {{ getInitials(user.name) }}
                    </AvatarFallback>
                </Avatar>
                <span
                    class="hidden text-sm font-medium text-neutral-700 md:inline-block dark:text-neutral-300"
                >
                    {{ user.name }}
                </span>
                <ChevronDown
                    class="hidden h-4 w-4 text-neutral-500 md:inline-block"
                />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
            align="end"
            class="mt-1 w-56 rounded-xl border border-neutral-200/80 shadow-lg dark:border-neutral-800"
        >
            <UserMenuContent :user="user" />
        </DropdownMenuContent>
    </DropdownMenu>
</template>

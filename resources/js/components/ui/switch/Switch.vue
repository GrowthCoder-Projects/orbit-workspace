<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        checked?: boolean;
        disabled?: boolean;
        class?: string;
    }>(),
    {
        checked: false,
        disabled: false,
        class: '',
    }
);

const emit = defineEmits<{
    (e: 'update:checked', value: boolean): void;
    (e: 'change', value: boolean): void;
}>();

const toggle = () => {
    if (!props.disabled) {
        const newValue = !props.checked;
        emit('update:checked', newValue);
        emit('change', newValue);
    }
};
</script>

<template>
    <button
        type="button"
        role="switch"
        :aria-checked="checked"
        :disabled="disabled"
        @click="toggle"
        class="peer inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50"
        :class="[
            checked ? 'bg-primary' : 'bg-muted-foreground/30',
            props.class
        ]"
    >
        <span
            class="pointer-events-none block h-4 w-4 rounded-full bg-background shadow-lg ring-0 transition-transform duration-200"
            :class="checked ? 'translate-x-4' : 'translate-x-0'"
        />
    </button>
</template>

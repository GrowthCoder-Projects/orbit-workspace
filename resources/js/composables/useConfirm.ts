import { ref } from 'vue';

export interface ConfirmOptions {
    title?: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'destructive' | 'warning' | 'primary';
}

const isOpen = ref(false);
const options = ref<ConfirmOptions>({
    title: 'Konfirmasi',
    message: '',
    confirmText: 'Ya, Hapus',
    cancelText: 'Batal',
    variant: 'destructive',
});

let resolveCallback: ((value: boolean) => void) | null = null;

export function useConfirm() {
    const confirm = (opts: string | ConfirmOptions) => {
        return new Promise<boolean>((resolve) => {
            if (typeof opts === 'string') {
                options.value = {
                    title: 'Konfirmasi Hapus',
                    message: opts,
                    confirmText: 'Hapus',
                    cancelText: 'Batal',
                    variant: 'destructive',
                };
            } else {
                options.value = {
                    title: opts.title || 'Konfirmasi Hapus',
                    message: opts.message,
                    confirmText: opts.confirmText || 'Hapus',
                    cancelText: opts.cancelText || 'Batal',
                    variant: opts.variant || 'destructive',
                };
            }

            isOpen.value = true;
            resolveCallback = resolve;
        });
    };

    const handleConfirm = () => {
        isOpen.value = false;

        if (resolveCallback) {
            resolveCallback(true);
            resolveCallback = null;
        }
    };

    const handleCancel = () => {
        isOpen.value = false;

        if (resolveCallback) {
            resolveCallback(false);
            resolveCallback = null;
        }
    };

    return {
        isOpen,
        options,
        confirm,
        handleConfirm,
        handleCancel,
    };
}

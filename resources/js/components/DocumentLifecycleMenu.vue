<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    type:
        | 'quotes'
        | 'sales'
        | 'sale-returns'
        | 'purchases'
        | 'purchase-returns'
        | 'receipts'
        | 'payments'
        | 'inventory-adjustments'
        | 'expenses'
        | 'payrolls'
        | 'payroll-periods';
    record: {
        id: number;
        is_active?: boolean;
        voided_at?: string | null;
        can_void?: boolean;
        void_blocked_reason?: string | null;
    };
    allowDeactivate?: boolean;
    canActivate?: boolean;
    canDeactivate?: boolean;
    canVoid?: boolean;
    voidDisabledReason?: string;
}>();

const showDialog = ref(false);
const reason = ref('');
const processing = ref(false);

const blockedReason = computed(
    () =>
        props.voidDisabledReason ??
        props.record.void_blocked_reason ??
        (props.record.can_void === false
            ? 'No se puede anular este documento.'
            : null),
);

function toggle() {
    const action = props.record.is_active === false ? 'activate' : 'deactivate';

    router.patch(
        `/${props.type}/${props.record.id}/${action}`,
        {},
        { preserveScroll: true },
    );
}

function openDialog() {
    reason.value = '';
    showDialog.value = true;
}

function confirmVoid() {
    if (!reason.value.trim()) {
        return;
    }

    processing.value = true;

    router.post(
        `/${props.type}/${props.record.id}/void`,
        { reason: reason.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                showDialog.value = false;
                reason.value = '';
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <div v-if="!record.voided_at" class="flex flex-wrap justify-end gap-2">
        <Button
            v-if="allowDeactivate !== false && (canActivate || canDeactivate)"
            variant="outline"
            size="sm"
            @click="toggle"
        >
            {{ record.is_active === false ? 'Activar' : 'Inactivar' }}
        </Button>
        <Button
            v-if="canVoid"
            variant="destructive"
            size="sm"
            @click="openDialog"
        >
            Anular
        </Button>

        <Dialog v-model:open="showDialog">
            <DialogContent>
                <template v-if="blockedReason">
                    <DialogHeader>
                        <DialogTitle>No se puede anular</DialogTitle>
                        <DialogDescription>
                            {{ blockedReason }}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="showDialog = false">
                            Cerrar
                        </Button>
                    </DialogFooter>
                </template>
                <template v-else>
                    <DialogHeader>
                        <DialogTitle>Anular documento</DialogTitle>
                        <DialogDescription>
                            Indica el motivo. Esta acción no se puede revertir.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="space-y-2">
                        <Label for="void-reason">Motivo</Label>
                        <Input
                            id="void-reason"
                            v-model="reason"
                            placeholder="Motivo de anulación"
                            maxlength="500"
                        />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="showDialog = false">
                            Cancelar
                        </Button>
                        <Button
                            variant="destructive"
                            :disabled="processing || !reason.trim()"
                            @click="confirmVoid"
                        >
                            Anular
                        </Button>
                    </DialogFooter>
                </template>
            </DialogContent>
        </Dialog>
    </div>
    <span v-else class="text-xs text-muted-foreground">Anulado</span>
</template>

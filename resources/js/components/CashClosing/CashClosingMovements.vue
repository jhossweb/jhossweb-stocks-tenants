<script setup lang="ts">
import { Plus, Trash2 } from 'lucide-vue-next';
import { reactive, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatAmount } from '@/composables/Finance/useCoinFormat';
import type { CashMovementData } from '@/types/Finance/CashClosing';

const movements = defineModel<CashMovementData[]>('movements', {
    required: true,
});

const newMovement = reactive<{
    cm_type: 'inflow' | 'outflow';
    cm_amount: number | undefined;
    cm_description: string;
}>({
    cm_type: 'inflow',
    cm_amount: undefined,
    cm_description: '',
});

const amountError = ref(false);

function addMovement() {
    if (!newMovement.cm_amount || newMovement.cm_amount <= 0) {
        amountError.value = true;

        return;
    }

    amountError.value = false;

    movements.value.push({
        cm_type: newMovement.cm_type,
        cm_amount: newMovement.cm_amount,
        cm_description: newMovement.cm_description.trim(),
        cm_reference: null,
    });

    newMovement.cm_amount = undefined;
    newMovement.cm_description = '';
}

function removeMovement(index: number) {
    movements.value.splice(index, 1);
}
</script>

<template>
    <div>
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <Label for="mv_type">Tipo</Label>
                <select
                    id="mv_type"
                    v-model="newMovement.cm_type"
                    class="mt-1 block h-10 w-40 rounded-lg border border-input bg-background px-3 text-sm"
                >
                    <option value="inflow">Ingreso</option>
                    <option value="outflow">Egreso</option>
                </select>
            </div>

            <div>
                <Label for="mv_amount">Monto</Label>
                <Input
                    id="mv_amount"
                    v-model.number="newMovement.cm_amount"
                    type="number"
                    step="0.0001"
                    min="0"
                    class="mt-1 w-36"
                    placeholder="0.00"
                    :aria-invalid="amountError || undefined"
                    @input="amountError = false"
                />
                <p
                    v-if="amountError"
                    id="mv_amount-error"
                    class="mt-1 text-xs text-destructive"
                    role="alert"
                >
                    Ingresa un monto mayor a 0.
                </p>
            </div>

            <div>
                <Label for="mv_desc">Descripción</Label>
                <Input
                    id="mv_desc"
                    v-model="newMovement.cm_description"
                    type="text"
                    class="mt-1 w-72"
                    placeholder="¿Para qué es?"
                    @keyup.enter="addMovement"
                />
            </div>

            <Button variant="secondary" size="lg" @click="addMovement">
                <Plus class="mr-2 size-4" aria-hidden="true" />
                Agregar
            </Button>
        </div>

        <div v-if="movements.length > 0" class="mt-5 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/40">
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold text-zinc-400"
                        >
                            Tipo
                        </th>
                        <th
                            class="px-4 py-3 text-right text-xs font-semibold text-zinc-400"
                        >
                            Monto
                        </th>
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold text-zinc-400"
                        >
                            Descripción
                        </th>
                        <th class="w-12 px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800">
                    <tr
                        v-for="(mv, idx) in movements"
                        :key="idx"
                        class="transition-colors hover:bg-zinc-800/20"
                    >
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold uppercase"
                                :class="
                                    mv.cm_type === 'inflow'
                                        ? 'bg-green-900/30 text-green-400'
                                        : 'bg-red-900/30 text-red-400'
                                "
                            >
                                {{
                                    mv.cm_type === 'inflow'
                                        ? 'Ingreso'
                                        : 'Egreso'
                                }}
                            </span>
                        </td>
                        <td
                            class="px-4 py-3 text-right font-mono text-base font-semibold tabular-nums"
                            :class="
                                mv.cm_type === 'inflow'
                                    ? 'text-green-400'
                                    : 'text-red-400'
                            "
                        >
                            {{ mv.cm_type === 'inflow' ? '+ ' : '- '
                            }}{{ formatAmount(mv.cm_amount) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-300">
                            {{ mv.cm_description }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-9"
                                :aria-label="`Eliminar movimiento ${mv.cm_description}`"
                                @click="removeMovement(idx)"
                            >
                                <Trash2
                                    class="size-4 text-red-400"
                                    aria-hidden="true"
                                />
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p v-else class="mt-3 text-sm text-zinc-500">
            Sin movimientos manuales. Añade ingresos o egresos de efectivo si es
            necesario.
        </p>
    </div>
</template>

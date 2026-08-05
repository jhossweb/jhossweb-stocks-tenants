<script setup lang="ts">
import { AlertTriangle } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import type { QuoteAvailability } from '@/types/Quotes';

const props = defineProps<{
    availability: QuoteAvailability;
}>();

const formatQty = (value: number) =>
    Number(value).toLocaleString(undefined, { maximumFractionDigits: 4 });
</script>

<template>
    <Alert variant="destructive">
        <AlertTriangle class="size-4" />
        <AlertTitle>
            No hay existencias suficientes para convertir la cotización
        </AlertTitle>
        <AlertDescription>
            <p class="mb-3 text-sm">
                Ajuste el inventario en los almacenes indicados antes de
                convertir la cotización a factura.
            </p>
            <div
                class="overflow-x-auto rounded-lg border border-destructive/30"
            >
                <table class="w-full text-left text-xs">
                    <thead
                        class="border-b border-destructive/30 bg-destructive/10"
                    >
                        <tr
                            class="text-[10px] font-bold tracking-wide uppercase"
                        >
                            <th class="px-3 py-2">Producto</th>
                            <th class="px-3 py-2">Almacén</th>
                            <th class="px-3 py-2 text-right">Requerido</th>
                            <th class="px-3 py-2 text-right">Disponible</th>
                            <th class="px-3 py-2 text-right">Falta</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-destructive/20">
                        <tr
                            v-for="(item, index) in props.availability.missing"
                            :key="index"
                        >
                            <td class="px-3 py-2">
                                <div class="font-semibold">
                                    {{ item.pro_name }}
                                    <span
                                        class="ml-1 font-mono text-[10px] opacity-70"
                                    >
                                        {{ item.pro_und }}
                                    </span>
                                </div>
                                <p
                                    v-for="(parent, i) in item.via_parent"
                                    :key="i"
                                    class="text-[10px] opacity-70"
                                >
                                    (necesario para {{ parent }})
                                </p>
                            </td>
                            <td class="px-3 py-2">{{ item.warehouse_name }}</td>
                            <td
                                class="px-3 py-2 text-right font-mono tabular-nums"
                            >
                                {{ formatQty(item.required) }}
                            </td>
                            <td
                                class="px-3 py-2 text-right font-mono tabular-nums"
                            >
                                {{ formatQty(item.available) }}
                            </td>
                            <td
                                class="px-3 py-2 text-right font-mono font-bold tabular-nums"
                            >
                                {{ formatQty(item.shortage) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </AlertDescription>
    </Alert>
</template>

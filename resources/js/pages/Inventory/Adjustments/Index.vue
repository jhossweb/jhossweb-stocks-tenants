<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardList } from 'lucide-vue-next';
import { formatDate } from '@/composables/useDate';

interface Adjustment {
    id: number;
    inv_number: string;
    inv_date: string;
    inv_notes: string;
    inv_status: string;
    created_at: string;
    movements: any[];
    transactionType?: { trans_type_code: string; trans_type_description: string };
}

const { adjustments } = defineProps<{
    adjustments: {
        data: Adjustment[];
        links: any[];
    }
}>();
</script>

<template>
    <Head title="Ajustes de Inventario" />

    <div class="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between rounded-xl border bg-card px-6 py-4 shadow-sm">
            <div class="flex items-center gap-4">
                <Link
                    href="/inventory"
                    class="text-muted-foreground hover:text-foreground rounded-full p-2 transition-colors hover:bg-accent"
                >
                    <ArrowLeft class="size-5" />
                </Link>
                <div>
                    <h1 class="text-xl font-bold">Ajustes de Inventario</h1>
                    <p class="text-xs text-muted-foreground">Gestión de ajustes por merma, deterioro y vencido.</p>
                </div>
            </div>
            <Link
                href="/inventory/adjustments/create"
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-all hover:bg-primary/90"
            >
                <ClipboardList class="size-4" /> Nuevo Ajuste
            </Link>
        </div>

        <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50 text-[10px] font-bold uppercase text-muted-foreground">
                        <tr>
                            <th class="px-4 py-4 text-left">N° Ajuste</th>
                            <th class="px-4 py-4 text-left">Fecha</th>
                            <th class="px-4 py-4 text-left">Notas</th>
                            <th class="px-4 py-4 text-center">Items</th>
                            <th class="px-4 py-4 text-center">Estado</th>
                            <th class="w-12 px-4 py-4" />
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="adjustments.data.length === 0">
                            <td colspan="6" class="px-4 py-12 text-center text-muted-foreground">
                                <ClipboardList class="mx-auto mb-4 size-12 text-muted-foreground/30" />
                                <p>No hay ajustes registrados</p>
                            </td>
                        </tr>
                        <tr v-for="adj in adjustments.data" :key="adj.id" class="hover:bg-muted/30 transition-colors">
                            <td class="px-4 py-4 font-mono text-xs">#{{ adj.inv_number }}</td>
                            <td class="px-4 py-4">{{ formatDate(adj.inv_date) }}</td>
                            <td class="max-w-[200px] truncate px-4 py-4 text-muted-foreground">{{ adj.inv_notes || '-' }}</td>
                            <td class="px-4 py-4 text-center">
                                <span class="rounded-full bg-muted/30 px-2 py-1 text-xs text-muted-foreground">
                                    {{ adj.movements?.length || 0 }} productos
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="rounded-full bg-green-500/10 px-2 py-1 text-xs text-green-500">
                                    {{ adj.inv_status === 'completed' ? 'Completado' : adj.inv_status }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <Link :href="`/inventory/adjustments/${adj.id}/edit`" class="text-xs text-primary hover:text-primary/80">Ver Detalles</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="adjustments.links.length > 3" class="flex justify-center border-t px-6 py-4">
                <div class="flex gap-1">
                    <template v-for="(link, key) in adjustments.links" :key="key">
                        <div v-if="link.url === null" class="cursor-not-allowed rounded border px-4 py-2 text-sm text-muted-foreground" v-html="link.label"></div>
                        <Link v-else :href="link.url" class="rounded border px-4 py-2 text-sm text-muted-foreground transition-colors hover:bg-accent" :class="{ 'border-primary bg-primary font-bold text-primary-foreground': link.active }"><span v-html="link.label"></span></Link>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

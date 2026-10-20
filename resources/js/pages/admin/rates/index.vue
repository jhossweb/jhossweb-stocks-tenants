<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    create as ratesCreate,
    destroy as ratesDestroy,
    edit as ratesEdit,
    index as ratesIndex,
} from '@/routes/admin/rates';
import type { RatesIndexProps } from '@/types/Tenancy';

const props = defineProps<RatesIndexProps>();

const search = ref(props.filters.search ?? '');

function applyFilters() {
    router.get(
        ratesIndex.url(),
        { search: search.value || undefined },
        { preserveState: true, replace: true },
    );
}

function remove(id: number) {
    if (confirm('¿Eliminar esta tasa?')) {
        router.delete(ratesDestroy.url({ rate: id }));
    }
}

function visit(url: string | null) {
    if (url) {
        router.get(url, {}, { preserveState: true });
    }
}
</script>

<template>
    <div>
        <Head title="Tasas de cambio" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Tasas de cambio
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Tasas de referencia para el cobro de suscripciones
                    </p>
                </div>
                <Button as-child>
                    <Link :href="ratesCreate()">Nueva Tasa</Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Filtros</CardTitle>
                </CardHeader>
                <CardContent>
                    <form
                        class="flex flex-wrap items-end gap-4"
                        @submit.prevent="applyFilters"
                    >
                        <div class="space-y-2">
                            <Label for="search">Fecha</Label>
                            <Input id="search" v-model="search" type="date" />
                        </div>
                        <Button type="submit">Filtrar</Button>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b bg-muted/50">
                                <tr>
                                    <th class="p-4 text-left font-medium">Fecha</th>
                                    <th class="p-4 text-left font-medium">USD</th>
                                    <th class="p-4 text-left font-medium">COP</th>
                                    <th class="p-4 text-left font-medium">EUR</th>
                                    <th class="p-4 text-left font-medium">COP (Bs)</th>
                                    <th class="p-4 text-left font-medium">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="rate in rates.data"
                                    :key="rate.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="p-4 font-mono">
                                        {{ rate.rate_date }}
                                    </td>
                                    <td class="p-4">{{ rate.rate_usd }}</td>
                                    <td class="p-4">{{ rate.rate_cop }}</td>
                                    <td class="p-4">{{ rate.rate_eur }}</td>
                                    <td class="p-4">{{ rate.rate_cop_bs }}</td>
                                    <td class="p-4">
                                        <div class="flex gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                as-child
                                            >
                                                <Link
                                                    :href="
                                                        ratesEdit({
                                                            rate: rate.id,
                                                        })
                                                    "
                                                >
                                                    Editar
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                @click="remove(rate.id)"
                                            >
                                                Eliminar
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="rates.data.length === 0">
                                    <td
                                        colspan="6"
                                        class="p-8 text-center text-muted-foreground"
                                    >
                                        No hay tasas registradas.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <div class="flex items-center justify-between">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!rates.prev_page_url"
                    @click="visit(rates.prev_page_url)"
                >
                    Anterior
                </Button>
                <span class="text-sm text-muted-foreground">
                    Página {{ rates.current_page }} de {{ rates.last_page }}
                </span>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!rates.next_page_url"
                    @click="visit(rates.next_page_url)"
                >
                    Siguiente
                </Button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Building2 } from 'lucide-vue-next';

interface Supplier {
    id: number;
    supplier_name: string;
    supplier_address: string;
    supplier_phone: string;
    supplier_email: string;
    supplier_website: string | null;
    supplier_credit_days: number;
    created_at: string;
}

const { suppliers } = defineProps<{
    suppliers: {
        data: Supplier[];
        links: any[];
    };
}>();
</script>

<template>
    <Head title="Listado de Proveedores" />

    <div class="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
        <div
            class="flex items-center justify-between rounded-xl border bg-card px-6 py-4 shadow-sm"
        >
            <div class="flex items-center gap-4">
                <Link
                    href="/dashboard"
                    class="rounded-full p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                >
                    <ArrowLeft class="size-5" />
                </Link>
                <div>
                    <h1 class="text-xl font-bold">Proveedores</h1>
                    <p class="text-xs text-muted-foreground">
                        Gestiona los proveedores registrados en el sistema.
                    </p>
                </div>
            </div>
            <Link
                href="/suppliers/create"
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-all hover:bg-primary/90"
            >
                <Building2 class="size-4" /> Nuevo Proveedor
            </Link>
        </div>

        <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b bg-muted/50 text-[10px] font-bold text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-4 py-4 text-left">Nombre</th>
                            <th class="px-4 py-4 text-left">Días Créd.</th>
                            <th class="px-4 py-4 text-left">Teléfono</th>
                            <th class="px-4 py-4 text-left">Email</th>
                            <th class="px-4 py-4 text-left">Dirección</th>
                            <th class="w-12 px-4 py-4" />
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="suppliers.data.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-12 text-center text-muted-foreground"
                            >
                                <Building2
                                    class="mx-auto mb-4 size-12 text-muted-foreground/30"
                                />
                                <p>No hay proveedores registrados</p>
                            </td>
                        </tr>
                        <tr
                            v-for="supplier in suppliers.data"
                            :key="supplier.id"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-4 font-medium whitespace-nowrap">
                                {{ supplier.supplier_name }}
                            </td>
                            <td
                                class="px-4 py-4 whitespace-nowrap text-muted-foreground"
                            >
                                <span
                                    :class="
                                        supplier.supplier_credit_days > 0
                                            ? 'text-amber-400'
                                            : 'text-zinc-500'
                                    "
                                    >{{
                                        supplier.supplier_credit_days
                                    }}
                                    días</span
                                >
                            </td>
                            <td class="px-4 py-4 text-muted-foreground">
                                {{ supplier.supplier_phone }}
                            </td>
                            <td class="px-4 py-4 text-muted-foreground">
                                {{ supplier.supplier_email }}
                            </td>
                            <td
                                class="max-w-[200px] truncate px-4 py-4 text-muted-foreground"
                            >
                                {{ supplier.supplier_address }}
                            </td>
                            <td class="px-4 py-4 text-center">
                                <Link
                                    :href="`/suppliers/${supplier.id}/edit`"
                                    class="text-xs text-primary hover:text-primary/80"
                                    >Editar</Link
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="suppliers.links.length > 3"
                class="flex justify-center border-t px-6 py-4"
            >
                <div class="flex gap-1">
                    <template v-for="(link, key) in suppliers.links" :key="key">
                        <div
                            v-if="link.url === null"
                            class="cursor-not-allowed rounded border px-4 py-2 text-sm text-muted-foreground"
                            v-html="link.label"
                        ></div>
                        <Link
                            v-else
                            :href="link.url"
                            class="rounded border px-4 py-2 text-sm text-muted-foreground transition-colors hover:bg-accent"
                            :class="{
                                'border-primary bg-primary font-bold text-primary-foreground':
                                    link.active,
                            }"
                            ><span v-html="link.label"></span
                        ></Link>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

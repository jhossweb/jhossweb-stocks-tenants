<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { create, destroy, edit, update } from '@/routes/admin/tenants';
import type {
    TenantsIndexProps,
    TenantRow,
    TenantStatus,
} from '@/types/Tenancy';

defineProps<TenantsIndexProps>();

const statusLabel: Record<TenantStatus, string> = {
    active: 'Activo',
    suspended: 'Suspendido',
};

const statusClass: Record<TenantStatus, string> = {
    active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    suspended:
        'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
};

const toggleStatus = (tenant: TenantRow) => {
    router.put(update(tenant.id).url, {
        name: tenant.name ?? '',
        plan: tenant.plan ?? 'standard',
        status: tenant.status === 'active' ? 'suspended' : 'active',
    });
};

const removeTenant = (tenant: TenantRow) => {
    if (
        window.confirm(
            `¿Eliminar el tenant "${tenant.name}"? Esta acción eliminará su base de datos y no puede deshacerse.`,
        )
    ) {
        router.delete(destroy(tenant.id).url);
    }
};
</script>

<template>
    <Head title="Tenants · Admin" />

    <div class="flex h-full flex-1 flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Tenants</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Clientes del SaaS: crea, suspende, reactiva o elimina los
                    espacios de trabajo.
                </p>
            </div>
            <Button as-child>
                <Link :href="create().url">
                    <Plus class="size-4" />
                    Nuevo tenant
                </Link>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Listado</CardTitle>
                <CardDescription>
                    Los cambios de estado aplican de inmediato.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b text-left text-muted-foreground"
                            >
                                <th class="pr-4 pb-3 font-medium">Empresa</th>
                                <th class="pr-4 pb-3 font-medium">Dominio</th>
                                <th class="pr-4 pb-3 font-medium">Plan</th>
                                <th class="pr-4 pb-3 font-medium">Estado</th>
                                <th class="pr-4 pb-3 font-medium">Creado</th>
                                <th class="pb-3 text-right font-medium">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="tenant in tenants"
                                :key="tenant.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4">
                                    <div class="font-medium">
                                        {{ tenant.name || tenant.id }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ tenant.id }}
                                    </div>
                                </td>
                                <td class="py-3 pr-4 text-muted-foreground">
                                    {{ tenant.domain ?? '—' }}
                                </td>
                                <td class="py-3 pr-4">
                                    <Badge variant="outline" class="capitalize">
                                        {{ tenant.plan ?? '—' }}
                                    </Badge>
                                </td>
                                <td class="py-3 pr-4">
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="statusClass[tenant.status]"
                                    >
                                        {{ statusLabel[tenant.status] }}
                                    </span>
                                </td>
                                <td class="py-3 pr-4 text-muted-foreground">
                                    {{ tenant.created_at?.slice(0, 10) ?? '—' }}
                                </td>
                                <td class="py-3 text-right">
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            as-child
                                        >
                                            <Link
                                                :href="edit(tenant.id).url"
                                                aria-label="Editar"
                                            >
                                                <Pencil class="size-4" />
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="secondary"
                                            size="sm"
                                            @click="toggleStatus(tenant)"
                                        >
                                            {{
                                                tenant.status === 'active'
                                                    ? 'Suspender'
                                                    : 'Activar'
                                            }}
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="text-destructive hover:text-destructive"
                                            @click="removeTenant(tenant)"
                                            aria-label="Eliminar"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

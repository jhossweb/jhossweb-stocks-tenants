<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    create as adminTenantsCreate,
    show as adminTenantsShow,
    edit as adminTenantsEdit,
} from '@/routes/admin/tenants';

defineProps<{
    tenants: any;
    filters: any;
}>();

function statusVariant(status: string) {
    if (status === 'active') {
        return 'default';
    }

    if (status === 'suspended') {
        return 'destructive';
    }

    return 'secondary';
}
</script>

<template>
    <div>
        <Head title="Tenants" />

        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Tenants
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Gestiona los tenants del sistema
                    </p>
                </div>
                <Button as-child>
                    <Link :href="adminTenantsCreate()">Nuevo Tenant</Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Filtros</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="search">Buscar</Label>
                            <Input
                                id="search"
                                placeholder="Empresa, RIF, email..."
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b bg-muted/50">
                                <tr>
                                    <th class="p-4 text-left font-medium">
                                        ID
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Empresa
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        RIF
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Dirección
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Teléfono
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Admin
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Email
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Subdominio
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Estado
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Plan
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="tenant in tenants.data"
                                    :key="tenant.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="p-4 font-mono">
                                        {{ tenant.id }}
                                    </td>
                                    <td class="p-4">{{ tenant.name }}</td>
                                    <td class="p-4">{{ tenant.rif }}</td>
                                    <td class="max-w-[200px] truncate p-4">
                                        {{ tenant.address }}
                                    </td>
                                    <td class="p-4">{{ tenant.phone }}</td>
                                    <td class="p-4">{{ tenant.admin_name }}</td>
                                    <td class="p-4">
                                        {{ tenant.admin_email }}
                                    </td>
                                    <td class="p-4 font-mono text-xs">
                                        {{ tenant.domain || '—' }}
                                    </td>
                                    <td class="p-4">
                                        <Badge
                                            :variant="
                                                statusVariant(tenant.status)
                                            "
                                            >{{ tenant.status }}</Badge
                                        >
                                    </td>
                                    <td class="p-4">
                                        {{ tenant.plan || 'Sin plan' }}
                                    </td>
                                    <td class="flex gap-2 p-4">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            as-child
                                        >
                                            <Link
                                                :href="
                                                    adminTenantsShow({
                                                        tenant: tenant.id,
                                                    })
                                                "
                                                >Ver</Link
                                            >
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            as-child
                                        >
                                            <Link
                                                :href="
                                                    adminTenantsEdit({
                                                        tenant: tenant.id,
                                                    })
                                                "
                                                >Editar</Link
                                            >
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    create as adminPlansCreate,
    edit as adminPlansEdit,
} from '@/routes/admin/plans';

defineProps<{
    plans: any;
    filters: any;
}>();
</script>

<template>
    <div>
        <Head title="Planes" />

        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Planes
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Gestiona los planes de suscripción
                    </p>
                </div>
                <Button as-child>
                    <Link :href="adminPlansCreate.url()">Nuevo Plan</Link>
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
                                placeholder="Código o nombre..."
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
                                        Código
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Nombre
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Ciclo
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Precio USD
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Trial (días)
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Activo
                                    </th>
                                    <th class="p-4 text-left font-medium">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="plan in plans.data"
                                    :key="plan.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="p-4 font-mono">
                                        {{ plan.code }}
                                    </td>
                                    <td class="p-4">{{ plan.name }}</td>
                                    <td class="p-4 capitalize">
                                        {{ plan.billing_cycle }}
                                    </td>
                                    <td class="p-4">${{ plan.price_usd }}</td>
                                    <td class="p-4">{{ plan.trial_days }}</td>
                                    <td class="p-4">
                                        <Badge
                                            :variant="
                                                plan.is_active
                                                    ? 'default'
                                                    : 'secondary'
                                            "
                                        >
                                            {{ plan.is_active ? 'Sí' : 'No' }}
                                        </Badge>
                                    </td>
                                    <td class="flex gap-2 p-4">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            as-child
                                        >
                                            <Link
                                                :href="
                                                    adminPlansEdit.url({
                                                        plan: plan.id,
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

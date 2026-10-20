<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    edit as adminPlansEdit,
    index as adminPlansIndex,
} from '@/routes/admin/plans';

defineProps<{
    plan: any;
    module_keys?: string[];
}>();
</script>

<template>
    <div>
        <Head title="Detalle Plan" />

        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ plan.name }}
                    </h1>
                    <p class="text-sm text-muted-foreground">{{ plan.code }}</p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="adminPlansEdit.url({ plan: plan.id })"
                            >Editar</Link
                        >
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="adminPlansIndex.url()">Volver</Link>
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Información general</CardTitle>
                </CardHeader>
                <CardContent
                    class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2"
                >
                    <div>
                        <span class="text-muted-foreground">Ciclo:</span>
                        {{ plan.billing_cycle }}
                    </div>
                    <div>
                        <span class="text-muted-foreground">Precio USD:</span>
                        ${{ plan.price_usd }}
                    </div>
                    <div>
                        <span class="text-muted-foreground">Trial:</span>
                        {{ plan.trial_days }} días
                    </div>
                    <div>
                        <span class="text-muted-foreground">Activo:</span>
                        <Badge
                            :variant="plan.is_active ? 'default' : 'secondary'"
                            class="ml-2"
                        >
                            {{ plan.is_active ? 'Sí' : 'No' }}
                        </Badge>
                    </div>
                    <div class="md:col-span-2">
                        <span class="text-muted-foreground">Descripción:</span>
                        {{ plan.description }}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Módulos</CardTitle>
                </CardHeader>
                <CardContent class="flex flex-wrap gap-2">
                    <Badge
                        v-for="m in module_keys"
                        :key="m"
                        variant="outline"
                        >{{ m }}</Badge
                    >
                </CardContent>
            </Card>
        </div>
    </div>
</template>

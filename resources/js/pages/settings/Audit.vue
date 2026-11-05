<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Paginated } from '@/types/Settings';

type AuditLog = {
    id: number;
    event: string;
    subject_type: string | null;
    subject_id: number | null;
    ip: string | null;
    created_at: string | null;
    user: { id: number; name: string; email: string } | null;
};

type AuditUser = { id: number; name: string; email: string };

const props = defineProps<{
    logs: Paginated<AuditLog>;
    filters: {
        event?: string;
        user_id?: number | string;
        date_from?: string;
        date_to?: string;
    };
    events: string[];
    users: AuditUser[];
}>();

const event = ref(props.filters.event ?? '');
const userId = ref(props.filters.user_id ?? '');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');

function filter() {
    router.get(
        '/settings/audit',
        {
            event: event.value || undefined,
            user_id: userId.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <div>
        <Head title="Auditoría" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Auditoría
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Registro de seguridad y cambios relevantes.
                    </p>
                </div>
                <Button variant="outline" as-child>
                    <a href="/settings/audit/export">Exportar CSV</a>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Filtros</CardTitle>
                </CardHeader>
                <CardContent>
                    <form
                        class="grid grid-cols-1 gap-4 md:grid-cols-5"
                        @submit.prevent="filter"
                    >
                        <div class="space-y-2">
                            <Label for="event">Evento</Label>
                            <select
                                id="event"
                                v-model="event"
                                class="h-10 w-full rounded-md border bg-background px-3"
                            >
                                <option value="">Todos</option>
                                <option
                                    v-for="item in props.events"
                                    :key="item"
                                    :value="item"
                                >
                                    {{ item }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="user_id">Usuario</Label>
                            <select
                                id="user_id"
                                v-model="userId"
                                class="h-10 w-full rounded-md border bg-background px-3"
                            >
                                <option value="">Todos</option>
                                <option
                                    v-for="user in props.users"
                                    :key="user.id"
                                    :value="user.id"
                                >
                                    {{ user.name }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="date_from">Desde</Label>
                            <Input
                                id="date_from"
                                v-model="dateFrom"
                                type="date"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="date_to">Hasta</Label>
                            <Input id="date_to" v-model="dateTo" type="date" />
                        </div>
                        <div class="flex items-end">
                            <Button type="submit">Filtrar</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Registros</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b text-left">
                                <tr>
                                    <th class="py-2">Fecha</th>
                                    <th class="py-2">Usuario</th>
                                    <th class="py-2">Evento</th>
                                    <th class="py-2">Entidad</th>
                                    <th class="py-2">IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="log in logs.data"
                                    :key="log.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-2">
                                        {{ log.created_at }}
                                    </td>
                                    <td class="py-2">
                                        {{ log.user?.email ?? '-' }}
                                    </td>
                                    <td class="py-2">{{ log.event }}</td>
                                    <td class="py-2">
                                        {{ log.subject_type ?? '-' }}
                                        {{ log.subject_id ?? '' }}
                                    </td>
                                    <td class="py-2">{{ log.ip ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

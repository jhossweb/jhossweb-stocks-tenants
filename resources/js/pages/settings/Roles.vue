<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Paginated, PermissionGroup, TenantRole } from '@/types/Settings';

const props = defineProps<{
    roles: Paginated<TenantRole>;
    permission_groups: PermissionGroup[];
}>();

const editing = ref<TenantRole | null>(null);

const form = useForm({
    name: '',
    permissions: [] as string[],
});

function resetForm() {
    editing.value = null;
    form.reset();
    form.clearErrors();
}

function startEdit(role: TenantRole) {
    editing.value = role;
    form.name = role.name;
    form.permissions = role.permissions.map((permission) => permission.name);
    form.clearErrors();
}

function submit() {
    if (editing.value) {
        form.put(`/settings/roles/${editing.value.id}`, {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });

        return;
    }

    form.post('/settings/roles', {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    });
}

function destroy(role: TenantRole) {
    if (!confirm(`¿Eliminar el rol ${role.name}?`)) {
        return;
    }

    router.delete(`/settings/roles/${role.id}`, { preserveScroll: true });
}
</script>

<template>
    <div>
        <Head title="Roles" />

        <div class="space-y-6">
            <h1 class="text-2xl font-semibold tracking-tight">Roles</h1>

            <Card>
                <CardHeader>
                    <CardTitle>
                        {{ editing ? 'Editar rol' : 'Nuevo rol' }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="space-y-2">
                            <Label for="name">Nombre</Label>
                            <Input id="name" v-model="form.name" required />
                            <p
                                v-if="form.errors.name"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-4">
                            <div
                                v-for="group in props.permission_groups"
                                :key="group.key"
                                class="space-y-2"
                            >
                                <p class="text-sm font-medium">
                                    {{ group.label }}
                                </p>
                                <div
                                    class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3"
                                >
                                    <label
                                        v-for="permission in group.permissions"
                                        :key="permission.key"
                                        class="flex items-center gap-2 text-sm"
                                    >
                                        <input
                                            v-model="form.permissions"
                                            type="checkbox"
                                            :value="permission.key"
                                            class="h-4 w-4"
                                        />
                                        {{ permission.label }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <Button type="submit" :disabled="form.processing">
                                {{ editing ? 'Actualizar' : 'Crear' }}
                            </Button>
                            <Button
                                v-if="editing"
                                type="button"
                                variant="outline"
                                @click="resetForm"
                            >
                                Cancelar
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Listado</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b text-left">
                                <tr>
                                    <th class="py-2">Rol</th>
                                    <th class="py-2">Usuarios</th>
                                    <th class="py-2">Permisos</th>
                                    <th class="py-2 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="role in roles.data"
                                    :key="role.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-2 font-medium">
                                        {{ role.name }}
                                    </td>
                                    <td class="py-2">{{ role.users_count }}</td>
                                    <td class="py-2">
                                        {{ role.permissions.length }}
                                    </td>
                                    <td class="py-2 text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button
                                                v-if="role.name !== 'admin'"
                                                variant="outline"
                                                size="sm"
                                                @click="startEdit(role)"
                                            >
                                                Editar
                                            </Button>
                                            <Button
                                                v-if="role.name !== 'admin'"
                                                variant="destructive"
                                                size="sm"
                                                @click="destroy(role)"
                                            >
                                                Eliminar
                                            </Button>
                                            <span
                                                v-if="role.name === 'admin'"
                                                class="text-xs text-muted-foreground"
                                            >
                                                Protegido
                                            </span>
                                        </div>
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

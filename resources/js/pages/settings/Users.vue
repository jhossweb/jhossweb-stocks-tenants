<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Paginated, TenantUser, TenantUserRole } from '@/types/Settings';

const props = defineProps<{
    users: Paginated<TenantUser>;
    roles: TenantUserRole[];
    used: number;
    limit: number | null;
}>();

const editing = ref<TenantUser | null>(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [] as string[],
});

const limitReached = computed(
    () => props.limit !== null && props.used >= props.limit,
);

function resetForm() {
    editing.value = null;
    form.reset();
    form.clearErrors();
}

function startEdit(user: TenantUser) {
    editing.value = user;
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.password_confirmation = '';
    form.roles = user.roles.map((role) => role.name);
    form.clearErrors();
}

function submit() {
    if (editing.value) {
        form.put(`/settings/users/${editing.value.id}`, {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });

        return;
    }

    form.post('/settings/users', {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    });
}

function toggleUser(user: TenantUser) {
    router.patch(
        `/settings/users/${user.id}/${user.is_active ? 'deactivate' : 'activate'}`,
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <div>
        <Head title="Usuarios" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Usuarios
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Usados {{ used }} / {{ limit ?? 'Sin límite' }}
                    </p>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>
                        {{ editing ? 'Editar usuario' : 'Nuevo usuario' }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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
                            <div class="space-y-2">
                                <Label for="email">Email</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                />
                                <p
                                    v-if="form.errors.email"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.email }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="password">Contraseña</Label>
                                <Input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    :required="!editing"
                                />
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        editing
                                            ? 'Déjala vacía para conservar la actual.'
                                            : 'Mínimo 8 caracteres.'
                                    }}
                                </p>
                                <p
                                    v-if="form.errors.password"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.password }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="password_confirmation"
                                    >Confirmar contraseña</Label
                                >
                                <Input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label>Roles</Label>
                            <div class="flex flex-wrap gap-4">
                                <label
                                    v-for="role in props.roles"
                                    :key="role.id"
                                    class="flex items-center gap-2 text-sm"
                                >
                                    <input
                                        v-model="form.roles"
                                        type="checkbox"
                                        :value="role.name"
                                        class="h-4 w-4"
                                    />
                                    {{ role.name }}
                                </label>
                            </div>
                            <p
                                v-if="form.errors.roles"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.roles }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button
                                type="submit"
                                :disabled="
                                    form.processing ||
                                    (!editing && limitReached)
                                "
                            >
                                {{ editing ? 'Actualizar' : 'Crear' }}
                            </Button>
                            <p
                                v-if="!editing && limitReached"
                                class="text-sm text-destructive"
                            >
                                Límite de usuarios alcanzado.
                            </p>
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
                                    <th class="py-2">Nombre</th>
                                    <th class="py-2">Email</th>
                                    <th class="py-2">Roles</th>
                                    <th class="py-2">Estado</th>
                                    <th class="py-2 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="user in users.data"
                                    :key="user.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-2">{{ user.name }}</td>
                                    <td class="py-2">{{ user.email }}</td>
                                    <td class="py-2">
                                        <div class="flex flex-wrap gap-1">
                                            <Badge
                                                v-for="role in user.roles"
                                                :key="role.id"
                                                variant="outline"
                                            >
                                                {{ role.name }}
                                            </Badge>
                                        </div>
                                    </td>
                                    <td class="py-2">
                                        <Badge
                                            :variant="
                                                user.is_active
                                                    ? 'default'
                                                    : 'secondary'
                                            "
                                        >
                                            {{
                                                user.is_active
                                                    ? 'Activo'
                                                    : 'Inactivo'
                                            }}
                                        </Badge>
                                    </td>
                                    <td class="py-2 text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="startEdit(user)"
                                            >
                                                Editar
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="toggleUser(user)"
                                            >
                                                {{
                                                    user.is_active
                                                        ? 'Inactivar'
                                                        : 'Activar'
                                                }}
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
    </div>
</template>

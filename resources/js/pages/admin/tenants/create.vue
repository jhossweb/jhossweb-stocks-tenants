<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index, store } from '@/routes/admin/tenants';
import type {
    TenantCreateProps,
    TenantPlan,
    TenantStatus,
} from '@/types/Tenancy';

const props = defineProps<TenantCreateProps>();

const form = useForm({
    company_name: '',
    slug: '',
    admin_name: '',
    admin_email: '',
    admin_password: '',
    admin_password_confirmation: '',
    plan: 'standard' as TenantPlan,
    status: 'active' as TenantStatus,
});

const domainPreview = computed(() => {
    const slug = form.slug.trim() || toSlug(form.company_name);

    return slug ? `${slug}.${props.domainSuffix}` : null;
});

const submit = () => {
    form.post(store().url, { preserveScroll: true });
};

function toSlug(value: string): string {
    return value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)+/g, '');
}
</script>

<template>
    <Head title="Nuevo tenant · Admin" />

    <div class="flex h-full flex-1 flex-col gap-6">
        <div class="flex items-center gap-4">
            <Link :href="index().url" class="text-muted-foreground">
                <ArrowLeft class="size-5" />
            </Link>
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Nuevo tenant</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Crea el espacio de un cliente; el sistema aprovisiona el
                    schema, la empresa y el usuario admin.
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="max-w-3xl">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="company_name">Nombre de la empresa</Label>
                    <Input
                        id="company_name"
                        v-model="form.company_name"
                        required
                        autofocus
                        placeholder="Vidrialum C.A."
                    />
                    <InputError :message="form.errors.company_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="slug">Slug/ID del tenant (opcional)</Label>
                    <Input
                        id="slug"
                        v-model="form.slug"
                        placeholder="Se genera del nombre (ej. vidrialum-ca)"
                    />
                    <p
                        v-if="domainPreview"
                        class="text-xs text-muted-foreground"
                    >
                        Dominio:
                        <span class="font-medium">{{ domainPreview }}</span>
                    </p>
                    <InputError :message="form.errors.slug" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label>Plan</Label>
                        <Select v-model="form.plan">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="standard">
                                    Standard
                                </SelectItem>
                                <SelectItem value="premium">Premium</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="grid gap-2">
                        <Label>Estado</Label>
                        <Select v-model="form.status">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="active">Activo</SelectItem>
                                <SelectItem value="suspended">
                                    Suspendido
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div
                    class="rounded-md border bg-muted/50 p-4 text-sm text-muted-foreground"
                >
                    Usuario administrador del tenant (se crea verificado).
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="admin_name">Nombre</Label>
                        <Input
                            id="admin_name"
                            v-model="form.admin_name"
                            required
                            placeholder="Nombre completo"
                        />
                        <InputError :message="form.errors.admin_name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="admin_email">Email</Label>
                        <Input
                            id="admin_email"
                            v-model="form.admin_email"
                            type="email"
                            required
                            placeholder="email@example.com"
                        />
                        <InputError :message="form.errors.admin_email" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="admin_password">Contraseña</Label>
                        <Input
                            id="admin_password"
                            v-model="form.admin_password"
                            type="password"
                            required
                            autocomplete="new-password"
                        />
                        <InputError :message="form.errors.admin_password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="admin_password_confirmation"
                            >Confirmar contraseña</Label
                        >
                        <Input
                            id="admin_password_confirmation"
                            v-model="form.admin_password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                        />
                        <InputError
                            :message="form.errors.admin_password_confirmation"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="form.processing">
                        <Save class="size-4" />
                        {{ form.processing ? 'Creando...' : 'Crear tenant' }}
                    </Button>
                    <Button type="button" variant="ghost" as-child>
                        <Link :href="index().url">Cancelar</Link>
                    </Button>
                </div>
            </div>
        </form>
    </div>
</template>
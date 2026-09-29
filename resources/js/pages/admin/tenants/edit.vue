<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
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
import { index, update } from '@/routes/admin/tenants';
import type { TenantEditProps, TenantPlan, TenantStatus } from '@/types/Tenancy';

const props = defineProps<TenantEditProps>();

const form = useForm({
    name: props.tenant.name ?? '',
    plan: (props.tenant.plan ?? 'standard') as TenantPlan,
    status: props.tenant.status as TenantStatus,
});

const submit = () => {
    form.put(update(props.tenant.id).url, { preserveScroll: true });
};
</script>

<template>
    <Head title="Editar tenant · Admin" />

    <div class="flex h-full flex-1 flex-col gap-6">
        <div class="flex items-center gap-4">
            <Link :href="index().url" class="text-muted-foreground">
                <ArrowLeft class="size-5" />
            </Link>
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Editar {{ tenant.name || tenant.id }}
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Dominio:
                    <span class="font-medium">{{ tenant.domain ?? '—' }}</span>
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="max-w-3xl">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Nombre de la empresa</Label>
                    <Input id="name" v-model="form.name" required />
                    <InputError :message="form.errors.name" />
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
                        <InputError :message="form.errors.plan" />
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
                        <InputError :message="form.errors.status" />
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="form.processing">
                        <Save class="size-4" />
                        {{ form.processing ? 'Guardando...' : 'Guardar' }}
                    </Button>
                    <Button type="button" variant="ghost" as-child>
                        <Link :href="index().url">Cancelar</Link>
                    </Button>
                </div>
            </div>
        </form>
    </div>
</template>
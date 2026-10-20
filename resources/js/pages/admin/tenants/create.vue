<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AlertError from '@/components/AlertError.vue';
import FormActions from '@/components/Form/FormActions.vue';
import InputError from '@/components/InputError.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    store as adminTenantsStore,
    index as adminTenantsIndex,
} from '@/routes/admin/tenants';

const form = useForm({
    name: '',
    rif: '',
    address: '',
    phone: '',
    admin_name: '',
    admin_email: '',
    admin_password: '',
    admin_password_confirmation: '',
    internal_notes: '',
});

const errorsList = () => Object.values(form.errors).filter(Boolean);

function submit() {
    form.post(adminTenantsStore.url());
}
</script>

<template>
    <div>
        <Head title="Crear Tenant" />

        <div class="space-y-6 p-6">
            <FormActions
                title="Crear Tenant"
                subtitle="Registra un nuevo tenant con todos sus datos"
                :back-url="adminTenantsIndex.url()"
                :processing="form.processing"
                @save="submit"
            />

            <AlertError
                v-if="Object.keys(form.errors).length"
                :errors="errorsList()"
                title="Errores de validación"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Datos de la empresa</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-2 md:col-span-2">
                            <Label for="name">Nombre de la empresa</Label>
                            <Input id="name" v-model="form.name" required />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="space-y-2">
                            <Label for="rif">RIF</Label>
                            <Input id="rif" v-model="form.rif" required />
                            <InputError :message="form.errors.rif" />
                        </div>
                        <div class="space-y-2">
                            <Label for="phone">Teléfono</Label>
                            <Input id="phone" v-model="form.phone" required />
                            <InputError :message="form.errors.phone" />
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <Label for="address">Dirección</Label>
                            <Input
                                id="address"
                                v-model="form.address"
                                required
                            />
                            <InputError :message="form.errors.address" />
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <Label for="internal_notes">Notas internas</Label>
                            <Input
                                id="internal_notes"
                                v-model="form.internal_notes"
                            />
                            <InputError :message="form.errors.internal_notes" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Contacto administrador</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="admin_name"
                                >Nombre del administrador</Label
                            >
                            <Input
                                id="admin_name"
                                v-model="form.admin_name"
                                required
                            />
                            <InputError :message="form.errors.admin_name" />
                        </div>
                        <div class="space-y-2">
                            <Label for="admin_email">Correo electrónico</Label>
                            <Input
                                id="admin_email"
                                v-model="form.admin_email"
                                type="email"
                                required
                            />
                            <InputError :message="form.errors.admin_email" />
                        </div>
                        <div class="space-y-2">
                            <Label for="admin_password">Contraseña</Label>
                            <Input
                                id="admin_password"
                                v-model="form.admin_password"
                                type="password"
                                required
                            />
                            <InputError :message="form.errors.admin_password" />
                        </div>
                        <div class="space-y-2">
                            <Label for="admin_password_confirmation">Confirmar contraseña</Label>
                            <Input
                                id="admin_password_confirmation"
                                v-model="form.admin_password_confirmation"
                                type="password"
                                required
                            />
                            <InputError :message="form.errors.admin_password_confirmation" />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

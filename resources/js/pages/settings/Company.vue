<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Building2, Loader2, Save } from 'lucide-vue-next';
import { computed } from 'vue';
import CompanyController from '@/actions/App/Http/Controllers/Settings/CompanyController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Company } from '@/types/Settings';

interface Props {
    company: Company | null;
}

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Datos de la Empresa',
                href: '/settings/company',
            },
        ],
    },
});

const form = useForm({
    cmp_name: props.company?.cmp_name ?? '',
    cmp_rif: props.company?.cmp_rif ?? '',
    cmp_address: props.company?.cmp_address ?? '',
    cmp_phone: props.company?.cmp_phone ?? '',
    cmp_email: props.company?.cmp_email ?? '',
    cmp_website: props.company?.cmp_website ?? '',
    cmp_logo: props.company?.cmp_logo ?? '',
});

const updateAction = computed(() => CompanyController.update());

const submit = () => {
    form.submit(updateAction.value.method, updateAction.value.url);
};
</script>

<template>
    <Head title="Datos de la Empresa" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Datos de la Empresa"
            description="Información fiscal y comercial que se utilizará en facturas y reportes del sistema."
        />

        <Card class="bg-zinc-950 border-zinc-800 shadow-xl">
            <CardHeader class="border-b border-zinc-800">
                <CardTitle class="flex items-center gap-2 text-lg font-bold text-zinc-100">
                    <Building2 class="w-5 h-5 text-indigo-500" />
                    Información de la Empresa
                </CardTitle>
                <CardDescription class="text-zinc-500">
                    Los campos marcados con * son obligatorios.
                </CardDescription>
            </CardHeader>

            <CardContent class="pt-6">
                <form @submit.prevent="submit" class="space-y-5">
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="cmp_name" class="text-zinc-400">
                                Nombre Comercial *
                            </Label>
                            <Input
                                id="cmp_name"
                                v-model="form.cmp_name"
                                type="text"
                                placeholder="Vidrialum C.A."
                                class="bg-zinc-900 border-zinc-800 focus:ring-indigo-600 transition-all"
                                required
                            />
                            <InputError :message="form.errors.cmp_name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="cmp_rif" class="text-zinc-400">
                                RIF *
                            </Label>
                            <Input
                                id="cmp_rif"
                                v-model="form.cmp_rif"
                                type="text"
                                placeholder="J-12345678-9"
                                class="bg-zinc-900 border-zinc-800 focus:ring-indigo-600 transition-all"
                                required
                            />
                            <InputError :message="form.errors.cmp_rif" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="cmp_address" class="text-zinc-400">
                            Dirección Fiscal *
                        </Label>
                        <textarea
                            id="cmp_address"
                            v-model="form.cmp_address"
                            rows="3"
                            placeholder="Av. Principal, Sector Industrial, Caracas"
                            class="flex w-full rounded-md border border-zinc-800 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 placeholder:text-zinc-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 transition-all resize-none"
                            required
                        ></textarea>
                        <InputError :message="form.errors.cmp_address" />
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="cmp_phone" class="text-zinc-400">
                                Teléfono *
                            </Label>
                            <Input
                                id="cmp_phone"
                                v-model="form.cmp_phone"
                                type="tel"
                                placeholder="+58 212 555 1234"
                                class="bg-zinc-900 border-zinc-800 focus:ring-indigo-600 transition-all"
                                required
                            />
                            <InputError :message="form.errors.cmp_phone" />
                        </div>

                        <div class="space-y-2">
                            <Label for="cmp_email" class="text-zinc-400">
                                Email *
                            </Label>
                            <Input
                                id="cmp_email"
                                v-model="form.cmp_email"
                                type="email"
                                placeholder="contacto@vidrialum.com"
                                class="bg-zinc-900 border-zinc-800 focus:ring-indigo-600 transition-all"
                                required
                            />
                            <InputError :message="form.errors.cmp_email" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="cmp_website" class="text-zinc-400">
                                Sitio Web
                            </Label>
                            <Input
                                id="cmp_website"
                                v-model="form.cmp_website"
                                type="url"
                                placeholder="https://vidrialum.com"
                                class="bg-zinc-900 border-zinc-800 focus:ring-indigo-600 transition-all"
                            />
                            <InputError :message="form.errors.cmp_website" />
                        </div>

                        <div class="space-y-2">
                            <Label for="cmp_logo" class="text-zinc-400">
                                Logo (ruta)
                            </Label>
                            <Input
                                id="cmp_logo"
                                v-model="form.cmp_logo"
                                type="text"
                                placeholder="/images/logo.png"
                                class="bg-zinc-900 border-zinc-800 focus:ring-indigo-600 transition-all"
                            />
                            <InputError :message="form.errors.cmp_logo" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-4 border-t border-zinc-800">
                        <span
                            v-if="form.recentlySuccessful"
                            class="text-sm text-emerald-400"
                        >
                            Guardado exitosamente.
                        </span>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-900/20"
                        >
                            <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                            <Save v-else class="w-4 h-4 mr-2" />
                            Guardar Cambios
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>

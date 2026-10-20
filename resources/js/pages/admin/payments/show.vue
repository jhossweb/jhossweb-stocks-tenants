<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import payments from '@/routes/admin/payments';
import type { PaymentShowProps } from '@/types/Tenancy';

const props = defineProps<PaymentShowProps>();

const showVoid = ref(false);

const voidForm = useForm({ reason: '' });

function formatUsd(value: string | number) {
    return new Intl.NumberFormat('es-VE', {
        style: 'currency',
        currency: 'USD',
    }).format(Number(value));
}

function confirmVoid() {
    voidForm.post(payments.void.url({ payment: props.payment.id }), {
        onSuccess: () => {
            showVoid.value = false;
            voidForm.reset();
        },
    });
}
</script>

<template>
    <div>
        <Head :title="`Cobro ${payment.receipt_number}`" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ payment.receipt_number }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ payment.tenant_name ?? '—' }} ·
                        {{ payment.plan_name ?? 'Sin plan' }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" as-child>
                        <a
                            :href="
                                payments.receipt.url({
                                    payment: payment.id,
                                })
                            "
                        >
                            Descargar recibo
                        </a>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="payments.index.url()">Volver</Link>
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Datos del cobro</CardTitle>
                </CardHeader>
                <CardContent
                    class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2"
                >
                    <div>
                        <span class="text-muted-foreground">Estado:</span>
                        <Badge
                            class="ml-2"
                            :variant="
                                payment.status === 'paid'
                                    ? 'default'
                                    : 'destructive'
                            "
                        >
                            {{ payment.status }}
                        </Badge>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Método:</span>
                        {{ payment.method }}
                    </div>
                    <div>
                        <span class="text-muted-foreground">Monto:</span>
                        {{ payment.amount }} {{ payment.currency }}
                    </div>
                    <div>
                        <span class="text-muted-foreground">Tasa aplicada:</span>
                        {{ payment.exchange_rate }}
                    </div>
                    <div>
                        <span class="text-muted-foreground">Equivalente USD:</span>
                        {{ formatUsd(payment.amount_usd) }}
                    </div>
                    <div>
                        <span class="text-muted-foreground">Fecha de pago:</span>
                        {{ payment.paid_at ?? '—' }}
                    </div>
                    <div>
                        <span class="text-muted-foreground">Referencia:</span>
                        {{ payment.reference ?? '—' }}
                    </div>
                    <div>
                        <span class="text-muted-foreground">Registrado por:</span>
                        {{ payment.recorded_by ?? '—' }}
                    </div>
                    <div class="md:col-span-2">
                        <span class="text-muted-foreground">Notas:</span>
                        {{ payment.notes ?? '—' }}
                    </div>
                </CardContent>
            </Card>

            <Card v-if="payment.is_voided">
                <CardHeader>
                    <CardTitle>Anulación</CardTitle>
                </CardHeader>
                <CardContent class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                    <div>
                        <span class="text-muted-foreground">Anulado el:</span>
                        {{ payment.voided_at ?? '—' }}
                    </div>
                    <div>
                        <span class="text-muted-foreground">Anulado por:</span>
                        {{ payment.voided_by ?? '—' }}
                    </div>
                </CardContent>
            </Card>

            <Card v-else>
                <CardHeader>
                    <CardTitle>Acciones</CardTitle>
                </CardHeader>
                <CardContent>
                    <Dialog v-model:open="showVoid">
                        <DialogTrigger as-child>
                            <Button variant="destructive">
                                Anular cobro
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Anular cobro</DialogTitle>
                                <DialogDescription>
                                    El cobro no se elimina; se marca como anulado
                                    para conservar la auditoría.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="space-y-2">
                                <Label for="reason">Motivo</Label>
                                <Input
                                    id="reason"
                                    v-model="voidForm.reason"
                                    placeholder="Motivo de anulación..."
                                />
                            </div>
                            <DialogFooter>
                                <Button
                                    variant="outline"
                                    @click="showVoid = false"
                                >
                                    Cancelar
                                </Button>
                                <Button
                                    variant="destructive"
                                    :disabled="voidForm.processing"
                                    @click="confirmVoid"
                                >
                                    Anular
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

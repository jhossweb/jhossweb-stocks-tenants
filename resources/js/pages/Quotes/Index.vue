<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    FileText,
    ClipboardList,
    Pencil,
    Download,
} from 'lucide-vue-next';
import { ref } from 'vue';
import ConvertQuoteModal from '@/components/Quotes/ConvertQuoteModal.vue';
import { formatDate } from '@/composables/useDate';
import type { Quote, QuoteStatusValue } from '@/types/Quotes';
import { QUOTE_STATUS_LABEL } from '@/types/Quotes';

const props = defineProps<{
    quotes: {
        data: Quote[];
        links: any[];
    };
}>();

const convertTarget = ref<Quote | null>(null);
const convertOpen = ref(false);

const openConvert = (quote: Quote) => {
    convertTarget.value = quote;
    convertOpen.value = true;
};

const statusClass: Record<QuoteStatusValue, string> = {
    pending: 'bg-blue-900/30 text-blue-400',
    approved: 'bg-emerald-900/30 text-emerald-400',
    rejected: 'bg-red-900/30 text-red-400',
    converted: 'bg-zinc-800 text-zinc-400',
};
</script>

<template>
    <Head title="Cotizaciones" />

    <div class="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
        <div
            class="flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-900 p-6 shadow-xl"
        >
            <div class="flex items-center gap-4">
                <Link
                    href="/dashboard"
                    class="rounded-full p-2 text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-white"
                >
                    <ArrowLeft class="size-6" />
                </Link>
                <div>
                    <h1 class="text-xl font-bold text-zinc-100">
                        Cotizaciones
                    </h1>
                    <p class="text-xs text-zinc-500">
                        Gestiona y registra cotizaciones sin afectar inventario.
                    </p>
                </div>
            </div>
            <Link
                href="/quotes/create"
                class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white shadow-lg shadow-indigo-900/20 transition-all hover:bg-indigo-500"
            >
                <FileText class="mr-2 size-4" /> Nueva Cotización
            </Link>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900 shadow-sm"
        >
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-zinc-800 bg-zinc-950 text-[10px] font-bold text-zinc-500 uppercase"
                    >
                        <tr>
                            <th class="px-4 py-4 text-left">Cotización</th>
                            <th class="px-4 py-4 text-left">Cliente</th>
                            <th class="px-4 py-4 text-left">Fecha</th>
                            <th class="px-4 py-4 text-right">Total</th>
                            <th class="px-4 py-4 text-center">Estado</th>
                            <th class="w-32 px-4 py-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800">
                        <tr v-if="props.quotes.data.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-12 text-center text-zinc-500"
                            >
                                <ClipboardList
                                    class="mx-auto mb-4 size-12 text-zinc-600"
                                />
                                <p>No hay cotizaciones registradas</p>
                            </td>
                        </tr>
                        <tr
                            v-for="quote in props.quotes.data"
                            :key="quote.id"
                            class="transition-colors hover:bg-zinc-800/30"
                        >
                            <td
                                class="px-4 py-4 font-mono text-xs text-zinc-300"
                            >
                                #{{ quote.sls_number }}
                            </td>
                            <td class="px-4 py-4 text-zinc-200">
                                {{ quote.customer?.clt_name ?? 'Desconocido' }}
                            </td>
                            <td class="px-4 py-4 text-zinc-400">
                                {{ formatDate(quote.sls_date) }}
                            </td>
                            <td
                                class="px-4 py-4 text-right font-mono text-zinc-200"
                            >
                                ${{ quote.sls_total }}
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span
                                    class="rounded-full px-2 py-1 text-xs"
                                    :class="statusClass[quote.sls_quote_status]"
                                >
                                    {{
                                        QUOTE_STATUS_LABEL[
                                            quote.sls_quote_status
                                        ]
                                    }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <div
                                    class="flex items-center justify-center gap-2"
                                >
                                    <button
                                        v-if="
                                            quote.sls_quote_status !==
                                            'converted'
                                        "
                                        type="button"
                                        @click="openConvert(quote)"
                                        class="text-xs font-semibold text-emerald-400 hover:text-emerald-300"
                                    >
                                        Convertir
                                    </button>
                                    <Link
                                        :href="`/quotes/${quote.id}/edit`"
                                        class="inline-flex items-center gap-1 text-xs text-indigo-400 hover:text-indigo-300"
                                    >
                                        <Pencil class="size-3" /> Editar
                                    </Link>
                                    <a
                                        :href="`/quotes/${quote.id}/pdf`"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center gap-1 text-xs text-zinc-400 hover:text-zinc-200"
                                    >
                                        <Download class="size-3" /> PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="props.quotes.links.length > 3"
                class="flex justify-center border-t border-zinc-800 px-6 py-4"
            >
                <div class="flex gap-1">
                    <template
                        v-for="(link, key) in props.quotes.links"
                        :key="key"
                    >
                        <div
                            v-if="link.url === null"
                            class="cursor-not-allowed rounded border border-zinc-700 px-4 py-2 text-sm text-zinc-500"
                            v-html="link.label"
                        ></div>
                        <Link
                            v-else
                            :href="link.url"
                            class="rounded border border-zinc-700 px-4 py-2 text-sm text-zinc-300 transition-colors hover:bg-zinc-800"
                            :class="{
                                'border-indigo-600 bg-indigo-600 font-bold text-white':
                                    link.active,
                            }"
                            ><span v-html="link.label"></span
                        ></Link>
                    </template>
                </div>
            </div>
        </div>

        <ConvertQuoteModal
            :open="convertOpen"
            :quote="convertTarget"
            @update:open="convertOpen = $event"
            @converted="router.reload()"
        />
    </div>
</template>

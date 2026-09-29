<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import {
    PlusCircle,
    Pencil,
    Trash2,
    MoreHorizontal,
    Package,
    Tags,
    Box,
    AlertCircle,
    Layers,
    Plus,
    X,
    ScrollText,
    ChevronLeft,
    ChevronRight,
    ArrowLeft,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import productRoutes from '@/routes/products';

interface Category {
    id: number;
    cat_name: string;
}

interface Brand {
    id: number;
    bra_name: string;
}

interface ProductComponent {
    cp_comp_pro_code: string;
    cp_pro_und: string;
    cp_quantity: number;
}

interface Product {
    id: number;
    pro_code: string;
    pro_name: string;
    pro_und: string;
    pro_conversion_factor?: number;
    pro_cant_min?: number;
    stocks_sum_stk_cant?: number;
    pro_iva: string;
    pro_cat_id: number;
    pro_bra_id: number;
    pro_type: string;
    category?: Category;
    brand?: Brand;
    componentes?: (Product & {
        pivot: {
            cp_comp_pro_code: string;
            cp_pro_und: string;
            cp_quantity: number;
        };
    })[];
}

const props = defineProps<{
    products: {
        data: Product[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    allProducts: Product[];
    categories: Category[];
    brands: Brand[];
    undOptions: string[];
    rawIvaOptions: string[];
    typeOptions: string[];
}>();

const isDialogOpen = ref(false);
const editingProduct = ref<Product | null>(null);

const form = useForm({
    pro_code: '',
    pro_name: '',
    pro_und: '',
    pro_conversion_factor: 1,
    pro_cant_min: 0,
    pro_iva: '',
    pro_cat_id: '' as unknown as number,
    pro_bra_id: '' as unknown as number,
    pro_type: 'simple',
    components: [] as ProductComponent[],
});

const openCreateModal = () => {
    editingProduct.value = null;
    form.reset();
    form.pro_type = 'simple';
    form.components = [];
    isDialogOpen.value = true;
};

const openEditModal = (product: Product) => {
    editingProduct.value = product;
    form.pro_code = product.pro_code;
    form.pro_name = product.pro_name;
    form.pro_und = product.pro_und;
    form.pro_conversion_factor = product.pro_conversion_factor ?? 1;
    form.pro_cant_min = product.pro_cant_min ?? 0;
    form.pro_iva = product.pro_iva;
    form.pro_cat_id = product.pro_cat_id;
    form.pro_bra_id = product.pro_bra_id;
    form.pro_type = product.pro_type;
    form.components = (product.componentes ?? []).map((c) => ({
        cp_comp_pro_code: c.pivot.cp_comp_pro_code,
        cp_pro_und: c.pivot.cp_pro_und,
        cp_quantity: c.pivot.cp_quantity,
    }));
    isDialogOpen.value = true;
};

const saveProduct = () => {
    if (editingProduct.value) {
        form.put(productRoutes.update.url(editingProduct.value.id), {
            onSuccess: () => {
                isDialogOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.post(productRoutes.store.url(), {
            onSuccess: () => {
                isDialogOpen.value = false;
                form.reset();
            },
        });
    }
};

const deleteProduct = (id: number) => {
    if (confirm('¿Estás seguro de eliminar este producto?')) {
        form.delete(productRoutes.destroy.url(id));
    }
};

const getUndLabel = (und: string) => {
    const labels: Record<string, string> = {
        und: 'Und',
        mts: 'Mts',
        kg: 'Kg',
        m2: 'm²',
    };

    return labels[und] || und;
};

const getTypeLabel = (type: string) => {
    return type === 'compuesto' ? 'Compuesto' : 'Simple';
};

const getProductByCode = (code: string): Product | undefined => {
    return props.allProducts.find((p) => p.pro_code === code);
};

const componentProducts = computed(() => {
    const currentCode = editingProduct.value?.pro_code;

    return props.allProducts.filter(
        (p) => p.pro_type === 'simple' && p.pro_code !== currentCode,
    );
});

const onSelectComponent = (index: number, proCode: string) => {
    form.components[index].cp_comp_pro_code = proCode;
    const product = getProductByCode(proCode);

    if (product) {
        form.components[index].cp_pro_und = product.pro_und;
    }
};

const addComponent = () => {
    form.components.push({
        cp_comp_pro_code: '',
        cp_pro_und: '',
        cp_quantity: 1,
    });
};

const removeComponent = (index: number) => {
    form.components.splice(index, 1);
};

const getAvailableComponents = (index: number) => {
    const selectedCodes = form.components.map((c) => c.cp_comp_pro_code);
    const currentCode = selectedCodes[index];

    return componentProducts.value.filter(
        (p) =>
            p.pro_code === currentCode || !selectedCodes.includes(p.pro_code),
    );
};

const goToPage = (page: number) => {
    router.get(
        '/products',
        { page },
        { preserveState: true, preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Productos" />

    <div class="min-h-screen bg-zinc-950 p-4 font-sans text-zinc-100 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <div
                class="flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-900 p-6 shadow-xl"
            >
                <div class="flex items-center gap-4">
                    <Link
                        href="/inventory"
                        class="rounded-full p-2 text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-white"
                    >
                        <ArrowLeft class="h-6 w-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Productos</h1>
                        <p class="text-xs text-zinc-500">
                            Administra el catálogo de productos del inventario.
                        </p>
                    </div>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white shadow-lg shadow-indigo-900/20 transition-all hover:bg-indigo-500"
                >
                    <PlusCircle class="mr-2 h-4 w-4" /> Añadir Producto
                </button>
            </div>

            <Dialog v-model:open="isDialogOpen">
                <DialogContent class="sm:max-w-[600px]">
                    <DialogHeader>
                        <DialogTitle>{{
                            editingProduct
                                ? 'Editar Producto'
                                : 'Añadir Nuevo Producto'
                        }}</DialogTitle>
                        <DialogDescription>
                            Ingresa los detalles del producto. Haz clic en
                            guardar cuando termines.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-4 py-4">
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="pro_code" class="text-right"
                                >Código</Label
                            >
                            <div class="col-span-3">
                                <Input
                                    id="pro_code"
                                    v-model="form.pro_code"
                                    placeholder="Ej: PRD-001"
                                    :class="{
                                        'border-red-500': form.errors.pro_code,
                                    }"
                                />
                                <p
                                    v-if="form.errors.pro_code"
                                    class="mt-1 text-xs text-red-500"
                                >
                                    {{ form.errors.pro_code }}
                                </p>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="pro_name" class="text-right"
                                >Nombre</Label
                            >
                            <div class="col-span-3">
                                <Input
                                    id="pro_name"
                                    v-model="form.pro_name"
                                    placeholder="Ej: Cemento Gris"
                                    :class="{
                                        'border-red-500': form.errors.pro_name,
                                    }"
                                />
                                <p
                                    v-if="form.errors.pro_name"
                                    class="mt-1 text-xs text-red-500"
                                >
                                    {{ form.errors.pro_name }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label class="text-right">Unidad</Label>
                            <div class="col-span-3">
                                <Select v-model="form.pro_und">
                                    <SelectTrigger
                                        :class="{
                                            'border-red-500':
                                                form.errors.pro_und,
                                        }"
                                    >
                                        <SelectValue
                                            placeholder="Selecciona la unidad"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="und in undOptions"
                                            :key="und"
                                            :value="und"
                                        >
                                            {{ getUndLabel(und) }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p
                                    v-if="form.errors.pro_und"
                                    class="mt-1 text-xs text-red-500"
                                >
                                    {{ form.errors.pro_und }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="
                                form.pro_und === 'mts' ||
                                form.pro_und === 'kg' ||
                                form.pro_und === 'm2'
                            "
                            class="grid grid-cols-4 items-center gap-4"
                        >
                            <Label
                                for="pro_conversion_factor"
                                class="text-right"
                                >Factor Conv.</Label
                            >
                            <div class="col-span-3">
                                <Input
                                    id="pro_conversion_factor"
                                    type="number"
                                    step="0.0001"
                                    min="0.0001"
                                    v-model="form.pro_conversion_factor"
                                    placeholder="Ej: 100 (1 = 100 {{ form.pro_und === 'mts' ? 'Mts' : form.pro_und === 'm2' ? 'M2' : 'Kg' }})"
                                />
                                <p class="mt-1 text-[10px] text-zinc-500">
                                    1 unidad de compra = N
                                    {{
                                        form.pro_und === 'mts'
                                            ? 'metros'
                                            : form.pro_und === 'm2'
                                              ? 'm²'
                                              : 'kilos'
                                    }}. Default: 1
                                </p>
                                <p
                                    v-if="form.errors.pro_conversion_factor"
                                    class="mt-1 text-xs text-red-500"
                                >
                                    {{ form.errors.pro_conversion_factor }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="pro_cant_min" class="text-right"
                                >Stock Mínimo</Label
                            >
                            <div class="col-span-3">
                                <Input
                                    id="pro_cant_min"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    v-model="form.pro_cant_min"
                                    placeholder="Ej: 10"
                                />
                                <p class="mt-1 text-[10px] text-zinc-500">
                                    Alerta cuando el stock baje de esta
                                    cantidad. 0 = sin alerta.
                                </p>
                                <p
                                    v-if="form.errors.pro_cant_min"
                                    class="mt-1 text-xs text-red-500"
                                >
                                    {{ form.errors.pro_cant_min }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label class="text-right">IVA (%)</Label>
                            <div class="col-span-3">
                                <Select v-model="form.pro_iva">
                                    <SelectTrigger
                                        :class="{
                                            'border-red-500':
                                                form.errors.pro_iva,
                                        }"
                                    >
                                        <SelectValue
                                            placeholder="Selecciona el IVA"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="iva in rawIvaOptions"
                                            :key="iva"
                                            :value="iva"
                                        >
                                            {{ iva }}%
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p
                                    v-if="form.errors.pro_iva"
                                    class="mt-1 text-xs text-red-500"
                                >
                                    {{ form.errors.pro_iva }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label class="text-right">Categoría</Label>
                            <div class="col-span-3">
                                <Select
                                    :model-value="
                                        form.pro_cat_id
                                            ? form.pro_cat_id.toString()
                                            : ''
                                    "
                                    @update:model-value="
                                        form.pro_cat_id = Number($event)
                                    "
                                >
                                    <SelectTrigger
                                        :class="{
                                            'border-red-500':
                                                form.errors.pro_cat_id,
                                        }"
                                    >
                                        <SelectValue
                                            placeholder="Selecciona la categoría"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="category in categories"
                                            :key="category.id"
                                            :value="category.id.toString()"
                                        >
                                            {{ category.cat_name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p
                                    v-if="form.errors.pro_cat_id"
                                    class="mt-1 text-xs text-red-500"
                                >
                                    {{ form.errors.pro_cat_id }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label class="text-right">Marca</Label>
                            <div class="col-span-3">
                                <Select
                                    :model-value="
                                        form.pro_bra_id
                                            ? form.pro_bra_id.toString()
                                            : ''
                                    "
                                    @update:model-value="
                                        form.pro_bra_id = Number($event)
                                    "
                                >
                                    <SelectTrigger
                                        :class="{
                                            'border-red-500':
                                                form.errors.pro_bra_id,
                                        }"
                                    >
                                        <SelectValue
                                            placeholder="Selecciona la marca"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="brand in brands"
                                            :key="brand.id"
                                            :value="brand.id.toString()"
                                        >
                                            {{ brand.bra_name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p
                                    v-if="form.errors.pro_bra_id"
                                    class="mt-1 text-xs text-red-500"
                                >
                                    {{ form.errors.pro_bra_id }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label class="text-right">Tipo</Label>
                            <div class="col-span-3">
                                <Select v-model="form.pro_type">
                                    <SelectTrigger
                                        :class="{
                                            'border-red-500':
                                                form.errors.pro_type,
                                        }"
                                    >
                                        <SelectValue
                                            placeholder="Selecciona el tipo"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="type in typeOptions"
                                            :key="type"
                                            :value="type"
                                        >
                                            {{ getTypeLabel(type) }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p
                                    v-if="form.errors.pro_type"
                                    class="mt-1 text-xs text-red-500"
                                >
                                    {{ form.errors.pro_type }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="form.pro_type === 'compuesto'"
                        class="mt-2 border-t pt-4"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <Label class="font-semibold">Componentes</Label>
                            <button
                                type="button"
                                @click="addComponent"
                                class="inline-flex items-center gap-1 rounded-lg border border-zinc-700 px-3 py-1.5 text-xs text-zinc-300 transition-colors hover:bg-zinc-800"
                            >
                                <Plus class="h-4 w-4" />
                                Agregar
                            </button>
                        </div>
                        <p
                            v-if="form.errors.components"
                            class="mb-2 text-xs text-red-500"
                        >
                            {{ form.errors.components }}
                        </p>

                        <div
                            v-for="(component, index) in form.components"
                            :key="index"
                            class="mb-3 flex items-end gap-2 rounded-lg border border-zinc-800 bg-zinc-900 p-3"
                        >
                            <div class="flex-1">
                                <Label class="mb-1 block text-xs text-zinc-400"
                                    >Producto</Label
                                >
                                <Select
                                    :model-value="component.cp_comp_pro_code"
                                    @update:model-value="
                                        onSelectComponent(
                                            index,
                                            $event as string,
                                        )
                                    "
                                >
                                    <SelectTrigger
                                        :class="{
                                            'border-red-500':
                                                form.errors[
                                                    `components.${index}.cp_comp_pro_code`
                                                ],
                                        }"
                                    >
                                        <SelectValue
                                            placeholder="Selecciona producto"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="p in getAvailableComponents(
                                                index,
                                            )"
                                            :key="p.pro_code"
                                            :value="p.pro_code"
                                        >
                                            {{ p.pro_name }} ({{ p.pro_code }})
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p
                                    v-if="
                                        form.errors[
                                            `components.${index}.cp_comp_pro_code`
                                        ]
                                    "
                                    class="mt-1 text-xs text-red-500"
                                >
                                    {{
                                        form.errors[
                                            `components.${index}.cp_comp_pro_code`
                                        ]
                                    }}
                                </p>
                            </div>
                            <div class="w-20">
                                <Label class="mb-1 block text-xs text-zinc-400"
                                    >Und.</Label
                                >
                                <Select v-model="component.cp_pro_und" disabled>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="u in undOptions"
                                            :key="u"
                                            :value="u"
                                        >
                                            {{ getUndLabel(u) }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="w-24">
                                <Label class="mb-1 block text-xs text-zinc-400"
                                    >Cantidad</Label
                                >
                                <Input
                                    type="number"
                                    step="0.01"
                                    min="0.001"
                                    v-model="component.cp_quantity"
                                />
                                <p
                                    v-if="
                                        form.errors[
                                            `components.${index}.cp_quantity`
                                        ]
                                    "
                                    class="mt-1 text-xs text-red-500"
                                >
                                    {{
                                        form.errors[
                                            `components.${index}.cp_quantity`
                                        ]
                                    }}
                                </p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg transition-colors hover:bg-zinc-800"
                                @click="removeComponent(index)"
                            >
                                <X class="h-4 w-4 text-red-400" />
                            </button>
                        </div>

                        <div
                            v-if="form.components.length === 0"
                            class="rounded-lg border border-dashed border-zinc-700 py-6 text-center text-sm text-zinc-500"
                        >
                            <AlertCircle
                                class="mx-auto mb-1 h-6 w-6 opacity-50"
                            />
                            Agrega los productos que componen este artículo
                        </div>
                    </div>

                    <DialogFooter>
                        <button
                            type="button"
                            @click="isDialogOpen = false"
                            class="rounded-lg border border-zinc-700 px-4 py-2 text-sm text-zinc-300 transition-colors hover:bg-zinc-800"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            @click="saveProduct"
                            :disabled="form.processing"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white transition-colors hover:bg-indigo-500 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <div
                v-if="products.data.length === 0"
                class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-700 bg-zinc-900 p-16 text-zinc-500"
            >
                <Package class="mb-4 h-16 w-16 opacity-20" />
                <p class="text-lg font-medium text-zinc-400">
                    No hay productos registrados
                </p>
                <p class="text-sm text-zinc-600">
                    Comienza añadiendo un nuevo producto a tu catálogo.
                </p>
            </div>

            <div
                v-else
                class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
            >
                <div
                    v-for="product in products.data"
                    :key="product.id"
                    class="group rounded-xl border border-zinc-800 bg-zinc-900 p-5 shadow-sm transition-all duration-200 hover:border-zinc-700 hover:shadow-md"
                >
                    <div class="mb-4 flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="shrink-0 rounded-lg bg-indigo-500/10 p-2.5"
                            >
                                <Box class="h-5 w-5 text-indigo-400" />
                            </div>
                            <div>
                                <h3
                                    class="leading-tight font-semibold text-zinc-100"
                                >
                                    {{ product.pro_name }}
                                </h3>
                                <p
                                    class="mt-0.5 font-mono text-[11px] text-zinc-500"
                                >
                                    {{ product.pro_code }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-1">
                            <Link
                                :href="'/kardex/' + product.pro_code"
                                class="rounded-md p-1.5 text-zinc-500 transition-colors hover:bg-zinc-800 hover:text-zinc-300"
                                title="Ver Kardex"
                            >
                                <ScrollText class="h-4 w-4" />
                            </Link>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <button
                                        class="rounded-md p-1.5 text-zinc-500 transition-colors hover:bg-zinc-800 hover:text-zinc-300"
                                    >
                                        <MoreHorizontal class="h-4 w-4" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent
                                    align="end"
                                    class="w-[160px]"
                                >
                                    <DropdownMenuLabel
                                        >Acciones</DropdownMenuLabel
                                    >
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        @select.prevent="openEditModal(product)"
                                    >
                                        <Pencil class="mr-2 h-4 w-4" />
                                        Editar
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        @select.prevent="
                                            deleteProduct(product.id)
                                        "
                                        class="text-red-600 focus:text-red-600"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Eliminar
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>

                    <div class="mb-3 flex flex-wrap gap-1.5">
                        <span
                            class="rounded-md bg-zinc-800 px-2 py-0.5 text-[11px] font-medium text-zinc-400"
                        >
                            {{ getUndLabel(product.pro_und) }}
                        </span>
                        <span
                            class="rounded-md bg-blue-900/30 px-2 py-0.5 text-[11px] font-medium text-blue-400"
                        >
                            IVA {{ product.pro_iva }}%
                        </span>
                        <span
                            v-if="product.pro_type === 'compuesto'"
                            class="inline-flex items-center gap-1 rounded-md bg-purple-900/30 px-2 py-0.5 text-[11px] font-medium text-purple-400"
                        >
                            <Layers class="h-3 w-3" /> Compuesto
                        </span>
                        <span
                            v-if="
                                product.pro_conversion_factor &&
                                product.pro_conversion_factor > 1
                            "
                            class="rounded-md bg-amber-900/30 px-2 py-0.5 text-[11px] font-medium text-amber-400"
                        >
                            ×{{ product.pro_conversion_factor }}
                        </span>
                    </div>

                    <div
                        v-if="
                            product.pro_type !== 'compuesto' &&
                            product.stocks_sum_stk_cant !== undefined
                        "
                        class="mb-3"
                    >
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-xs text-zinc-500"
                                >Stock total</span
                            >
                            <span
                                class="font-mono text-sm font-bold"
                                :class="
                                    (product.stocks_sum_stk_cant ?? 0) > 0
                                        ? 'text-zinc-100'
                                        : 'text-red-400'
                                "
                            >
                                {{ product.stocks_sum_stk_cant ?? 0 }}
                                <span
                                    class="text-[10px] font-normal text-zinc-500"
                                >
                                    {{ getUndLabel(product.pro_und) }}</span
                                >
                            </span>
                        </div>
                        <div
                            v-if="
                                product.pro_cant_min &&
                                product.pro_cant_min > 0 &&
                                (product.stocks_sum_stk_cant ?? 0) <=
                                    product.pro_cant_min
                            "
                            class="mt-1.5 flex items-center gap-1.5 rounded-md bg-amber-900/20 px-2 py-1 text-[11px] text-amber-400"
                        >
                            <AlertCircle class="h-3 w-3 shrink-0" />
                            Stock bajo (mín: {{ product.pro_cant_min }})
                        </div>
                    </div>

                    <div
                        v-if="
                            product.pro_type === 'compuesto' &&
                            product.componentes &&
                            product.componentes.length > 0
                        "
                        class="mb-3 border-t border-zinc-800 pt-3"
                    >
                        <p
                            class="mb-2 text-[10px] font-semibold tracking-wider text-zinc-500 uppercase"
                        >
                            Componentes
                        </p>
                        <div class="space-y-1">
                            <div
                                v-for="comp in product.componentes"
                                :key="comp.pro_code"
                                class="flex items-center justify-between text-xs"
                            >
                                <span class="truncate pr-2 text-zinc-400">{{
                                    comp.pro_name
                                }}</span>
                                <span class="shrink-0 font-mono text-zinc-500"
                                    >{{ comp.pivot.cp_quantity }}
                                    {{
                                        getUndLabel(comp.pivot.cp_pro_und)
                                    }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex items-center gap-3 border-t border-zinc-800 pt-3"
                    >
                        <div
                            v-if="product.category"
                            class="flex min-w-0 items-center gap-1.5 text-xs text-zinc-500"
                        >
                            <Tags class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">{{
                                product.category.cat_name
                            }}</span>
                        </div>
                        <div
                            v-if="product.brand"
                            class="flex min-w-0 items-center gap-1.5 text-xs text-zinc-500"
                        >
                            <Package class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">{{
                                product.brand.bra_name
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="products.last_page > 1"
                class="flex items-center justify-between px-1"
            >
                <p class="text-sm text-zinc-500">
                    Mostrando {{ products.from }}-{{ products.to }} de
                    {{ products.total }} productos
                </p>
                <div class="flex items-center gap-1">
                    <button
                        class="rounded-lg p-2 text-zinc-500 transition-colors hover:bg-zinc-800 hover:text-zinc-300 disabled:opacity-30"
                        :disabled="products.current_page <= 1"
                        @click="goToPage(products.current_page - 1)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>
                    <template v-for="page in products.last_page" :key="page">
                        <button
                            v-if="
                                page === 1 ||
                                page === products.last_page ||
                                Math.abs(page - products.current_page) <= 1
                            "
                            class="h-8 min-w-[32px] rounded-lg font-mono text-sm transition-colors"
                            :class="
                                page === products.current_page
                                    ? 'bg-indigo-600 font-bold text-white'
                                    : 'text-zinc-400 hover:bg-zinc-800 hover:text-zinc-300'
                            "
                            @click="goToPage(page)"
                        >
                            {{ page }}
                        </button>
                        <span
                            v-else-if="
                                page === products.current_page - 2 ||
                                page === products.current_page + 2
                            "
                            class="px-1 text-sm text-zinc-600 select-none"
                            >...</span
                        >
                    </template>
                    <button
                        class="rounded-lg p-2 text-zinc-500 transition-colors hover:bg-zinc-800 hover:text-zinc-300 disabled:opacity-30"
                        :disabled="products.current_page >= products.last_page"
                        @click="goToPage(products.current_page + 1)"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

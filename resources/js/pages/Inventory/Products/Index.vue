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
    componentes?: (Product & { pivot: { cp_comp_pro_code: string; cp_pro_und: string; cp_quantity: number } })[];
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
    form.components = (product.componentes ?? []).map(c => ({
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
        'und': 'Und',
        'mts': 'Mts',
        'kg': 'Kg',
        'm2': 'm²'
    };

    return labels[und] || und;
};

const getTypeLabel = (type: string) => {
    return type === 'compuesto' ? 'Compuesto' : 'Simple';
};

const getProductByCode = (code: string): Product | undefined => {
    return props.allProducts.find(p => p.pro_code === code);
};

const componentProducts = computed(() => {
    const currentCode = editingProduct.value?.pro_code;

    return props.allProducts.filter(p =>
        p.pro_type === 'simple' && p.pro_code !== currentCode
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
    const selectedCodes = form.components.map(c => c.cp_comp_pro_code);
    const currentCode = selectedCodes[index];

    return componentProducts.value.filter(p =>
        p.pro_code === currentCode || !selectedCodes.includes(p.pro_code)
    );
};

const goToPage = (page: number) => {
    router.get('/products', { page }, { preserveState: true, preserveScroll: true });
};
</script>

<template>
    <Head title="Productos" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex items-center justify-between bg-zinc-900 p-6 rounded-xl border border-zinc-800 shadow-xl">
                <div class="flex items-center gap-4">
                    <Link href="/inventory" class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <ArrowLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Productos</h1>
                        <p class="text-xs text-zinc-500">Administra el catálogo de productos del inventario.</p>
                    </div>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-semibold transition-all shadow-lg shadow-indigo-900/20"
                >
                    <PlusCircle class="w-4 h-4 mr-2" /> Añadir Producto
                </button>
            </div>

            <Dialog v-model:open="isDialogOpen">
                <DialogContent class="sm:max-w-[600px]">
                    <DialogHeader>
                        <DialogTitle>{{ editingProduct ? 'Editar Producto' : 'Añadir Nuevo Producto' }}</DialogTitle>
                        <DialogDescription>
                            Ingresa los detalles del producto. Haz clic en guardar cuando termines.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-4 py-4">
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="pro_code" class="text-right">Código</Label>
                            <div class="col-span-3">
                                <Input id="pro_code" v-model="form.pro_code" placeholder="Ej: PRD-001" :class="{ 'border-red-500': form.errors.pro_code }" />
                                <p v-if="form.errors.pro_code" class="text-xs text-red-500 mt-1">{{ form.errors.pro_code }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="pro_name" class="text-right">Nombre</Label>
                            <div class="col-span-3">
                                <Input id="pro_name" v-model="form.pro_name" placeholder="Ej: Cemento Gris" :class="{ 'border-red-500': form.errors.pro_name }" />
                                <p v-if="form.errors.pro_name" class="text-xs text-red-500 mt-1">{{ form.errors.pro_name }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label class="text-right">Unidad</Label>
                            <div class="col-span-3">
                                <Select v-model="form.pro_und">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.pro_und }">
                                        <SelectValue placeholder="Selecciona la unidad" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="und in undOptions" :key="und" :value="und">
                                            {{ getUndLabel(und) }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.pro_und" class="text-xs text-red-500 mt-1">{{ form.errors.pro_und }}</p>
                            </div>
                        </div>

                        <div v-if="form.pro_und === 'mts' || form.pro_und === 'kg' || form.pro_und === 'm2'" class="grid grid-cols-4 items-center gap-4">
                            <Label for="pro_conversion_factor" class="text-right">Factor Conv.</Label>
                            <div class="col-span-3">
                                <Input id="pro_conversion_factor" type="number" step="0.0001" min="0.0001" v-model="form.pro_conversion_factor" placeholder="Ej: 100 (1 = 100 {{ form.pro_und === 'mts' ? 'Mts' : form.pro_und === 'm2' ? 'M2' : 'Kg' }})" />
                                <p class="text-[10px] text-zinc-500 mt-1">1 unidad de compra = N {{ form.pro_und === 'mts' ? 'metros' : form.pro_und === 'm2' ? 'm²' : 'kilos' }}. Default: 1</p>
                                <p v-if="form.errors.pro_conversion_factor" class="text-xs text-red-500 mt-1">{{ form.errors.pro_conversion_factor }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="pro_cant_min" class="text-right">Stock Mínimo</Label>
                            <div class="col-span-3">
                                <Input id="pro_cant_min" type="number" step="0.01" min="0" v-model="form.pro_cant_min" placeholder="Ej: 10" />
                                <p class="text-[10px] text-zinc-500 mt-1">Alerta cuando el stock baje de esta cantidad. 0 = sin alerta.</p>
                                <p v-if="form.errors.pro_cant_min" class="text-xs text-red-500 mt-1">{{ form.errors.pro_cant_min }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label class="text-right">IVA (%)</Label>
                            <div class="col-span-3">
                                <Select v-model="form.pro_iva">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.pro_iva }">
                                        <SelectValue placeholder="Selecciona el IVA" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="iva in rawIvaOptions" :key="iva" :value="iva">
                                            {{ iva }}%
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.pro_iva" class="text-xs text-red-500 mt-1">{{ form.errors.pro_iva }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label class="text-right">Categoría</Label>
                            <div class="col-span-3">
                                <Select :model-value="form.pro_cat_id ? form.pro_cat_id.toString() : ''" @update:model-value="form.pro_cat_id = Number($event)">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.pro_cat_id }">
                                        <SelectValue placeholder="Selecciona la categoría" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="category in categories" :key="category.id" :value="category.id.toString()">
                                            {{ category.cat_name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.pro_cat_id" class="text-xs text-red-500 mt-1">{{ form.errors.pro_cat_id }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label class="text-right">Marca</Label>
                            <div class="col-span-3">
                                <Select :model-value="form.pro_bra_id ? form.pro_bra_id.toString() : ''" @update:model-value="form.pro_bra_id = Number($event)">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.pro_bra_id }">
                                        <SelectValue placeholder="Selecciona la marca" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="brand in brands" :key="brand.id" :value="brand.id.toString()">
                                            {{ brand.bra_name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.pro_bra_id" class="text-xs text-red-500 mt-1">{{ form.errors.pro_bra_id }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label class="text-right">Tipo</Label>
                            <div class="col-span-3">
                                <Select v-model="form.pro_type">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.pro_type }">
                                        <SelectValue placeholder="Selecciona el tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="type in typeOptions" :key="type" :value="type">
                                            {{ getTypeLabel(type) }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.pro_type" class="text-xs text-red-500 mt-1">{{ form.errors.pro_type }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="form.pro_type === 'compuesto'" class="border-t pt-4 mt-2">
                        <div class="flex items-center justify-between mb-3">
                            <Label class="font-semibold">Componentes</Label>
                            <button type="button" @click="addComponent" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs border border-zinc-700 rounded-lg hover:bg-zinc-800 transition-colors text-zinc-300">
                                <Plus class="w-4 h-4" />
                                Agregar
                            </button>
                        </div>
                        <p v-if="form.errors.components" class="text-xs text-red-500 mb-2">{{ form.errors.components }}</p>

                        <div v-for="(component, index) in form.components" :key="index" class="flex items-end gap-2 mb-3 p-3 border border-zinc-800 rounded-lg bg-zinc-900">
                            <div class="flex-1">
                                <Label class="text-xs mb-1 block text-zinc-400">Producto</Label>
                                <Select :model-value="component.cp_comp_pro_code" @update:model-value="onSelectComponent(index, $event)">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors[`components.${index}.cp_comp_pro_code`] }">
                                        <SelectValue placeholder="Selecciona producto" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="p in getAvailableComponents(index)" :key="p.pro_code" :value="p.pro_code">
                                            {{ p.pro_name }} ({{ p.pro_code }})
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors[`components.${index}.cp_comp_pro_code`]" class="text-xs text-red-500 mt-1">{{ form.errors[`components.${index}.cp_comp_pro_code`] }}</p>
                            </div>
                            <div class="w-20">
                                <Label class="text-xs mb-1 block text-zinc-400">Und.</Label>
                                <Select v-model="component.cp_pro_und" disabled>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="u in undOptions" :key="u" :value="u">
                                            {{ getUndLabel(u) }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="w-24">
                                <Label class="text-xs mb-1 block text-zinc-400">Cantidad</Label>
                                <Input type="number" step="0.01" min="0.001" v-model="component.cp_quantity" />
                                <p v-if="form.errors[`components.${index}.cp_quantity`]" class="text-xs text-red-500 mt-1">{{ form.errors[`components.${index}.cp_quantity`] }}</p>
                            </div>
                            <button type="button" class="inline-flex items-center justify-center h-9 w-9 shrink-0 rounded-lg hover:bg-zinc-800 transition-colors" @click="removeComponent(index)">
                                <X class="h-4 w-4 text-red-400" />
                            </button>
                        </div>

                        <div v-if="form.components.length === 0" class="text-center py-6 text-sm text-zinc-500 border border-dashed border-zinc-700 rounded-lg">
                            <AlertCircle class="h-6 w-6 mx-auto mb-1 opacity-50" />
                            Agrega los productos que componen este artículo
                        </div>
                    </div>

                    <DialogFooter>
                        <button type="button" @click="isDialogOpen = false" class="px-4 py-2 text-sm border border-zinc-700 rounded-lg hover:bg-zinc-800 transition-colors text-zinc-300">Cancelar</button>
                        <button type="submit" @click="saveProduct" :disabled="form.processing" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-semibold transition-colors disabled:opacity-50">
                            {{ form.processing ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <div v-if="products.data.length === 0" class="bg-zinc-900 border border-dashed border-zinc-700 rounded-xl p-16 flex flex-col items-center justify-center text-zinc-500">
                <Package class="h-16 w-16 mb-4 opacity-20" />
                <p class="text-lg font-medium text-zinc-400">No hay productos registrados</p>
                <p class="text-sm text-zinc-600">Comienza añadiendo un nuevo producto a tu catálogo.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div
                    v-for="product in products.data"
                    :key="product.id"
                    class="group bg-zinc-900 border border-zinc-800 rounded-xl p-5 shadow-sm hover:shadow-md hover:border-zinc-700 transition-all duration-200"
                >
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-indigo-500/10 rounded-lg shrink-0">
                                <Box class="h-5 w-5 text-indigo-400" />
                            </div>
                            <div>
                                <h3 class="font-semibold text-zinc-100 leading-tight">{{ product.pro_name }}</h3>
                                <p class="text-[11px] font-mono text-zinc-500 mt-0.5">{{ product.pro_code }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-1">
                            <Link :href="'/kardex/' + product.pro_code" class="p-1.5 rounded-md hover:bg-zinc-800 transition-colors text-zinc-500 hover:text-zinc-300" title="Ver Kardex">
                                <ScrollText class="h-4 w-4" />
                            </Link>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <button class="p-1.5 rounded-md hover:bg-zinc-800 transition-colors text-zinc-500 hover:text-zinc-300">
                                        <MoreHorizontal class="h-4 w-4" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-[160px]">
                                    <DropdownMenuLabel>Acciones</DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem @select.prevent="openEditModal(product)">
                                        <Pencil class="mr-2 h-4 w-4" />
                                        Editar
                                    </DropdownMenuItem>
                                    <DropdownMenuItem @select.prevent="deleteProduct(product.id)" class="text-red-600 focus:text-red-600">
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Eliminar
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-1.5 mb-3">
                        <span class="text-[11px] px-2 py-0.5 rounded-md bg-zinc-800 text-zinc-400 font-medium">
                            {{ getUndLabel(product.pro_und) }}
                        </span>
                        <span class="text-[11px] px-2 py-0.5 rounded-md bg-blue-900/30 text-blue-400 font-medium">
                            IVA {{ product.pro_iva }}%
                        </span>
                        <span v-if="product.pro_type === 'compuesto'" class="text-[11px] px-2 py-0.5 rounded-md bg-purple-900/30 text-purple-400 font-medium inline-flex items-center gap-1">
                            <Layers class="h-3 w-3" /> Compuesto
                        </span>
                        <span v-if="product.pro_conversion_factor && product.pro_conversion_factor > 1" class="text-[11px] px-2 py-0.5 rounded-md bg-amber-900/30 text-amber-400 font-medium">
                            ×{{ product.pro_conversion_factor }}
                        </span>
                    </div>

                    <div v-if="product.pro_type !== 'compuesto' && product.stocks_sum_stk_cant !== undefined" class="mb-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500 text-xs">Stock total</span>
                            <span class="font-mono text-sm font-bold" :class="(product.stocks_sum_stk_cant ?? 0) > 0 ? 'text-zinc-100' : 'text-red-400'">
                                {{ product.stocks_sum_stk_cant ?? 0 }}
                                <span class="text-[10px] font-normal text-zinc-500"> {{ getUndLabel(product.pro_und) }}</span>
                            </span>
                        </div>
                        <div v-if="product.pro_cant_min && product.pro_cant_min > 0 && (product.stocks_sum_stk_cant ?? 0) <= product.pro_cant_min" class="mt-1.5 flex items-center gap-1.5 text-[11px] text-amber-400 bg-amber-900/20 px-2 py-1 rounded-md">
                            <AlertCircle class="h-3 w-3 shrink-0" />
                            Stock bajo (mín: {{ product.pro_cant_min }})
                        </div>
                    </div>

                    <div v-if="product.pro_type === 'compuesto' && product.componentes && product.componentes.length > 0" class="border-t border-zinc-800 pt-3 mb-3">
                        <p class="text-[10px] font-semibold text-zinc-500 mb-2 uppercase tracking-wider">Componentes</p>
                        <div class="space-y-1">
                            <div v-for="comp in product.componentes" :key="comp.pro_code" class="flex items-center justify-between text-xs">
                                <span class="text-zinc-400 truncate pr-2">{{ comp.pro_name }}</span>
                                <span class="text-zinc-500 font-mono shrink-0">{{ comp.pivot.cp_quantity }} {{ getUndLabel(comp.pivot.cp_pro_und) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-3 border-t border-zinc-800">
                        <div v-if="product.category" class="flex items-center gap-1.5 text-xs text-zinc-500 min-w-0">
                            <Tags class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">{{ product.category.cat_name }}</span>
                        </div>
                        <div v-if="product.brand" class="flex items-center gap-1.5 text-xs text-zinc-500 min-w-0">
                            <Package class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">{{ product.brand.bra_name }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="products.last_page > 1" class="flex items-center justify-between px-1">
                <p class="text-sm text-zinc-500">
                    Mostrando {{ products.from }}-{{ products.to }} de {{ products.total }} productos
                </p>
                <div class="flex items-center gap-1">
                    <button
                        class="p-2 rounded-lg text-zinc-500 hover:bg-zinc-800 hover:text-zinc-300 transition-colors disabled:opacity-30"
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
                            class="min-w-[32px] h-8 text-sm font-mono rounded-lg transition-colors"
                            :class="page === products.current_page
                                ? 'bg-indigo-600 text-white font-bold'
                                : 'text-zinc-400 hover:bg-zinc-800 hover:text-zinc-300'"
                            @click="goToPage(page)"
                        >
                            {{ page }}
                        </button>
                        <span
                            v-else-if="
                                page === products.current_page - 2 ||
                                page === products.current_page + 2
                            "
                            class="px-1 text-zinc-600 select-none text-sm"
                        >...</span>
                    </template>
                    <button
                        class="p-2 rounded-lg text-zinc-500 hover:bg-zinc-800 hover:text-zinc-300 transition-colors disabled:opacity-30"
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

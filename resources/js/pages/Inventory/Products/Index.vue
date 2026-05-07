<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { 
    PackageSearch, 
    PlusCircle, 
    Pencil, 
    Trash2, 
    MoreHorizontal,
    Package,
    Tags,
    Box
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import productRoutes from '@/routes/products';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Inventario', href: '#' },
            { title: 'Productos', href: '/products' },
        ],
    },
});

interface Category {
    id: number;
    cat_name: string;
}

interface Brand {
    id: number;
    bra_name: string;
}

interface Product {
    id: number;
    pro_code: string;
    pro_name: string;
    pro_und: string;
    pro_iva: string;
    pro_cat_id: number;
    pro_bra_id: number;
    category?: Category;
    brand?: Brand;
}

const props = defineProps<{
    products: Product[];
    categories: Category[];
    brands: Brand[];
    undOptions: string[];
    rawIvaOptions: string[];
}>();

const isDialogOpen = ref(false);
const editingProduct = ref<Product | null>(null);

const form = useForm({
    pro_code: '',
    pro_name: '',
    pro_und: '',
    pro_iva: '',
    pro_cat_id: '' as unknown as number,
    pro_bra_id: '' as unknown as number,
});

const openCreateModal = () => {
    editingProduct.value = null;
    form.reset();
    isDialogOpen.value = true;
};

const openEditModal = (product: Product) => {
    console.log('Opening edit modal for product:', product);
    editingProduct.value = product;
    form.pro_code = product.pro_code;
    form.pro_name = product.pro_name;
    form.pro_und = product.pro_und;
    form.pro_iva = product.pro_iva;
    form.pro_cat_id = product.pro_cat_id;
    form.pro_bra_id = product.pro_bra_id;
    isDialogOpen.value = true;
};

const saveProduct = () => {
    console.log('Saving product, editing:', editingProduct.value);
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
        'und': 'Unidad',
        'mts': 'Metros',
        'kg': 'Kilogramos'
    };
    return labels[und] || und;
};
</script>

<template>
    <Head title="Productos" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                    <PackageSearch class="h-8 w-8 text-primary" />
                    Productos
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Administra el catálogo de productos del inventario.
                </p>
            </div>
            
            <Button @click="openCreateModal" class="shadow-md hover:shadow-lg transition-all">
                <PlusCircle class="mr-2 h-4 w-4" />
                Añadir Producto
            </Button>
        </div>

        <Dialog v-model:open="isDialogOpen">
            <DialogContent class="sm:max-w-[500px]">
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
                </div>
                
                <DialogFooter>
                    <Button type="button" variant="outline" @click="isDialogOpen = false">Cancelar</Button>
                    <Button type="submit" @click="saveProduct" :disabled="form.processing">
                        {{ form.processing ? 'Guardando...' : 'Guardar' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-if="products.length === 0" class="col-span-full py-20 bg-white dark:bg-gray-900 border border-dashed border-gray-300 dark:border-gray-700 rounded-2xl flex flex-col items-center justify-center text-gray-500">
                <Package class="h-16 w-16 mb-4 opacity-20" />
                <p class="text-lg font-medium">No hay productos registrados</p>
                <p class="text-sm">Comienza añadiendo un nuevo producto a tu catálogo.</p>
            </div>
            
            <div v-for="product in products" :key="product.id" 
                class="group relative bg-white dark:bg-gray-900 border border-sidebar-border rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden"
            >
                <div class="absolute top-0 right-0 -m-4 h-24 w-24 bg-primary/5 rounded-full blur-2xl group-hover:bg-primary/10 transition-colors"></div>
                
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-primary/10 rounded-xl">
                        <Box class="h-6 w-6 text-primary" />
                    </div>
                    
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" size="icon" class="h-8 w-8 rounded-full">
                                <MoreHorizontal class="h-4 w-4" />
                            </Button>
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
                
                <div class="space-y-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ product.pro_name }}</h3>
                        <p class="text-sm font-mono text-gray-500 dark:text-gray-400 mt-1">Código: {{ product.pro_code }}</p>
                    </div>
                    
                    <div class="flex flex-wrap gap-2">
                        <Badge variant="outline" class="bg-gray-50 dark:bg-gray-800 border-primary/20 text-primary">
                            {{ getUndLabel(product.pro_und) }}
                        </Badge>
                        <Badge variant="outline" class="bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400">
                            IVA {{ product.pro_iva }}%
                        </Badge>
                    </div>

                    <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400 mt-4 border-t border-gray-100 dark:border-gray-800 pt-4">
                        <div class="flex items-center gap-1.5 min-w-0" v-if="product.category">
                            <Tags class="h-4 w-4 shrink-0" />
                            <span class="truncate">{{ product.category?.cat_name }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 border-l border-gray-200 dark:border-gray-700 pl-4 min-w-0" v-if="product.brand">
                            <Package class="h-4 w-4 shrink-0" />
                            <span class="truncate">{{ product.brand?.bra_name }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

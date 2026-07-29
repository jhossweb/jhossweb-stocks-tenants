<script setup lang="ts">
import { Search, Loader2, Check, PlusCircle } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { 
    Dialog, 
    DialogContent, 
    DialogHeader, 
    DialogTitle, 
    DialogTrigger,
    DialogDescription 
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

interface Props {
    title: string;
    description?: string;
    items: any[];
    searchFields: string[];
    displayFields: { label: string; key: string; class?: string }[];
    triggerLabel?: string;
    triggerClass?: string;
    placeholder?: string;
    selectedValue?: string | number;
}

const props = defineProps<Props>();
const emit = defineEmits(['select']);

const isOpen = ref(false);
const searchQuery = ref('');

const filteredItems = computed(() => {
    if (!searchQuery.value) {
return props.items;
}

    const query = searchQuery.value.toLowerCase();
    
    return props.items.filter(item => {
        return props.searchFields.some(field => {
            const value = item[field];

            return value && String(value).toLowerCase().includes(query);
        });
    });
});

const handleSelect = (item: any) => {
    emit('select', item);
    isOpen.value = false;
    searchQuery.value = '';
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogTrigger as-child>
            <slot name="trigger">
                <Button 
                    variant="outline" 
                    type="button"
                    :class="cn('w-full justify-start text-left font-normal bg-zinc-950 border-zinc-800 text-zinc-300 hover:bg-zinc-900 transition-all', triggerClass)"
                >
                    {{ triggerLabel || 'Seleccionar...' }}
                </Button>
            </slot>
        </DialogTrigger>
        
        <DialogContent class="sm:max-w-[600px] bg-zinc-950 border-zinc-800 p-0 overflow-hidden shadow-2xl flex flex-col gap-0">
            <DialogHeader class="p-6 pb-0">
                <DialogTitle class="text-xl font-bold text-zinc-100 flex items-center gap-2">
                    {{ title }}
                </DialogTitle>
                <DialogDescription v-if="description" class="text-zinc-500">
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <div class="p-6 space-y-4 flex-1 flex flex-col min-h-0">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" />
                    <Input 
                        v-model="searchQuery" 
                        :placeholder="placeholder || 'Buscar...'" 
                        class="pl-10 bg-zinc-900 border-zinc-800 text-zinc-100 focus:ring-indigo-600 h-10"
                        auto-focus
                    />
                </div>

                <div class="flex-1 overflow-y-auto max-h-[400px] rounded-lg border border-zinc-800 bg-zinc-900/50 custom-scrollbar">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-zinc-900 border-b border-zinc-800 text-zinc-500 text-[10px] uppercase font-bold z-10">
                            <tr>
                                <th v-for="field in displayFields" :key="field.key" class="px-4 py-3 text-left">
                                    {{ field.label }}
                                </th>
                                <th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr 
                                v-for="(item, index) in filteredItems" 
                                :key="index"
                                @click="handleSelect(item)"
                                class="hover:bg-indigo-600/10 cursor-pointer transition-colors group"
                            >
                                <td v-for="field in displayFields" :key="field.key" :class="cn('px-4 py-3 text-zinc-300 group-hover:text-white transition-colors', field.class)">
                                    {{ item[field.key] }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="w-5 h-5 flex items-center justify-center rounded-full bg-zinc-800 group-hover:bg-indigo-600 transition-all">
                                        <Check v-if="selectedValue === item.id || selectedValue === item.pro_code" class="w-3 h-3 text-white" />
                                        <PlusCircle v-else class="w-3 h-3 text-zinc-500 group-hover:text-white" />
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredItems.length === 0">
                                <td :colspan="displayFields.length + 1" class="px-4 py-12 text-center text-zinc-500">
                                    No se encontraron resultados para "{{ searchQuery }}"
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #27272a;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #3f3f46;
}
</style>

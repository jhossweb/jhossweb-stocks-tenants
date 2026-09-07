<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();

// Helper to check if any child (or grandchild) is active
const hasActiveChild = (item: NavItem): boolean => {
    if (isCurrentUrl(item.href)) {
        return true;
    }

    if (item.items) {
        return item.items.some((child) => hasActiveChild(child));
    }

    return false;
};
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="item.title">
                <!-- If item has sub-items -->
                <Collapsible
                    v-if="item.items && item.items.length > 0"
                    as-child
                    :default-open="hasActiveChild(item)"
                    class="group/collapsible"
                >
                    <SidebarMenuItem>
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton
                                :tooltip="item.title"
                                :is-active="hasActiveChild(item)"
                            >
                                <component :is="item.icon" v-if="item.icon" />
                                <span>{{ item.title }}</span>
                                <ChevronRight
                                    class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                                />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <template
                                    v-for="subItem in item.items"
                                    :key="subItem.title"
                                >
                                    <!-- Nested Collapsible for second level if needed -->
                                    <Collapsible
                                        v-if="
                                            subItem.items &&
                                            subItem.items.length > 0
                                        "
                                        as-child
                                        :default-open="hasActiveChild(subItem)"
                                        class="group/sub-collapsible"
                                    >
                                        <SidebarMenuSubItem>
                                            <CollapsibleTrigger as-child>
                                                <SidebarMenuSubButton
                                                    :is-active="
                                                        hasActiveChild(subItem)
                                                    "
                                                >
                                                    <span>{{
                                                        subItem.title
                                                    }}</span>
                                                    <ChevronRight
                                                        class="ml-auto size-3 transition-transform duration-200 group-data-[state=open]/sub-collapsible:rotate-90"
                                                    />
                                                </SidebarMenuSubButton>
                                            </CollapsibleTrigger>
                                            <CollapsibleContent>
                                                <SidebarMenuSub>
                                                    <SidebarMenuSubItem
                                                        v-for="grandChild in subItem.items"
                                                        :key="grandChild.title"
                                                    >
                                                        <SidebarMenuSubButton
                                                            as-child
                                                            :is-active="
                                                                isCurrentUrl(
                                                                    grandChild.href,
                                                                )
                                                            "
                                                        >
                                                            <Link
                                                                :href="
                                                                    grandChild.href
                                                                "
                                                            >
                                                                <span>{{
                                                                    grandChild.title
                                                                }}</span>
                                                            </Link>
                                                        </SidebarMenuSubButton>
                                                    </SidebarMenuSubItem>
                                                </SidebarMenuSub>
                                            </CollapsibleContent>
                                        </SidebarMenuSubItem>
                                    </Collapsible>

                                    <!-- Simple sub-item -->
                                    <SidebarMenuSubItem v-else>
                                        <SidebarMenuSubButton
                                            as-child
                                            :is-active="
                                                isCurrentUrl(subItem.href)
                                            "
                                        >
                                            <Link :href="subItem.href">
                                                <span>{{ subItem.title }}</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                </template>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </SidebarMenuItem>
                </Collapsible>

                <!-- Single level item -->
                <SidebarMenuItem v-else>
                    <SidebarMenuButton
                        as-child
                        :is-active="isCurrentUrl(item.href)"
                        :tooltip="item.title"
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" v-if="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>

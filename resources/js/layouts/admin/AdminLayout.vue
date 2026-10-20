<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LogOut } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import Toaster from '@/components/ui/sonner.vue';
import { useFlashToast } from '@/composables/useFlashToast';
import { logout } from '@/routes';
import { index as paymentsIndex } from '@/routes/admin/payments';
import { index as plansIndex } from '@/routes/admin/plans';
import { index as ratesIndex } from '@/routes/admin/rates';
import { index as subscriptionsIndex } from '@/routes/admin/subscriptions';
import { index as tenantsIndex } from '@/routes/admin/tenants';
import type { User } from '@/types/auth';

useFlashToast();

const page = usePage();
const user = computed<User | null>(() => {
    const auth = page.props.auth as { user?: User } | undefined;

    return auth?.user ?? null;
});

const navItems = [
    { label: 'Tenants', href: tenantsIndex.url() },
    { label: 'Planes', href: plansIndex.url() },
    { label: 'Suscripciones', href: subscriptionsIndex.url() },
    { label: 'Tasas', href: ratesIndex.url() },
    { label: 'Cobros', href: paymentsIndex.url() },
];
</script>

<template>
    <Toaster />
    <div class="min-h-screen bg-background text-foreground">
        <header
            class="sticky top-0 z-40 w-full border-b bg-background/80 backdrop-blur"
        >
            <div
                class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-4 px-4"
            >
                <div class="flex items-center gap-6">
                    <Link
                        :href="tenantsIndex.url()"
                        class="text-sm font-semibold tracking-tight"
                    >
                        Vidrialum ERP · SaaS Admin
                    </Link>
                    <nav class="flex items-center gap-1 text-sm">
                        <Link
                            v-for="item in navItems"
                            :key="item.label"
                            :href="item.href"
                            class="rounded-md px-3 py-1.5 text-muted-foreground hover:bg-accent hover:text-foreground"
                        >
                            {{ item.label }}
                        </Link>
                    </nav>
                </div>

                <div
                    v-if="user"
                    class="flex items-center gap-3 text-sm text-muted-foreground"
                >
                    <span class="hidden sm:inline">{{ user.email }}</span>
                    <Button variant="outline" size="sm" as-child>
                        <Link as="button" :href="logout()">
                            <LogOut class="size-4" />
                            Salir
                        </Link>
                    </Button>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8">
            <slot />
        </main>
    </div>
</template>

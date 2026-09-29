<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTenantRegistration } from '@/composables/Tenancy/useTenantRegistration';
import { home } from '@/routes';
import { store } from '@/routes/register-company';
import type { CompanyCreateProps } from '@/types/Tenancy';

defineOptions({
    layout: {
        title: 'Create your company',
        description: 'Get started with Vidrialum ERP for your business',
    },
});

const props = defineProps<CompanyCreateProps>();

const { companyName, domainPreview, canSubmit } = useTenantRegistration(
    props.domainSuffix,
);
</script>

<template>
    <Head title="Create your company" />

    <Form
        method="post"
        :action="store().url"
        :reset-on-success="['admin_password', 'admin_password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="company_name">Company name</Label>
                <Input
                    id="company_name"
                    v-model="companyName"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="organization"
                    name="company_name"
                    placeholder="Vidrialum C.A."
                />
                <InputError :message="errors.company_name" />
            </div>

            <div
                v-if="canSubmit"
                class="rounded-md border bg-muted px-3 py-2 text-sm text-muted-foreground"
            >
                Your workspace:
                <span class="font-medium">{{ domainPreview }}</span>
            </div>

            <div class="grid gap-2">
                <Label for="admin_name">Your name</Label>
                <Input
                    id="admin_name"
                    type="text"
                    required
                    :tabindex="2"
                    autocomplete="name"
                    name="admin_name"
                    placeholder="Full name"
                />
                <InputError :message="errors.admin_name" />
            </div>

            <div class="grid gap-2">
                <Label for="admin_email">Work email</Label>
                <Input
                    id="admin_email"
                    type="email"
                    required
                    :tabindex="3"
                    autocomplete="email"
                    name="admin_email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.admin_email" />
            </div>

            <div class="grid gap-2">
                <Label for="admin_password">Password</Label>
                <PasswordInput
                    id="admin_password"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="admin_password"
                    placeholder="Password"
                />
                <InputError :message="errors.admin_password" />
            </div>

            <div class="grid gap-2">
                <Label for="admin_password_confirmation"
                    >Confirm password</Label
                >
                <PasswordInput
                    id="admin_password_confirmation"
                    required
                    :tabindex="5"
                    autocomplete="new-password"
                    name="admin_password_confirmation"
                    placeholder="Confirm password"
                />
                <InputError :message="errors.admin_password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="6"
                :disabled="processing"
            >
                <Spinner v-if="processing" />
                Create company
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <TextLink
                :href="home()"
                class="underline underline-offset-4"
                :tabindex="7"
                >Back to home</TextLink
            >
        </div>
    </Form>
</template>

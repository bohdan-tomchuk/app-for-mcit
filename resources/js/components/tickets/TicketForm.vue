<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Option, TicketFormValues } from '@/types';

const fieldClass =
    'border-input dark:bg-input/30 w-full rounded-md border bg-transparent px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm';

withDefaults(
    defineProps<{
        action: {
            action: string;
            method: 'get' | 'post' | 'put' | 'patch' | 'delete';
        };
        priorities: Option[];
        values?: TicketFormValues;
        submitLabel: string;
    }>(),
    { values: () => ({ title: '', description: '', priority: 'normal' }) },
);
</script>

<template>
    <Form
        v-bind="action"
        class="max-w-2xl space-y-6"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-2">
            <Label for="title">Назва</Label>
            <Input
                id="title"
                name="title"
                :default-value="values.title"
                required
            />
            <InputError :message="errors.title" />
        </div>

        <div class="grid gap-2">
            <Label for="description">Опис</Label>
            <textarea
                id="description"
                name="description"
                rows="6"
                :class="fieldClass"
                :value="values.description"
                required
            />
            <InputError :message="errors.description" />
        </div>

        <div class="grid gap-2">
            <Label for="priority">Пріоритет</Label>
            <select
                id="priority"
                name="priority"
                :class="fieldClass"
                :value="values.priority"
            >
                <option v-for="p in priorities" :key="p.value" :value="p.value">
                    {{ p.label }}
                </option>
            </select>
            <InputError :message="errors.priority" />
        </div>

        <Button :disabled="processing" data-test="submit-ticket-button">
            {{ submitLabel }}
        </Button>
    </Form>
</template>

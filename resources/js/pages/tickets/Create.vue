<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import TicketForm from '@/components/tickets/TicketForm.vue';
import { create, index, store } from '@/routes/tickets';
import type { Option } from '@/types';

defineProps<{ priorities: Option[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Заявки', href: index() },
            { title: 'Нова заявка', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="Нова заявка" />

    <div class="p-4">
        <Heading title="Нова заявка" />
        <TicketForm
            :action="store.form()"
            :priorities="priorities"
            submit-label="Створити"
        />
    </div>
</template>

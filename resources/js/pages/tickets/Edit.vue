<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import TicketForm from '@/components/tickets/TicketForm.vue';
import { edit, index, show, update } from '@/routes/tickets';
import type { Option, TicketFormValues } from '@/types';

const props = defineProps<{
    ticket: TicketFormValues & { id: number };
    priorities: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Заявки', href: index() }],
    },
});
</script>

<template>
    <Head title="Редагування заявки" />

    <div class="p-4">
        <Heading title="Редагування заявки" />
        <TicketForm
            :action="update.form(ticket.id)"
            :priorities="priorities"
            :values="ticket"
            submit-label="Зберегти"
        />
    </div>
</template>

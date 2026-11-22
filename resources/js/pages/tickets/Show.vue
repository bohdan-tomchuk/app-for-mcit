<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { index } from '@/routes/tickets';
import type { Ticket } from '@/types';

defineProps<{ ticket: Ticket }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Заявки', href: index() }],
    },
});

const formatDate = (iso: string | null | undefined) =>
    iso ? new Date(iso).toLocaleString('uk-UA') : '';
</script>

<template>
    <Head :title="ticket.title" />

    <div class="max-w-3xl space-y-6 p-4">
        <Heading :title="ticket.title" />

        <dl class="grid grid-cols-[10rem_1fr] gap-y-2 text-sm">
            <dt class="text-muted-foreground">Статус</dt>
            <dd><Badge variant="secondary">{{ ticket.status.label }}</Badge></dd>
            <dt class="text-muted-foreground">Пріоритет</dt>
            <dd>{{ ticket.priority.label }}</dd>
            <dt class="text-muted-foreground">Автор</dt>
            <dd>{{ ticket.author }}</dd>
            <dt class="text-muted-foreground">Створено</dt>
            <dd>{{ formatDate(ticket.created_at) }}</dd>
            <dt class="text-muted-foreground">Оновлено</dt>
            <dd>{{ formatDate(ticket.updated_at) }}</dd>
        </dl>

        <p class="whitespace-pre-wrap" data-test="ticket-description">{{ ticket.description }}</p>

        <section data-test="ticket-history">
            <h2 class="mb-2 font-semibold">Історія</h2>
            <ul class="text-sm">
                <li>Створено {{ ticket.author }}, {{ formatDate(ticket.created_at) }}</li>
            </ul>
        </section>
    </div>
</template>

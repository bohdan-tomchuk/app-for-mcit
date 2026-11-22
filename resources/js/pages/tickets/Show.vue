<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index, status } from '@/routes/tickets';
import type { Option, StatusChange, Ticket } from '@/types';

const props = defineProps<{
    ticket: Ticket;
    history: StatusChange[];
    transitions: Option[];
}>();

const changeStatus = (target: string) =>
    router.patch(status(props.ticket.id).url, { status: target }, { preserveScroll: true });

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

        <div v-if="transitions.length" class="flex gap-2" data-test="ticket-transitions">
            <Button
                v-for="t in transitions"
                :key="t.value"
                @click="changeStatus(t.value)"
            >
                {{ t.value === 'in_progress' ? 'Взяти в роботу' : 'Завершити' }}
            </Button>
        </div>
        <InputError :message="$page.props.errors.status" />

        <p class="whitespace-pre-wrap" data-test="ticket-description">{{ ticket.description }}</p>

        <section data-test="ticket-history">
            <h2 class="mb-2 font-semibold">Історія</h2>
            <ul class="text-sm">
                <li>Створено {{ ticket.author }}, {{ formatDate(ticket.created_at) }}</li>
                <li v-for="h in history" :key="h.id">
                    {{ h.from_status.label }} → {{ h.to_status.label }}, {{ h.actor }}, {{ formatDate(h.created_at) }}
                </li>
            </ul>
        </section>
    </div>
</template>

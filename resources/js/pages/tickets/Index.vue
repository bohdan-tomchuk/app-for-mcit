<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { create, index, show } from '@/routes/tickets';
import type { Paginated, Ticket } from '@/types';

const props = defineProps<{
    tickets: Paginated<Ticket>;
    isAdmin: boolean;
    can: { create: boolean };
    filters: { status: string | null; search: string | null };
    statuses: { value: string; label: string }[];
}>();

const ALL = 'all';

const search = ref(props.filters.search ?? '');

const visit = (status: string | null, term: string) =>
    router.get(
        index().url,
        {
            ...(status && status !== ALL ? { status } : {}),
            ...(term.trim() ? { search: term.trim() } : {}),
        },
        { preserveScroll: true, preserveState: true, replace: true },
    );

const applyStatus = (value: unknown) =>
    visit(value ? String(value) : null, search.value);

const applySearch = useDebounceFn(
    () => visit(props.filters.status, search.value),
    300,
);

const reset = () => {
    search.value = '';
    visit(null, '');
};

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Заявки', href: index() }],
    },
});

const formatDate = (iso: string | null) =>
    iso ? new Date(iso).toLocaleDateString('uk-UA') : '';
</script>

<template>
    <Head title="Заявки" />

    <div class="p-4">
        <div class="flex items-start justify-between">
            <Heading title="Заявки" />
            <Button v-if="can.create" as-child>
                <Link :href="create()" data-test="new-ticket-button">
                    Нова заявка
                </Link>
            </Button>
        </div>

        <div class="my-4 flex items-center gap-2">
            <Input
                v-model="search"
                class="w-64"
                placeholder="Пошук за назвою"
                data-test="search-input"
                @input="applySearch"
            />
            <Select
                :model-value="filters.status ?? ALL"
                @update:model-value="applyStatus"
            >
                <SelectTrigger class="w-48" data-test="status-filter">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL">Усі</SelectItem>
                    <SelectItem
                        v-for="s in statuses"
                        :key="s.value"
                        :value="s.value"
                    >
                        {{ s.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <Button variant="outline" data-test="filter-reset" @click="reset">
                Скинути
            </Button>
        </div>

        <div
            v-if="tickets.data.length === 0"
            class="py-12 text-center text-muted-foreground"
            data-test="tickets-empty"
        >
            <template v-if="filters.status || filters.search">
                <p>Нічого не знайдено</p>
                <Button
                    class="mt-2"
                    variant="outline"
                    data-test="filter-empty-reset"
                    @click="reset"
                >
                    Скинути фільтр
                </Button>
            </template>
            <p v-else>
                {{ isAdmin ? 'Заявок немає' : 'У вас ще немає заявок' }}
            </p>
        </div>

        <template v-else>
            <table class="w-full text-left text-sm" data-test="tickets-table">
                <thead class="border-b text-muted-foreground">
                    <tr>
                        <th class="py-2">ID</th>
                        <th>Назва</th>
                        <th v-if="isAdmin">Автор</th>
                        <th>Пріоритет</th>
                        <th>Статус</th>
                        <th>Створено</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="ticket in tickets.data"
                        :key="ticket.id"
                        class="border-b"
                    >
                        <td class="py-2">{{ ticket.id }}</td>
                        <td>
                            <Link
                                :href="show(ticket.id)"
                                class="font-medium hover:underline"
                                data-test="ticket-link"
                            >
                                {{ ticket.title }}
                            </Link>
                        </td>
                        <td v-if="isAdmin">{{ ticket.author }}</td>
                        <td>{{ ticket.priority.label }}</td>
                        <td>
                            <Badge variant="secondary">{{
                                ticket.status.label
                            }}</Badge>
                        </td>
                        <td>{{ formatDate(ticket.created_at) }}</td>
                    </tr>
                </tbody>
            </table>

            <nav v-if="tickets.meta.last_page > 1" class="mt-4 flex gap-2">
                <Button v-if="tickets.links.prev" as-child variant="outline">
                    <Link :href="tickets.links.prev" preserve-scroll
                        >Назад</Link
                    >
                </Button>
                <span class="self-center text-sm">
                    {{ tickets.meta.current_page }} /
                    {{ tickets.meta.last_page }}
                </span>
                <Button v-if="tickets.links.next" as-child variant="outline">
                    <Link :href="tickets.links.next" preserve-scroll>Далі</Link>
                </Button>
            </nav>
        </template>
    </div>
</template>

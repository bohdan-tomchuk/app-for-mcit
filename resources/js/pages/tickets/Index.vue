<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/tickets';

defineProps<{ can: { create: boolean } }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Заявки', href: index() }],
    },
});
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
    </div>
</template>

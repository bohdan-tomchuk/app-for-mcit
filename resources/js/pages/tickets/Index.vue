<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/tickets';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Заявки', href: index() }],
    },
});
</script>

<template>
    <Head title="Заявки" />

    <div class="p-4">
        <Heading title="Заявки" />
    </div>
</template>

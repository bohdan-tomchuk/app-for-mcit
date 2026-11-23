<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { login } from '@/routes';
import { index } from '@/routes/tickets';
</script>

<template>
    <Head title="Tickets Panel" />
    <main
        class="flex min-h-screen flex-col items-center justify-center gap-4 p-6 text-center"
    >
        <h1 class="text-3xl font-semibold">Tickets Panel</h1>
        <p class="text-muted-foreground">
            Створюйте заявки, відстежуйте їх статус та історію змін.
        </p>
        <Link
            :href="$page.props.auth.user ? index() : login()"
            class="rounded-md bg-primary px-5 py-2 text-sm text-primary-foreground hover:opacity-90"
        >
            {{ $page.props.auth.user ? 'До заявок' : 'Увійти' }}
        </Link>
    </main>
</template>

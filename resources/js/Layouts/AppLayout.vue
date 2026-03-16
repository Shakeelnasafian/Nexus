<script setup>
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const nav = [
    { label: 'Vendors',    href: '/vendors' },
    { label: 'Workflows',  href: '/workflows' },
    { label: 'Objectives', href: '/objectives' },
    { label: 'SLA Records',href: '/sla-records' },
];
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Top nav -->
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
                <Link href="/" class="text-xl font-bold text-indigo-600">Nexus</Link>
                <div class="flex gap-6">
                    <Link
                        v-for="item in nav"
                        :key="item.href"
                        :href="item.href"
                        class="text-sm font-medium text-gray-600 hover:text-indigo-600 transition-colors"
                    >
                        {{ item.label }}
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Page content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <slot />
        </main>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ workflows: Array });
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Workflows</h1>
            <Link href="/workflows/create" class="btn-primary">New Workflow</Link>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="th">Name</th>
                        <th class="th">Trigger</th>
                        <th class="th">Steps</th>
                        <th class="th">Active</th>
                        <th class="th"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="wf in workflows" :key="wf.id">
                        <td class="td font-medium text-gray-900">{{ wf.name }}</td>
                        <td class="td">
                            <span class="font-mono text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded">
                                {{ wf.trigger_event }}
                            </span>
                        </td>
                        <td class="td text-gray-500">{{ wf.steps_count }}</td>
                        <td class="td">
                            <span :class="wf.is_active ? 'text-green-600' : 'text-gray-400'">
                                {{ wf.is_active ? 'Yes' : 'No' }}
                            </span>
                        </td>
                        <td class="td text-right">
                            <Link :href="`/workflows/${wf.id}`" class="link">View</Link>
                        </td>
                    </tr>
                    <tr v-if="!workflows.length">
                        <td colspan="5" class="td text-center text-gray-400">No workflows yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<style>
.th { @apply px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider; }
.td { @apply px-6 py-4 text-sm; }
.link { @apply text-indigo-600 hover:text-indigo-800 font-medium; }
.btn-primary { @apply inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors; }
</style>

<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ workflow: Object, runs: Array });
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <Link href="/workflows" class="text-sm text-gray-500 hover:text-gray-700">← Workflows</Link>
                <h1 class="text-2xl font-semibold text-gray-900 mt-1">{{ workflow.name }}</h1>
                <p class="font-mono text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded inline-block mt-1">
                    {{ workflow.trigger_event }}
                </p>
            </div>
            <span :class="workflow.is_active ? 'text-green-600' : 'text-gray-400'" class="text-sm font-medium">
                {{ workflow.is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>

        <!-- Steps -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Steps</h2>
            <ol class="space-y-2">
                <li v-for="step in workflow.steps" :key="step.id"
                    class="flex items-center gap-3 text-sm">
                    <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                        {{ step.sort_order }}
                    </span>
                    <span class="font-mono bg-gray-100 px-2 py-0.5 rounded text-xs">{{ step.action_type }}</span>
                    <span class="text-gray-500 truncate">{{ JSON.stringify(step.payload) }}</span>
                </li>
                <li v-if="!workflow.steps.length" class="text-gray-400">No steps configured.</li>
            </ol>
        </div>

        <!-- Recent Runs -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <h2 class="px-6 py-4 border-b border-gray-200 text-base font-semibold text-gray-900">Recent Runs</h2>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="th">ID</th>
                        <th class="th">Status</th>
                        <th class="th">Started</th>
                        <th class="th">Completed / Failed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="run in runs" :key="run.id">
                        <td class="td text-gray-500">#{{ run.id }}</td>
                        <td class="td">
                            <span :class="{
                                'bg-yellow-100 text-yellow-800': run.status === 'pending',
                                'bg-blue-100 text-blue-800':    run.status === 'running',
                                'bg-green-100 text-green-800':  run.status === 'completed',
                                'bg-red-100 text-red-800':      run.status === 'failed',
                            }" class="badge capitalize">{{ run.status }}</span>
                        </td>
                        <td class="td text-gray-500">{{ run.started_at ?? '—' }}</td>
                        <td class="td text-gray-500">{{ run.completed_at ?? run.failed_at ?? '—' }}</td>
                    </tr>
                    <tr v-if="!runs.length">
                        <td colspan="4" class="td text-center text-gray-400">No runs yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

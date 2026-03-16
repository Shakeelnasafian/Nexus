<script setup>
import { Link, useForm } from '@inertiajs/vue3';

defineProps({ objectives: Array });

const form = useForm({});
const act = (id, action) => form.post(`/objectives/${id}/${action}`);

const statusColor = (status) => ({
    active:    'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    missed:    'bg-red-100 text-red-800',
}[status] ?? 'bg-gray-100 text-gray-800');
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Objectives</h1>
            <Link href="/objectives/create" class="btn-primary">New Objective</Link>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="th">Title</th>
                        <th class="th">Due</th>
                        <th class="th">Status</th>
                        <th class="th">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="obj in objectives" :key="obj.id">
                        <td class="td font-medium text-gray-900">{{ obj.title }}</td>
                        <td class="td text-gray-500">{{ obj.due_date ?? '—' }}</td>
                        <td class="td">
                            <span :class="['badge', statusColor(obj.status)]">{{ obj.status }}</span>
                        </td>
                        <td class="td">
                            <div v-if="obj.status === 'active'" class="flex gap-2">
                                <button @click="act(obj.id, 'complete')" class="btn-sm-success">Complete</button>
                                <button @click="act(obj.id, 'miss')" class="btn-sm-danger">Miss</button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!objectives.length">
                        <td colspan="4" class="td text-center text-gray-400">No objectives yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

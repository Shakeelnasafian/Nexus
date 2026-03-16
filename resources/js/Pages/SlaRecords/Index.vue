<script setup>
defineProps({ records: Array });

const statusColor = (status) => ({
    active:  'bg-green-100 text-green-800',
    breached:'bg-red-100 text-red-800',
    closed:  'bg-gray-100 text-gray-700',
}[status] ?? 'bg-gray-100 text-gray-700');
</script>

<template>
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">SLA Records</h1>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="th">Title</th>
                        <th class="th">Vendor</th>
                        <th class="th">Status</th>
                        <th class="th">Started</th>
                        <th class="th">Closed / Breached</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="record in records" :key="record.id">
                        <td class="td font-medium text-gray-900">{{ record.title }}</td>
                        <td class="td text-gray-500">{{ record.vendor?.name ?? '—' }}</td>
                        <td class="td">
                            <span :class="['badge', statusColor(record.status)]">{{ record.status }}</span>
                        </td>
                        <td class="td text-gray-500">{{ record.started_at }}</td>
                        <td class="td text-gray-500">
                            {{ record.closed_at ?? record.breached_at ?? '—' }}
                        </td>
                    </tr>
                    <tr v-if="!records.length">
                        <td colspan="5" class="td text-center text-gray-400">No SLA records yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

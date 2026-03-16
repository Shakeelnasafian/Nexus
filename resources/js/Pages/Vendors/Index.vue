<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ vendors: Array });

const statusColor = (status) => ({
    pending:    'bg-yellow-100 text-yellow-800',
    active:     'bg-green-100 text-green-800',
    suspended:  'bg-orange-100 text-orange-800',
    terminated: 'bg-red-100 text-red-800',
}[status] ?? 'bg-gray-100 text-gray-800');
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Vendors</h1>
            <Link href="/vendors/create" class="btn-primary">Add Vendor</Link>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="th">Name</th>
                        <th class="th">Code</th>
                        <th class="th">Status</th>
                        <th class="th">Created</th>
                        <th class="th"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="vendor in vendors" :key="vendor.id">
                        <td class="td font-medium text-gray-900">{{ vendor.name }}</td>
                        <td class="td text-gray-500">{{ vendor.code }}</td>
                        <td class="td">
                            <span :class="['badge', statusColor(vendor.state)]">
                                {{ vendor.state }}
                            </span>
                        </td>
                        <td class="td text-gray-500">{{ vendor.created_at }}</td>
                        <td class="td text-right">
                            <Link :href="`/vendors/${vendor.id}`" class="link">View</Link>
                        </td>
                    </tr>
                    <tr v-if="!vendors.length">
                        <td colspan="5" class="td text-center text-gray-400">No vendors yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

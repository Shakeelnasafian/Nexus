<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ vendor: Object, contracts: Array });

const actionForm = useForm({});
const act = (action) => actionForm.post(`/vendors/${props.vendor.id}/${action}`);
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <Link href="/vendors" class="text-sm text-gray-500 hover:text-gray-700">← Vendors</Link>
                <h1 class="text-2xl font-semibold text-gray-900 mt-1">{{ vendor.name }}</h1>
                <p class="text-sm text-gray-500">{{ vendor.code }}</p>
            </div>
            <div class="flex gap-2">
                <button v-if="vendor.state === 'pending'"
                    @click="act('activate')" class="btn-success">Activate</button>
                <button v-if="vendor.state === 'active'"
                    @click="act('suspend')" class="btn-warning">Suspend</button>
                <button v-if="vendor.state === 'suspended'"
                    @click="act('reinstate')" class="btn-success">Reinstate</button>
                <button v-if="['active','suspended'].includes(vendor.state)"
                    @click="act('terminate')" class="btn-danger">Terminate</button>
            </div>
        </div>

        <!-- Contracts -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-medium text-gray-900">Contracts</h2>
                <Link :href="`/vendors/${vendor.id}/contracts/create`" class="btn-primary">Add Contract</Link>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="th">Title</th>
                        <th class="th">Status</th>
                        <th class="th">Starts</th>
                        <th class="th"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="contract in contracts" :key="contract.id">
                        <td class="td font-medium text-gray-900">{{ contract.title }}</td>
                        <td class="td">
                            <span class="badge bg-gray-100 text-gray-700 capitalize">{{ contract.state }}</span>
                        </td>
                        <td class="td text-gray-500">{{ contract.starts_at }}</td>
                        <td class="td text-right">
                            <Link :href="`/contracts/${contract.id}`" class="link">View</Link>
                        </td>
                    </tr>
                    <tr v-if="!contracts.length">
                        <td colspan="4" class="td text-center text-gray-400">No contracts yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

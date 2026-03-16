<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ contract: Object });

const form = useForm({});
const act = (action) => form.post(`/contracts/${props.contract.id}/${action}`);
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <Link :href="`/vendors/${contract.vendor_id}`" class="text-sm text-gray-500 hover:text-gray-700">
                    ← Back to Vendor
                </Link>
                <h1 class="text-2xl font-semibold text-gray-900 mt-1">{{ contract.title }}</h1>
                <p class="text-sm text-gray-500 capitalize">Status: {{ contract.state }}</p>
            </div>
            <div class="flex gap-2">
                <button v-if="contract.state === 'draft'"
                    @click="act('activate')" class="btn-success">Activate</button>
                <button v-if="contract.state === 'active'"
                    @click="act('terminate')" class="btn-danger">Terminate</button>
            </div>
        </div>

        <dl class="bg-white shadow rounded-lg p-6 grid grid-cols-2 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">Start Date</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ contract.starts_at }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">End Date</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ contract.ends_at ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Created</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ contract.created_at }}</dd>
            </div>
        </dl>
    </div>
</template>

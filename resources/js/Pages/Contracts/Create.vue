<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ vendor: Object });

const form = useForm({ title: '', starts_at: '', ends_at: '' });
const submit = () => form.post(`/vendors/${props.vendor.id}/contracts`);
</script>

<template>
    <div class="max-w-lg">
        <Link :href="`/vendors/${vendor.id}`" class="text-sm text-gray-500 hover:text-gray-700">
            ← {{ vendor.name }}
        </Link>
        <h1 class="text-2xl font-semibold text-gray-900 mt-2 mb-6">New Contract</h1>

        <form @submit.prevent="submit" class="bg-white shadow rounded-lg p-6 space-y-4">
            <div>
                <label class="label">Title</label>
                <input v-model="form.title" type="text" class="input" required />
                <p v-if="form.errors.title" class="error">{{ form.errors.title }}</p>
            </div>
            <div>
                <label class="label">Start Date</label>
                <input v-model="form.starts_at" type="date" class="input" required />
                <p v-if="form.errors.starts_at" class="error">{{ form.errors.starts_at }}</p>
            </div>
            <div>
                <label class="label">End Date <span class="text-gray-400">(optional)</span></label>
                <input v-model="form.ends_at" type="date" class="input" />
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <Link :href="`/vendors/${vendor.id}`" class="btn-secondary">Cancel</Link>
                <button type="submit" :disabled="form.processing" class="btn-primary">
                    Create Contract
                </button>
            </div>
        </form>
    </div>
</template>

<style>
.label  { @apply block text-sm font-medium text-gray-700 mb-1; }
.input  { @apply block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500; }
.error  { @apply mt-1 text-sm text-red-600; }
.btn-primary   { @apply inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50; }
.btn-secondary { @apply inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors; }
</style>

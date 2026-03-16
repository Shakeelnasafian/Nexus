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

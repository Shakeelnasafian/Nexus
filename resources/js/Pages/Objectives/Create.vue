<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({ title: '', description: '', due_date: '' });
const submit = () => form.post('/objectives');
</script>

<template>
    <div class="max-w-lg">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">New Objective</h1>

        <form @submit.prevent="submit" class="bg-white shadow rounded-lg p-6 space-y-4">
            <div>
                <label class="label">Title</label>
                <input v-model="form.title" type="text" class="input" required />
                <p v-if="form.errors.title" class="error">{{ form.errors.title }}</p>
            </div>
            <div>
                <label class="label">Description <span class="text-gray-400">(optional)</span></label>
                <textarea v-model="form.description" rows="3" class="input" />
            </div>
            <div>
                <label class="label">Due Date <span class="text-gray-400">(optional)</span></label>
                <input v-model="form.due_date" type="date" class="input" />
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <Link href="/objectives" class="btn-secondary">Cancel</Link>
                <button type="submit" :disabled="form.processing" class="btn-primary">
                    Create Objective
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

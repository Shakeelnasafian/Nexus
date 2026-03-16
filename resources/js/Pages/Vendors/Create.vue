<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({ name: '', code: '' });
const submit = () => form.post('/vendors');
</script>

<template>
    <div class="max-w-lg">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">Add Vendor</h1>

        <form @submit.prevent="submit" class="bg-white shadow rounded-lg p-6 space-y-4">
            <div>
                <label class="label">Name</label>
                <input v-model="form.name" type="text" class="input" required />
                <p v-if="form.errors.name" class="error">{{ form.errors.name }}</p>
            </div>
            <div>
                <label class="label">Code <span class="text-gray-400">(unique slug)</span></label>
                <input v-model="form.code" type="text" class="input" required />
                <p v-if="form.errors.code" class="error">{{ form.errors.code }}</p>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <a href="/vendors" class="btn-secondary">Cancel</a>
                <button type="submit" :disabled="form.processing" class="btn-primary">
                    Create Vendor
                </button>
            </div>
        </form>
    </div>
</template>

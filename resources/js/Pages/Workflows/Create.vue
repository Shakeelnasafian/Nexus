<script setup>
import { useForm } from '@inertiajs/vue3';

defineProps({ triggers: Array });

const form = useForm({ name: '', trigger_event: '', is_active: true });
const submit = () => form.post('/workflows');
</script>

<template>
    <div class="max-w-lg">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">New Workflow</h1>

        <form @submit.prevent="submit" class="bg-white shadow rounded-lg p-6 space-y-4">
            <div>
                <label class="label">Name</label>
                <input v-model="form.name" type="text" class="input" required />
                <p v-if="form.errors.name" class="error">{{ form.errors.name }}</p>
            </div>
            <div>
                <label class="label">Trigger</label>
                <select v-model="form.trigger_event" class="input" required>
                    <option value="" disabled>Select a trigger…</option>
                    <option v-for="trigger in triggers" :key="trigger" :value="trigger">
                        {{ trigger }}
                    </option>
                </select>
                <p v-if="form.errors.trigger_event" class="error">{{ form.errors.trigger_event }}</p>
            </div>
            <div class="flex items-center gap-3">
                <input id="active" v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600" />
                <label for="active" class="text-sm font-medium text-gray-700">Active</label>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <a href="/workflows" class="btn-secondary">Cancel</a>
                <button type="submit" :disabled="form.processing" class="btn-primary">
                    Create Workflow
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

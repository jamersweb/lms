<template>
  <AppShell>
    <div class="max-w-2xl mx-auto space-y-6">
      <!-- Back Button -->
      <Link href="/admin/habits" class="inline-flex items-center gap-2 text-neutral-600 hover:text-neutral-900">
        <ArrowLeft class="w-4 h-4" />
        Back to Habits
      </Link>

      <!-- Form -->
      <div class="bg-white rounded-xl border border-neutral-200 p-6">
        <div class="flex items-center justify-between mb-6">
          <h1 class="font-serif text-2xl font-bold text-neutral-900">Edit Habit</h1>
          <div class="text-sm text-neutral-500">
            For: <span class="text-primary-600 font-medium">{{ habit.user.name }}</span>
          </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Habit Title *</label>
            <input
              v-model="form.title"
              type="text"
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              required
            />
            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Description</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              placeholder="Optional description or instructions"
            ></textarea>
            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Frequency *</label>
            <div class="flex gap-4">
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="form.frequency_type"
                  type="radio"
                  value="daily"
                  class="h-4 w-4 text-primary-600 border-neutral-300 focus:ring-primary-500"
                />
                <span class="text-neutral-700">Daily</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="form.frequency_type"
                  type="radio"
                  value="weekly"
                  class="h-4 w-4 text-primary-600 border-neutral-300 focus:ring-primary-500"
                />
                <span class="text-neutral-700">Weekly</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="form.frequency_type"
                  type="radio"
                  value="custom"
                  class="h-4 w-4 text-primary-600 border-neutral-300 focus:ring-primary-500"
                />
                <span class="text-neutral-700">Custom</span>
              </label>
            </div>
            <p v-if="form.errors.frequency_type" class="mt-1 text-sm text-red-600">{{ form.errors.frequency_type }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Target Per Day</label>
            <input
              v-model="form.target_per_day"
              type="number"
              min="1"
              max="10"
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
            />
            <p v-if="form.errors.target_per_day" class="mt-1 text-sm text-red-600">{{ form.errors.target_per_day }}</p>
          </div>

          <div class="flex items-center gap-3 pt-4 border-t border-neutral-100">
            <button
              type="submit"
              :disabled="form.processing"
              class="px-6 py-2 bg-primary-900 text-white rounded-lg font-medium hover:bg-primary-800 transition-colors disabled:opacity-50"
            >
              {{ form.processing ? 'Saving...' : 'Save Changes' }}
            </button>
            <Link href="/admin/habits" class="px-6 py-2 text-neutral-600 hover:text-neutral-900">
              Cancel
            </Link>
          </div>
        </form>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

const props = defineProps({
  habit: Object,
});

const form = useForm({
  title: props.habit.title,
  description: props.habit.description || '',
  frequency_type: props.habit.frequency_type || 'daily',
  target_per_day: props.habit.target_per_day || 1,
});

const submit = () => {
  form.put(`/admin/habits/${props.habit.id}`);
};
</script>

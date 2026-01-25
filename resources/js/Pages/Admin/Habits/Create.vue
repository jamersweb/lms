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
        <h1 class="font-serif text-2xl font-bold text-neutral-900 mb-6">Create Habit for User</h1>

        <form @submit.prevent="submit" class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Select User *</label>
            <select
              v-model="form.user_id"
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              required
            >
              <option value="">Choose a user...</option>
              <option v-for="user in users" :key="user.id" :value="user.id">
                {{ user.name }} ({{ user.email }})
              </option>
            </select>
            <p v-if="form.errors.user_id" class="mt-1 text-sm text-red-600">{{ form.errors.user_id }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Habit Name *</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              placeholder="e.g., Morning Dhikr, Read Quran"
              required
            />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Description</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              placeholder="Optional description or instructions for this habit"
            ></textarea>
            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Frequency *</label>
            <div class="flex gap-4">
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="form.frequency"
                  type="radio"
                  value="daily"
                  class="h-4 w-4 text-primary-600 border-neutral-300 focus:ring-primary-500"
                />
                <span class="text-neutral-700">Daily</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="form.frequency"
                  type="radio"
                  value="weekly"
                  class="h-4 w-4 text-primary-600 border-neutral-300 focus:ring-primary-500"
                />
                <span class="text-neutral-700">Weekly</span>
              </label>
            </div>
            <p v-if="form.errors.frequency" class="mt-1 text-sm text-red-600">{{ form.errors.frequency }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Reminder Time</label>
            <input
              v-model="form.reminder_time"
              type="time"
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
            />
            <p class="mt-1 text-xs text-neutral-500">Optional: Set a daily reminder time</p>
          </div>

          <div class="flex items-center gap-3 pt-4 border-t border-neutral-100">
            <button
              type="submit"
              :disabled="form.processing"
              class="px-6 py-2 bg-primary-900 text-white rounded-lg font-medium hover:bg-primary-800 transition-colors disabled:opacity-50"
            >
              {{ form.processing ? 'Creating...' : 'Create Habit' }}
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
  users: Array,
  selectedUserId: [Number, String],
});

const form = useForm({
  user_id: props.selectedUserId || '',
  name: '',
  description: '',
  frequency: 'daily',
  reminder_time: '',
});

const submit = () => {
  form.post('/admin/habits');
};
</script>

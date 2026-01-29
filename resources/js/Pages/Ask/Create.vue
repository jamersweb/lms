<template>
  <AppShell>
    <div class="max-w-2xl mx-auto">
      <div class="mb-6">
        <h1 class="font-serif text-2xl font-bold text-neutral-900">New Question</h1>
        <p class="text-sm text-neutral-600">
          Describe your question clearly so mentors can help you effectively.
        </p>
      </div>

      <form @submit.prevent="submit" class="space-y-4 bg-white border border-neutral-200 rounded-xl p-6">
        <div>
          <label for="subject" class="block text-sm font-medium text-neutral-700 mb-1">
            Subject
          </label>
          <input
            id="subject"
            type="text"
            v-model="form.subject"
            class="w-full rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
          />
          <p v-if="form.errors.subject" class="mt-1 text-xs text-red-600">
            {{ form.errors.subject }}
          </p>
        </div>

        <div>
          <label for="body" class="block text-sm font-medium text-neutral-700 mb-1">
            Question
          </label>
          <textarea
            id="body"
            rows="6"
            v-model="form.body"
            class="w-full rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
          ></textarea>
          <p v-if="form.errors.body" class="mt-1 text-xs text-red-600">
            {{ form.errors.body }}
          </p>
        </div>

        <div class="flex items-center justify-end gap-3">
          <Link :href="route('ask.index')" class="text-sm text-neutral-600 hover:text-neutral-900">
            Cancel
          </Link>
          <button
            type="submit"
            class="inline-flex items-center px-4 py-2 rounded-md bg-primary-900 text-white text-sm font-medium hover:bg-primary-800 disabled:opacity-50"
            :disabled="form.processing"
          >
            Submit Question
          </button>
        </div>
      </form>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({
  subject: '',
  body: '',
});

const submit = () => {
  form.post(route('ask.store'));
};
</script>


<template>
  <AppShell>
    <div class="max-w-3xl mx-auto">
      <div class="mb-8">
        <h1 class="font-serif text-3xl font-bold text-neutral-900 mb-2">{{ assessment.title }}</h1>
        <p class="text-sm text-neutral-600">{{ assessment.description }}</p>
        <p class="text-sm text-neutral-500 mt-2">Course: {{ course.title }}</p>
      </div>

      <form @submit.prevent="submit" class="bg-white border border-neutral-200 rounded-xl p-6 shadow-sm space-y-6">
        <div v-for="(question, index) in assessment.questions" :key="question.key" class="border-b border-neutral-100 pb-6 last:border-0">
          <h3 class="font-medium text-neutral-900 mb-4">{{ question.text }}</h3>
          
          <div class="space-y-3">
            <label class="flex items-center gap-3 cursor-pointer">
              <input
                type="radio"
                :name="`response_${question.key}`"
                :value="true"
                v-model="form.responses[index].already_practicing"
                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
              />
              <span class="text-sm text-neutral-700">Yes, I already practice this consistently</span>
            </label>
            
            <label class="flex items-center gap-3 cursor-pointer">
              <input
                type="radio"
                :name="`response_${question.key}`"
                :value="false"
                v-model="form.responses[index].already_practicing"
                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
              />
              <span class="text-sm text-neutral-700">No, I need to learn this</span>
            </label>
          </div>

          <div class="mt-4">
            <label class="block text-sm font-medium text-neutral-700 mb-1">Additional notes (optional)</label>
            <textarea
              v-model="form.responses[index].notes"
              rows="2"
              class="w-full rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
              placeholder="Any additional context..."
            ></textarea>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4">
          <Link :href="route('courses.show', course.id)" class="px-4 py-2 text-sm text-neutral-600 hover:text-neutral-900">
            Cancel
          </Link>
          <button
            type="submit"
            class="inline-flex items-center px-4 py-2 rounded-md bg-primary-900 text-white text-sm font-medium hover:bg-primary-800 disabled:opacity-50"
            :disabled="form.processing"
          >
            Submit Assessment
          </button>
        </div>
      </form>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { computed } from 'vue';

const props = defineProps({
  course: Object,
  assessment: Object,
  responses: Object,
});

const form = useForm({
  responses: computed(() => {
    return props.assessment.questions.map((q) => ({
      question_key: q.key,
      already_practicing: props.responses[q.key]?.already_practicing ?? false,
      notes: props.responses[q.key]?.notes ?? '',
    }));
  }).value,
});

const submit = () => {
  form.post(route('assessments.store', props.course.id));
};
</script>

<template>
  <AppShell>
    <div class="max-w-xl mx-auto py-16 text-center">
      <h1 class="text-3xl font-bold text-red-600 mb-4">Access restricted</h1>
      <p class="text-neutral-700 mb-6">
        This lesson is not available to your profile based on the current gating rules.
      </p>
      <p class="text-neutral-500 mb-8">
        Please speak to the course administrator if you believe this is a mistake.
      </p>
      <div class="space-x-3">
        <Link
          :href="route('courses.index')"
          class="inline-flex items-center px-4 py-2 rounded-md border border-neutral-300 text-sm font-medium text-neutral-700 bg-white hover:bg-neutral-50"
        >
          Back to courses
        </Link>
        <Link
          v-if="course"
          :href="route('courses.show', { course: course.id })"
          class="inline-flex items-center px-4 py-2 rounded-md border border-primary-600 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700"
        >
          View course
        </Link>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

defineProps({
  course: {
    type: Object,
    default: null,
  },
  lesson: {
    type: Object,
    default: null,
  },
});
</script>


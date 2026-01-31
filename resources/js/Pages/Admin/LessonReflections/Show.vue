<template>
  <AppShell>
    <div class="mb-6">
      <Link
        :href="route('admin.lesson-reflections.index')"
        class="inline-flex items-center text-sm text-neutral-600 hover:text-neutral-900 mb-4"
      >
        <ArrowLeft class="w-4 h-4 mr-2" />
        Back to Reflections
      </Link>
      <h1 class="text-2xl font-bold text-neutral-900">Reflection Review</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Content -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Student Info -->
        <div class="bg-white rounded-xl border border-neutral-200 p-6">
          <h2 class="text-lg font-semibold text-neutral-900 mb-4">Student Information</h2>
          <div class="space-y-2">
            <div>
              <span class="text-sm font-medium text-neutral-700">Name:</span>
              <span class="ml-2 text-sm text-neutral-900">{{ reflection.user.name }}</span>
            </div>
            <div>
              <span class="text-sm font-medium text-neutral-700">Email:</span>
              <span class="ml-2 text-sm text-neutral-900">{{ reflection.user.email }}</span>
            </div>
          </div>
        </div>

        <!-- Lesson Context -->
        <div class="bg-white rounded-xl border border-neutral-200 p-6">
          <h2 class="text-lg font-semibold text-neutral-900 mb-4">Lesson Context</h2>
          <div class="space-y-2">
            <div>
              <span class="text-sm font-medium text-neutral-700">Course:</span>
              <span class="ml-2 text-sm text-neutral-900">{{ reflection.lesson.module.course.title }}</span>
            </div>
            <div>
              <span class="text-sm font-medium text-neutral-700">Module:</span>
              <span class="ml-2 text-sm text-neutral-900">{{ reflection.lesson.module.title }}</span>
            </div>
            <div>
              <span class="text-sm font-medium text-neutral-700">Lesson:</span>
              <span class="ml-2 text-sm text-neutral-900">{{ reflection.lesson.title }}</span>
            </div>
          </div>
        </div>

        <!-- Reflection Takeaway -->
        <div class="bg-white rounded-xl border border-neutral-200 p-6">
          <h2 class="text-lg font-semibold text-neutral-900 mb-4">Spiritual Takeaway</h2>
          <div class="bg-neutral-50 rounded-lg p-4">
            <p class="text-sm text-neutral-800 whitespace-pre-line leading-relaxed">
              {{ reflection.takeaway }}
            </p>
          </div>
          <div class="mt-4 text-xs text-neutral-500">
            Submitted: {{ formatDate(reflection.submitted_at || reflection.created_at) }}
          </div>
        </div>

        <!-- Review Form -->
        <div class="bg-white rounded-xl border border-neutral-200 p-6">
          <h2 class="text-lg font-semibold text-neutral-900 mb-4">Review</h2>
          <form @submit.prevent="submitReview">
            <div class="space-y-4">
              <!-- Status -->
              <div>
                <label class="block text-sm font-medium text-neutral-700 mb-2">
                  Review Status <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="reviewForm.review_status"
                  class="w-full rounded-lg border-neutral-300 text-sm focus:border-primary-500 focus:ring-primary-500"
                >
                  <option value="pending">Pending</option>
                  <option value="reviewed">Reviewed</option>
                  <option value="needs_followup">Needs Follow-up</option>
                </select>
              </div>

              <!-- Teacher Note -->
              <div>
                <label class="block text-sm font-medium text-neutral-700 mb-2">
                  Teacher Note
                </label>
                <textarea
                  v-model="reviewForm.teacher_note"
                  rows="6"
                  class="w-full rounded-lg border-neutral-300 text-sm focus:border-primary-500 focus:ring-primary-500"
                  placeholder="Add feedback or notes for the student..."
                  maxlength="5000"
                ></textarea>
                <p class="mt-1 text-xs text-neutral-500">
                  {{ reviewForm.teacher_note?.length || 0 }} / 5,000 characters
                </p>
              </div>

              <!-- Submit Button -->
              <div class="flex justify-end gap-3">
                <Link
                  :href="route('admin.lesson-reflections.index')"
                  class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50"
                >
                  Cancel
                </Link>
                <button
                  type="submit"
                  :disabled="reviewForm.processing"
                  class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                >
                  {{ reviewForm.processing ? 'Saving...' : 'Save Review' }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Review Status -->
        <div class="bg-white rounded-xl border border-neutral-200 p-6">
          <h3 class="text-sm font-semibold text-neutral-700 mb-3">Current Status</h3>
          <div class="space-y-3">
            <div>
              <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border" :class="badgeClass(reflection.review_status)">
                {{ formatStatus(reflection.review_status) }}
              </span>
            </div>
            <div v-if="reflection.reviewed_by" class="text-sm">
              <span class="text-neutral-600">Reviewed by:</span>
              <span class="ml-1 font-medium text-neutral-900">{{ reflection.reviewer?.name || 'Unknown' }}</span>
            </div>
            <div v-if="reflection.reviewed_at" class="text-sm">
              <span class="text-neutral-600">Reviewed at:</span>
              <span class="ml-1 text-neutral-900">{{ formatDate(reflection.reviewed_at) }}</span>
            </div>
          </div>
        </div>

        <!-- Teacher Note Preview -->
        <div v-if="reflection.teacher_note" class="bg-white rounded-xl border border-neutral-200 p-6">
          <h3 class="text-sm font-semibold text-neutral-700 mb-3">Current Teacher Note</h3>
          <div class="bg-neutral-50 rounded-lg p-3">
            <p class="text-sm text-neutral-800 whitespace-pre-line">
              {{ reflection.teacher_note }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps({
  reflection: Object,
});

const reviewForm = useForm({
  review_status: props.reflection.review_status || 'pending',
  teacher_note: props.reflection.teacher_note || '',
});

const submitReview = () => {
  reviewForm.patch(route('admin.lesson-reflections.update', { reflection: props.reflection.id }), {
    preserveScroll: true,
    onSuccess: () => {
      // Page will reload with updated data
    },
  });
};

const formatStatus = (status) => {
  const statusMap = {
    pending: 'Pending',
    reviewed: 'Reviewed',
    needs_followup: 'Needs Follow-up',
  };
  return statusMap[status] || status;
};

const badgeClass = (status) => {
  const classes = {
    pending: 'border-amber-300 text-amber-700 bg-amber-50',
    reviewed: 'border-emerald-300 text-emerald-700 bg-emerald-50',
    needs_followup: 'border-red-300 text-red-700 bg-red-50',
  };
  return classes[status] || 'border-neutral-300 text-neutral-600 bg-neutral-50';
};

const formatDate = (date) => {
  if (!date) return '—';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};
</script>

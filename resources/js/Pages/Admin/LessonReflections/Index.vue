<template>
  <AppShell>
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-neutral-900">Lesson Reflections</h1>
        <p class="text-sm text-neutral-500">Review and respond to student reflections.</p>
      </div>
      <div class="flex gap-2">
        <button
          v-for="option in statusOptions"
          :key="option.value"
          @click="setStatus(option.value)"
          :class="[
            'px-3 py-1.5 rounded-full text-xs font-medium border',
            filter_status === option.value
              ? 'bg-primary-600 text-white border-primary-600'
              : 'bg-white text-neutral-600 border-neutral-200 hover:bg-neutral-50'
          ]"
        >
          {{ option.label }}
        </button>
      </div>
    </div>

    <div v-if="reflections.data.length === 0" class="bg-white rounded-xl border border-dashed border-neutral-200 p-8 text-center text-neutral-500">
      No reflections found for this filter.
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="reflection in reflections.data"
        :key="reflection.id"
        class="bg-white rounded-xl border border-neutral-200 p-5 shadow-sm flex flex-col gap-3"
      >
        <div class="flex items-center justify-between text-sm text-neutral-600">
          <div>
            <span class="font-semibold text-neutral-900">{{ reflection.user.name }}</span>
            <span class="mx-2 text-neutral-400">•</span>
            <span>{{ reflection.lesson.module.course.title }} – {{ reflection.lesson.title }}</span>
          </div>
          <span class="text-xs px-2 py-0.5 rounded-full border" :class="badgeClass(reflection.review_status)">
            {{ reflection.review_status }}
          </span>
        </div>

        <p class="text-sm text-neutral-800 whitespace-pre-line">
          {{ reflection.content }}
        </p>

        <div v-if="reflection.mentor_note" class="text-xs text-neutral-600 bg-neutral-50 rounded-lg p-3">
          <span class="font-semibold text-neutral-800">Mentor note: </span>{{ reflection.mentor_note }}
        </div>

        <form class="flex flex-col gap-2" @submit.prevent="update(reflection)">
          <textarea
            v-model="reflection.mentor_note"
            rows="2"
            class="w-full rounded-lg border-neutral-200 text-xs text-neutral-700 focus:border-primary-500 focus:ring-primary-500"
            placeholder="Add or update mentor note..."
          ></textarea>

          <div class="flex gap-2">
            <button
              type="button"
              class="px-3 py-1.5 rounded-lg text-xs font-medium border border-emerald-600 text-emerald-700 bg-emerald-50 hover:bg-emerald-100"
              @click="reflection.review_status = 'approved'; update(reflection)"
            >
              Approve
            </button>
            <button
              type="button"
              class="px-3 py-1.5 rounded-lg text-xs font-medium border border-amber-600 text-amber-700 bg-amber-50 hover:bg-amber-100"
              @click="reflection.review_status = 'needs_clarification'; update(reflection)"
            >
              Needs clarification
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  reflections: Object,
  filter_status: String,
});

const statusOptions = [
  { value: 'pending', label: 'Pending' },
  { value: 'approved', label: 'Approved' },
  { value: 'needs_clarification', label: 'Needs clarification' },
];

const setStatus = (value) => {
  router.get(route('admin.lesson-reflections.index', { status: value }), {}, { preserveScroll: true });
};

const update = (reflection) => {
  router.patch(route('admin.lesson-reflections.update', { reflection: reflection.id }), {
    review_status: reflection.review_status,
    mentor_note: reflection.mentor_note,
  }, {
    preserveScroll: true,
  });
};

const badgeClass = (status) => {
  if (status === 'approved') return 'border-emerald-600 text-emerald-700 bg-emerald-50';
  if (status === 'needs_clarification') return 'border-amber-600 text-amber-700 bg-amber-50';
  return 'border-neutral-300 text-neutral-600 bg-neutral-50';
};
</script>


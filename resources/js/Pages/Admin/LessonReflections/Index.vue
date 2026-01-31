<template>
  <AppShell>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-neutral-900 mb-2">Reflection Review Portal</h1>
      <p class="text-sm text-neutral-500">Review student reflections and provide feedback.</p>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-xl border border-neutral-200 p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Status Filter -->
        <div>
          <label class="block text-xs font-medium text-neutral-700 mb-1">Status</label>
          <select
            v-model="filters.status"
            @change="applyFilters"
            class="w-full rounded-lg border-neutral-300 text-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option value="pending">Pending</option>
            <option value="reviewed">Reviewed</option>
            <option value="needs_followup">Needs Follow-up</option>
          </select>
        </div>

        <!-- Course Filter -->
        <div>
          <label class="block text-xs font-medium text-neutral-700 mb-1">Course</label>
          <select
            v-model="filters.course_id"
            @change="onCourseChange"
            class="w-full rounded-lg border-neutral-300 text-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option :value="null">All Courses</option>
            <option v-for="course in courses" :key="course.id" :value="course.id">
              {{ course.title }}
            </option>
          </select>
        </div>

        <!-- Module Filter -->
        <div>
          <label class="block text-xs font-medium text-neutral-700 mb-1">Module</label>
          <select
            v-model="filters.module_id"
            @change="onModuleChange"
            :disabled="!filters.course_id"
            class="w-full rounded-lg border-neutral-300 text-sm focus:border-primary-500 focus:ring-primary-500 disabled:bg-neutral-100"
          >
            <option :value="null">All Modules</option>
            <option v-for="module in modules" :key="module.id" :value="module.id">
              {{ module.title }}
            </option>
          </select>
        </div>

        <!-- Lesson Filter -->
        <div>
          <label class="block text-xs font-medium text-neutral-700 mb-1">Lesson</label>
          <select
            v-model="filters.lesson_id"
            @change="applyFilters"
            :disabled="!filters.module_id"
            class="w-full rounded-lg border-neutral-300 text-sm focus:border-primary-500 focus:ring-primary-500 disabled:bg-neutral-100"
          >
            <option :value="null">All Lessons</option>
            <option v-for="lesson in lessons" :key="lesson.id" :value="lesson.id">
              {{ lesson.title }}
            </option>
          </select>
        </div>

        <!-- Search -->
        <div>
          <label class="block text-xs font-medium text-neutral-700 mb-1">Search Student</label>
          <input
            v-model="searchQuery"
            @input="debouncedSearch"
            type="text"
            placeholder="Name or email..."
            class="w-full rounded-lg border-neutral-300 text-sm focus:border-primary-500 focus:ring-primary-500"
          />
        </div>
      </div>

      <div class="mt-4 flex justify-end">
        <button
          @click="resetFilters"
          class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50"
        >
          Reset Filters
        </button>
      </div>
    </div>

    <!-- Status Tabs (Quick View) -->
    <div class="flex gap-2 mb-4">
      <button
        v-for="tab in statusTabs"
        :key="tab.value"
        @click="setStatusTab(tab.value)"
        :class="[
          'px-4 py-2 rounded-lg text-sm font-medium border transition-colors',
          filters.status === tab.value
            ? 'bg-primary-600 text-white border-primary-600'
            : 'bg-white text-neutral-600 border-neutral-200 hover:bg-neutral-50'
        ]"
      >
        {{ tab.label }}
        <span v-if="tab.count !== undefined" class="ml-2 px-2 py-0.5 bg-white/20 rounded-full text-xs">
          {{ tab.count }}
        </span>
      </button>
    </div>

    <!-- Table -->
    <div v-if="reflections.data.length === 0" class="bg-white rounded-xl border border-dashed border-neutral-200 p-8 text-center text-neutral-500">
      No reflections found for this filter.
    </div>

    <div v-else class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
      <table class="w-full">
        <thead class="bg-neutral-50 border-b border-neutral-200">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-700">Student</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-700">Course > Module > Lesson</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-700">Status</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-700">Submitted</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-neutral-700">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-neutral-200">
          <tr v-for="reflection in reflections.data" :key="reflection.id" class="hover:bg-neutral-50">
            <td class="px-4 py-3">
              <div class="text-sm font-medium text-neutral-900">{{ reflection.user.name }}</div>
              <div class="text-xs text-neutral-500">{{ reflection.user.email }}</div>
            </td>
            <td class="px-4 py-3">
              <div class="text-sm text-neutral-700">
                {{ reflection.lesson.module.course.title }} > {{ reflection.lesson.module.title }} > {{ reflection.lesson.title }}
              </div>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium border" :class="badgeClass(reflection.review_status)">
                {{ formatStatus(reflection.review_status) }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-neutral-600">
              {{ formatDate(reflection.submitted_at || reflection.created_at) }}
            </td>
            <td class="px-4 py-3 text-right">
              <Link
                :href="route('admin.lesson-reflections.show', { reflection: reflection.id })"
                class="text-primary-600 hover:text-primary-700 text-sm font-medium"
              >
                View
              </Link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="reflections.links && reflections.links.length > 3" class="mt-6 flex justify-center">
      <div class="flex gap-2">
        <Link
          v-for="link in reflections.links"
          :key="link.label"
          :href="link.url || '#'"
          :class="[
            'px-4 py-2 rounded-lg text-sm font-medium border transition-colors',
            link.active
              ? 'bg-primary-600 text-white border-primary-600'
              : 'bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-50',
            !link.url ? 'opacity-50 cursor-not-allowed' : ''
          ]"
          v-html="link.label"
        />
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps({
  reflections: Object,
  filters: Object,
  courses: Array,
  modules: Array,
  lessons: Array,
});

const searchQuery = ref(props.filters.q || '');
let searchTimeout = null;

const statusTabs = [
  { value: 'pending', label: 'Pending' },
  { value: 'reviewed', label: 'Reviewed' },
  { value: 'needs_followup', label: 'Needs Follow-up' },
];

const filters = ref({
  status: props.filters.status || 'pending',
  course_id: props.filters.course_id || null,
  module_id: props.filters.module_id || null,
  lesson_id: props.filters.lesson_id || null,
  q: props.filters.q || '',
});

const applyFilters = () => {
  router.get(route('admin.lesson-reflections.index'), filters.value, {
    preserveState: true,
    preserveScroll: true,
  });
};

const onCourseChange = () => {
  // Reset module and lesson when course changes
  filters.value.module_id = null;
  filters.value.lesson_id = null;
  applyFilters();
};

const onModuleChange = () => {
  // Reset lesson when module changes
  filters.value.lesson_id = null;
  applyFilters();
};

const setStatusTab = (status) => {
  filters.value.status = status;
  applyFilters();
};

const resetFilters = () => {
  filters.value = {
    status: 'pending',
    course_id: null,
    module_id: null,
    lesson_id: null,
    q: '',
  };
  searchQuery.value = '';
  applyFilters();
};

const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    filters.value.q = searchQuery.value;
    applyFilters();
  }, 300);
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
    month: 'short',
    day: 'numeric',
  });
};
</script>

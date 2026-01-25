<template>
  <AppShell>
    <Head title="Admin - Courses" />
    
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-serif font-bold text-primary-900">Manage Courses</h1>
          <p class="text-neutral-600 mt-1">Create and manage your course catalog</p>
        </div>
        <Link 
          href="/admin/courses/create"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-900 text-white rounded-lg font-medium hover:bg-primary-800 transition-colors"
        >
          <Plus class="w-5 h-5" />
          Create Course
        </Link>
      </div>

      <!-- Search -->
      <div class="relative max-w-md">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-neutral-400" />
        <input 
          v-model="search"
          type="text" 
          placeholder="Search courses..." 
          class="w-full pl-10 pr-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
        />
      </div>

      <!-- Courses Table -->
      <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <table class="w-full">
          <thead class="bg-neutral-50 border-b border-neutral-200">
            <tr>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Course</th>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Modules</th>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Lessons</th>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Enrollments</th>
              <th class="text-right px-6 py-4 text-sm font-semibold text-neutral-700">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-neutral-100">
            <tr v-for="course in filteredCourses" :key="course.id" class="hover:bg-neutral-50 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-primary-800 to-primary-950 flex items-center justify-center text-white font-serif font-bold text-lg">
                    {{ course.title?.charAt(0) }}
                  </div>
                  <div>
                    <div class="font-medium text-neutral-900">{{ course.title }}</div>
                    <div class="text-sm text-neutral-500">{{ course.slug }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm bg-neutral-100 text-neutral-700">
                  {{ course.modules_count || 0 }} modules
                </span>
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm bg-neutral-100 text-neutral-700">
                  {{ course.lessons_count || 0 }} lessons
                </span>
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm bg-primary-50 text-primary-700">
                  {{ course.enrollments_count || 0 }} students
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-2">
                  <Link 
                    :href="`/admin/courses/${course.id}/edit`"
                    class="p-2 text-neutral-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                  >
                    <Pencil class="w-4 h-4" />
                  </Link>
                  <button 
                    @click="deleteCourse(course)"
                    class="p-2 text-neutral-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="filteredCourses.length === 0">
              <td colspan="5" class="px-6 py-12 text-center text-neutral-500">
                <BookOpen class="w-12 h-12 mx-auto mb-3 text-neutral-300" />
                <p>No courses found</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Plus, Search, Pencil, Trash2, BookOpen } from 'lucide-vue-next';

const props = defineProps({
  courses: Array,
});

const search = ref('');

const filteredCourses = computed(() => {
  if (!search.value) return props.courses;
  return props.courses.filter(course => 
    course.title.toLowerCase().includes(search.value.toLowerCase())
  );
});

const deleteCourse = (course) => {
  if (confirm(`Are you sure you want to delete "${course.title}"?`)) {
    router.delete(`/admin/courses/${course.id}`);
  }
};
</script>

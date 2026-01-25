<template>
  <AppShell>
    <Head title="Admin - Lessons" />
    
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-serif font-bold text-primary-900">Manage Lessons</h1>
          <p class="text-neutral-600 mt-1">Create and manage lesson content</p>
        </div>
        <Link 
          href="/admin/lessons/create"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-900 text-white rounded-lg font-medium hover:bg-primary-800 transition-colors"
        >
          <Plus class="w-5 h-5" />
          Create Lesson
        </Link>
      </div>

      <!-- Search & Filters -->
      <div class="flex flex-col sm:flex-row gap-4">
        <div class="relative flex-1 max-w-md">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-neutral-400" />
          <input 
            v-model="search"
            type="text" 
            placeholder="Search lessons..." 
            class="w-full pl-10 pr-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
          />
        </div>
      </div>

      <!-- Lessons Table -->
      <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <table class="w-full">
          <thead class="bg-neutral-50 border-b border-neutral-200">
            <tr>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Lesson</th>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Module / Course</th>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Video</th>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Status</th>
              <th class="text-right px-6 py-4 text-sm font-semibold text-neutral-700">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-neutral-100">
            <tr v-for="lesson in filteredLessons" :key="lesson.id" class="hover:bg-neutral-50 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary-800 to-primary-950 flex items-center justify-center">
                    <Video class="w-5 h-5 text-white" />
                  </div>
                  <div>
                    <div class="font-medium text-neutral-900">{{ lesson.title }}</div>
                    <div class="text-sm text-neutral-500">{{ lesson.slug }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm">
                  <div class="text-neutral-900">{{ lesson.module?.title }}</div>
                  <div class="text-neutral-500">{{ lesson.module?.course?.title }}</div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="[
                  'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
                  lesson.video_provider === 'youtube' ? 'bg-red-50 text-red-700' : 
                  lesson.video_provider === 'external' ? 'bg-blue-50 text-blue-700' : 'bg-neutral-100 text-neutral-700'
                ]">
                  <Youtube v-if="lesson.video_provider === 'youtube'" class="w-3 h-3" />
                  <ExternalLink v-else-if="lesson.video_provider === 'external'" class="w-3 h-3" />
                  <Film v-else class="w-3 h-3" />
                  {{ lesson.video_provider }}
                </span>
              </td>
              <td class="px-6 py-4">
                <span v-if="lesson.is_free_preview" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700">
                  Free Preview
                </span>
                <span v-else class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-neutral-100 text-neutral-600">
                  Enrolled Only
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-2">
                  <Link 
                    :href="`/admin/lessons/${lesson.id}/edit`"
                    class="p-2 text-neutral-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                  >
                    <Pencil class="w-4 h-4" />
                  </Link>
                  <button 
                    @click="deleteLesson(lesson)"
                    class="p-2 text-neutral-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="filteredLessons.length === 0">
              <td colspan="5" class="px-6 py-12 text-center text-neutral-500">
                <Video class="w-12 h-12 mx-auto mb-3 text-neutral-300" />
                <p>No lessons found</p>
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
import { Plus, Search, Pencil, Trash2, Video, Youtube, ExternalLink, Film } from 'lucide-vue-next';

const props = defineProps({
  lessons: Array,
});

const search = ref('');

const filteredLessons = computed(() => {
  if (!search.value) return props.lessons;
  return props.lessons.filter(lesson => 
    lesson.title.toLowerCase().includes(search.value.toLowerCase())
  );
});

const deleteLesson = (lesson) => {
  if (confirm(`Are you sure you want to delete "${lesson.title}"?`)) {
    router.delete(`/admin/lessons/${lesson.id}`);
  }
};
</script>

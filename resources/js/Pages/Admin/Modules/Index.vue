<template>
  <AppShell>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="font-serif text-3xl font-bold text-neutral-900">Module Management</h1>
          <p class="text-neutral-600 mt-1">Organize lessons into modules within courses</p>
        </div>
        <Link href="/admin/modules/create" class="px-4 py-2 bg-primary-900 text-white rounded-lg text-sm font-medium hover:bg-primary-800 transition-colors flex items-center gap-2">
          <Plus class="w-4 h-4" />
          Create Module
        </Link>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl border border-neutral-200 p-4">
        <div class="flex flex-col md:flex-row gap-4">
          <div class="flex-1">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search modules..."
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              @input="debouncedSearch"
            />
          </div>
          <select
            v-model="courseFilter"
            class="px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
            @change="applyFilters"
          >
            <option value="">All Courses</option>
            <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.title }}</option>
          </select>
        </div>
      </div>

      <!-- Modules Table -->
      <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-neutral-50 border-b border-neutral-200">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">Module</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">Course</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">Lessons</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">Order</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-neutral-600 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
              <tr v-for="module in modules.data" :key="module.id" class="hover:bg-neutral-50">
                <td class="px-6 py-4">
                  <div class="font-medium text-neutral-900">{{ module.title }}</div>
                  <div class="text-sm text-neutral-400">{{ module.slug }}</div>
                </td>
                <td class="px-6 py-4">
                  <Link :href="`/admin/courses/${module.course.id}/edit`" class="text-primary-600 hover:text-primary-700">
                    {{ module.course.title }}
                  </Link>
                </td>
                <td class="px-6 py-4 text-neutral-600">{{ module.lessons_count }} lessons</td>
                <td class="px-6 py-4 text-neutral-600">#{{ module.sort_order }}</td>
                <td class="px-6 py-4">
                  <div class="flex items-center justify-end gap-2">
                    <Link :href="`/admin/lessons/create?module_id=${module.id}`" class="p-2 text-neutral-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="Add Lesson">
                      <Plus class="w-4 h-4" />
                    </Link>
                    <Link :href="`/admin/modules/${module.id}/edit`" class="p-2 text-neutral-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors">
                      <Pencil class="w-4 h-4" />
                    </Link>
                    <button @click="confirmDelete(module)" class="p-2 text-neutral-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!modules.data.length">
                <td colspan="5" class="px-6 py-12 text-center text-neutral-400">
                  No modules found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="modules.last_page > 1" class="px-6 py-4 border-t border-neutral-100 flex items-center justify-between">
          <div class="text-sm text-neutral-500">
            Showing {{ modules.from }} to {{ modules.to }} of {{ modules.total }} modules
          </div>
          <div class="flex gap-2">
            <Link
              v-for="link in modules.links"
              :key="link.label"
              :href="link.url"
              :class="[
                'px-3 py-1 rounded text-sm',
                link.active ? 'bg-primary-600 text-white' : 'text-neutral-600 hover:bg-neutral-100',
                !link.url ? 'opacity-50 cursor-not-allowed' : ''
              ]"
              v-html="link.label"
            />
          </div>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Plus, Pencil, Trash2 } from 'lucide-vue-next';

const props = defineProps({
  modules: Object,
  courses: Array,
  filters: Object,
});

const searchQuery = ref(props.filters.search || '');
const courseFilter = ref(props.filters.course_id || '');

let searchTimeout = null;

const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 300);
};

const applyFilters = () => {
  router.get('/admin/modules', {
    search: searchQuery.value || undefined,
    course_id: courseFilter.value || undefined,
  }, { preserveState: true });
};

const confirmDelete = (module) => {
  if (module.lessons_count > 0) {
    alert('Cannot delete module with existing lessons. Delete lessons first.');
    return;
  }
  if (confirm(`Are you sure you want to delete "${module.title}"?`)) {
    router.delete(`/admin/modules/${module.id}`);
  }
};
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="font-serif text-3xl font-bold text-neutral-900">Habit Management</h1>
          <p class="text-neutral-600 mt-1">View and manage habits for all users</p>
        </div>
        <Link href="/admin/habits/create" class="px-4 py-2 bg-primary-900 text-white rounded-lg text-sm font-medium hover:bg-primary-800 transition-colors flex items-center gap-2">
          <Plus class="w-4 h-4" />
          Create Habit
        </Link>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl border border-neutral-200 p-4">
        <div class="flex flex-col md:flex-row gap-4">
          <div class="flex-1">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search habits or users..."
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              @input="debouncedSearch"
            />
          </div>
          <select
            v-model="userFilter"
            class="px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
            @change="applyFilters"
          >
            <option value="">All Users</option>
            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
          </select>
        </div>
      </div>

      <!-- Habits Table -->
      <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-neutral-50 border-b border-neutral-200">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">Habit</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">User</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">Frequency</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">Streak</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">Logs</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">Created</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-neutral-600 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
              <tr v-for="habit in habits.data" :key="habit.id" class="hover:bg-neutral-50">
                <td class="px-6 py-4">
                  <div class="font-medium text-neutral-900">{{ habit.title }}</div>
                  <div v-if="habit.description" class="text-sm text-neutral-500 mt-1">{{ habit.description }}</div>
                </td>
                <td class="px-6 py-4">
                  <Link :href="`/admin/users/${habit.user.id}`" class="text-primary-600 hover:text-primary-700">
                    {{ habit.user.name }}
                  </Link>
                </td>
                <td class="px-6 py-4">
                  <span :class="[
                    'px-2.5 py-1 rounded-full text-xs font-semibold',
                    habit.frequency_type === 'daily' ? 'bg-blue-100 text-blue-700' :
                    habit.frequency_type === 'weekly' ? 'bg-purple-100 text-purple-700' :
                    'bg-gray-100 text-gray-700'
                  ]">
                    {{ habit.frequency_type }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <Flame class="w-4 h-4 text-orange-500" />
                    <span class="text-neutral-900">-</span>
                    <span class="text-neutral-400 text-sm">(best: -)</span>
                  </div>
                </td>
                <td class="px-6 py-4 text-neutral-600">{{ habit.logs_count }}</td>
                <td class="px-6 py-4 text-neutral-500 text-sm">{{ habit.created_at }}</td>
                <td class="px-6 py-4">
                  <div class="flex items-center justify-end gap-2">
                    <Link :href="`/admin/habits/${habit.id}/edit`" class="p-2 text-neutral-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors">
                      <Pencil class="w-4 h-4" />
                    </Link>
                    <button @click="confirmDelete(habit)" class="p-2 text-neutral-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!habits.data.length">
                <td colspan="7" class="px-6 py-12 text-center text-neutral-400">
                  No habits found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="habits.last_page > 1" class="px-6 py-4 border-t border-neutral-100 flex items-center justify-between">
          <div class="text-sm text-neutral-500">
            Showing {{ habits.from }} to {{ habits.to }} of {{ habits.total }} habits
          </div>
          <div class="flex gap-2">
            <Link
              v-for="link in habits.links"
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
import { Plus, Pencil, Trash2, Flame } from 'lucide-vue-next';

const props = defineProps({
  habits: Object,
  users: Array,
  filters: Object,
});

const searchQuery = ref(props.filters.search || '');
const userFilter = ref(props.filters.user_id || '');

let searchTimeout = null;

const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 300);
};

const applyFilters = () => {
  router.get('/admin/habits', {
    search: searchQuery.value || undefined,
    user_id: userFilter.value || undefined,
  }, { preserveState: true });
};

const confirmDelete = (habit) => {
  if (confirm(`Are you sure you want to delete "${habit.title}"? This action cannot be undone.`)) {
    router.delete(`/admin/habits/${habit.id}`);
  }
};
</script>

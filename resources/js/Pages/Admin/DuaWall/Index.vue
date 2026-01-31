<template>
  <AppShell>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-neutral-900 mb-2">Dua Wall Moderation</h1>
      <p class="text-sm text-neutral-500">Moderate dua requests posted by students.</p>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-xl border border-neutral-200 p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Status Filter -->
        <div>
          <label class="block text-xs font-medium text-neutral-700 mb-1">Status</label>
          <select
            v-model="filters.status"
            @change="applyFilters"
            class="w-full rounded-lg border-neutral-300 text-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option :value="null">All Statuses</option>
            <option value="active">Active</option>
            <option value="hidden">Hidden</option>
          </select>
        </div>

        <!-- Deleted Filter -->
        <div>
          <label class="block text-xs font-medium text-neutral-700 mb-1">Deleted</label>
          <select
            v-model="filters.deleted"
            @change="applyFilters"
            class="w-full rounded-lg border-neutral-300 text-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option :value="null">All</option>
            <option value="yes">Yes</option>
            <option value="no">No</option>
          </select>
        </div>

        <!-- Search -->
        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-neutral-700 mb-1">Search Content</label>
          <input
            v-model="searchQuery"
            @input="debouncedSearch"
            type="text"
            placeholder="Search dua requests..."
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

    <!-- Table -->
    <div v-if="requests.data.length === 0" class="bg-white rounded-xl border border-dashed border-neutral-200 p-8 text-center text-neutral-500">
      No dua requests found for this filter.
    </div>

    <div v-else class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
      <table class="w-full">
        <thead class="bg-neutral-50 border-b border-neutral-200">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-700">Content</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-700">Author</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-700">Status</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-700">Deleted</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-700">Prayers</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-700">Created</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-neutral-700">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-neutral-200">
          <tr v-for="request in requests.data" :key="request.id" class="hover:bg-neutral-50">
            <td class="px-4 py-3">
              <div class="text-sm text-neutral-800 max-w-md">
                {{ request.content_snippet }}
                <span v-if="request.content.length > 100" class="text-neutral-400">...</span>
              </div>
              <div v-if="request.is_anonymous" class="text-xs text-neutral-500 mt-1">Anonymous</div>
            </td>
            <td class="px-4 py-3">
              <div v-if="request.author" class="text-sm font-medium text-neutral-900">{{ request.author.name }}</div>
              <div v-if="request.author" class="text-xs text-neutral-500">{{ request.author.email }}</div>
              <div v-else class="text-xs text-neutral-400">Anonymous</div>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium border" :class="statusBadgeClass(request.status)">
                {{ request.status === 'active' ? 'Active' : 'Hidden' }}
              </span>
              <div v-if="request.hidden_by" class="text-xs text-neutral-500 mt-1">
                Hidden by {{ request.hidden_by.name }}
              </div>
            </td>
            <td class="px-4 py-3">
              <span v-if="request.deleted_at" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium border border-red-300 text-red-700 bg-red-50">
                Yes
              </span>
              <span v-else class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium border border-neutral-300 text-neutral-600 bg-neutral-50">
                No
              </span>
            </td>
            <td class="px-4 py-3">
              <span class="text-sm font-semibold text-emerald-700">{{ request.prayers_count }}</span>
            </td>
            <td class="px-4 py-3 text-sm text-neutral-600">
              {{ request.created_at }}
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-2">
                <button
                  v-if="request.status === 'active' && !request.deleted_at"
                  @click="hideRequest(request.id)"
                  class="px-3 py-1 text-xs font-medium text-amber-700 bg-amber-50 border border-amber-300 rounded-lg hover:bg-amber-100"
                >
                  Hide
                </button>
                <button
                  v-if="request.status === 'hidden' && !request.deleted_at"
                  @click="unhideRequest(request.id)"
                  class="px-3 py-1 text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-300 rounded-lg hover:bg-emerald-100"
                >
                  Unhide
                </button>
                <button
                  v-if="!request.deleted_at"
                  @click="deleteRequest(request.id)"
                  class="px-3 py-1 text-xs font-medium text-red-700 bg-red-50 border border-red-300 rounded-lg hover:bg-red-100"
                >
                  Delete
                </button>
                <button
                  v-if="request.deleted_at"
                  @click="restoreRequest(request.id)"
                  class="px-3 py-1 text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-300 rounded-lg hover:bg-emerald-100"
                >
                  Restore
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="requests.links && requests.links.length > 3" class="mt-6 flex justify-center">
      <div class="flex gap-2">
        <Link
          v-for="link in requests.links"
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
import { ref } from 'vue';
import { route } from 'ziggy-js';
import axios from 'axios';

const props = defineProps({
  requests: Object,
  filters: Object,
});

const searchQuery = ref(props.filters.search || '');
let searchTimeout = null;

const filters = ref({
  status: props.filters.status || null,
  deleted: props.filters.deleted || null,
  search: props.filters.search || '',
});

const applyFilters = () => {
  router.get(route('admin.dua-wall.index'), filters.value, {
    preserveState: true,
    preserveScroll: true,
  });
};

const resetFilters = () => {
  filters.value = {
    status: null,
    deleted: null,
    search: '',
  };
  searchQuery.value = '';
  applyFilters();
};

const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    filters.value.search = searchQuery.value;
    applyFilters();
  }, 300);
};

const statusBadgeClass = (status) => {
  if (status === 'active') {
    return 'border-emerald-300 text-emerald-700 bg-emerald-50';
  }
  return 'border-amber-300 text-amber-700 bg-amber-50';
};

const hideRequest = async (id) => {
  if (!confirm('Are you sure you want to hide this dua request?')) return;

  try {
    await axios.patch(route('admin.dua-wall.hide', { dua: id }));
    router.reload({ only: ['requests'] });
  } catch (error) {
    alert('Failed to hide request.');
  }
};

const unhideRequest = async (id) => {
  if (!confirm('Are you sure you want to unhide this dua request?')) return;

  try {
    await axios.patch(route('admin.dua-wall.unhide', { dua: id }));
    router.reload({ only: ['requests'] });
  } catch (error) {
    alert('Failed to unhide request.');
  }
};

const deleteRequest = async (id) => {
  if (!confirm('Are you sure you want to delete this dua request? This action can be undone.')) return;

  try {
    await axios.delete(route('admin.dua-wall.destroy', { dua: id }));
    router.reload({ only: ['requests'] });
  } catch (error) {
    alert('Failed to delete request.');
  }
};

const restoreRequest = async (id) => {
  if (!confirm('Are you sure you want to restore this dua request?')) return;

  try {
    await axios.patch(route('admin.dua-wall.restore', { id }));
    router.reload({ only: ['requests'] });
  } catch (error) {
    alert('Failed to restore request.');
  }
};
</script>

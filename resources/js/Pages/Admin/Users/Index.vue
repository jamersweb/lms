<template>
  <AppShell>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="font-serif text-3xl font-bold text-neutral-900">User Management</h1>
          <p class="text-neutral-600 mt-1">View and manage all users</p>
        </div>
        <Link href="/admin/users/create" class="px-4 py-2 bg-primary-900 text-white rounded-lg text-sm font-medium hover:bg-primary-800 transition-colors flex items-center gap-2">
          <UserPlus class="w-4 h-4" />
          Add User
        </Link>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl border border-neutral-200 p-4">
        <div class="flex flex-col md:flex-row gap-4">
          <div class="flex-1">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search users by name or email..."
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              @input="debouncedSearch"
            />
          </div>
          <select
            v-model="roleFilter"
            class="px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
            @change="applyFilters"
          >
            <option value="">All Roles</option>
            <option value="admin">Admins Only</option>
            <option value="user">Users Only</option>
          </select>
        </div>
      </div>

      <!-- Users Table -->
      <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-neutral-50 border-b border-neutral-200">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">User</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">Role</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">Enrollments</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">Habits</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">Joined</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-neutral-600 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
              <tr v-for="user in users.data" :key="user.id" class="hover:bg-neutral-50">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center text-sm font-bold text-primary-900">
                      {{ getInitials(user.name) }}
                    </div>
                    <div>
                      <div class="font-medium text-neutral-900">{{ user.name }}</div>
                      <div class="text-sm text-neutral-500">{{ user.email }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span :class="[
                    'px-2.5 py-1 rounded-full text-xs font-semibold',
                    user.is_admin ? 'bg-primary-100 text-primary-700' : 'bg-neutral-100 text-neutral-600'
                  ]">
                    {{ user.is_admin ? 'Admin' : 'User' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-neutral-600">{{ user.enrollments_count }}</td>
                <td class="px-6 py-4 text-neutral-600">{{ user.habits_count }}</td>
                <td class="px-6 py-4 text-neutral-500 text-sm">{{ user.created_at }}</td>
                <td class="px-6 py-4">
                  <div class="flex items-center justify-end gap-2">
                    <Link :href="`/admin/users/${user.id}`" class="p-2 text-neutral-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors">
                      <Eye class="w-4 h-4" />
                    </Link>
                    <Link :href="`/admin/users/${user.id}/edit`" class="p-2 text-neutral-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors">
                      <Pencil class="w-4 h-4" />
                    </Link>
                    <button @click="toggleAdmin(user)" class="p-2 text-neutral-400 hover:text-secondary-600 hover:bg-secondary-50 rounded-lg transition-colors" :title="user.is_admin ? 'Remove Admin' : 'Make Admin'">
                      <Shield class="w-4 h-4" />
                    </button>
                    <button @click="confirmDelete(user)" class="p-2 text-neutral-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!users.data.length">
                <td colspan="6" class="px-6 py-12 text-center text-neutral-400">
                  No users found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="users.last_page > 1" class="px-6 py-4 border-t border-neutral-100 flex items-center justify-between">
          <div class="text-sm text-neutral-500">
            Showing {{ users.from }} to {{ users.to }} of {{ users.total }} users
          </div>
          <div class="flex gap-2">
            <Link
              v-for="link in users.links"
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
import { UserPlus, Eye, Pencil, Trash2, Shield } from 'lucide-vue-next';

const props = defineProps({
  users: Object,
  filters: Object,
});

const searchQuery = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || '');

let searchTimeout = null;

const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 300);
};

const applyFilters = () => {
  router.get('/admin/users', {
    search: searchQuery.value || undefined,
    role: roleFilter.value || undefined,
  }, { preserveState: true });
};

const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const toggleAdmin = (user) => {
  if (confirm(`${user.is_admin ? 'Remove admin privileges from' : 'Grant admin privileges to'} ${user.name}?`)) {
    router.post(`/admin/users/${user.id}/toggle-admin`);
  }
};

const confirmDelete = (user) => {
  if (confirm(`Are you sure you want to delete ${user.name}? This action cannot be undone.`)) {
    router.delete(`/admin/users/${user.id}`);
  }
};
</script>

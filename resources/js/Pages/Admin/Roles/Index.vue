<template>
  <AppShell>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="font-serif text-3xl font-bold text-neutral-900">Roles</h1>
          <p class="text-neutral-600 mt-1">Manage roles and permissions</p>
        </div>
        <Link
          href="/admin/roles/create"
          class="px-4 py-2 bg-primary-900 text-white rounded-lg text-sm font-medium hover:bg-primary-800 transition-colors flex items-center gap-2"
        >
          <Plus class="w-4 h-4" />
          Add Role
        </Link>
      </div>

      <div v-if="$page.props.flash?.success" class="p-4 bg-green-50 text-green-800 rounded-lg">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash?.error" class="p-4 bg-red-50 text-red-800 rounded-lg">
        {{ $page.props.flash.error }}
      </div>

      <div class="bg-white rounded-xl border border-neutral-200 p-4">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search roles..."
          class="w-full max-w-md px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
          @input="debouncedSearch"
        />
      </div>

      <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-neutral-50 border-b border-neutral-200">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">Role</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">Slug</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wider">Users</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-neutral-600 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
              <tr v-for="role in roles.data" :key="role.id" class="hover:bg-neutral-50">
                <td class="px-6 py-4">
                  <div class="font-medium text-neutral-900">{{ role.name }}</div>
                  <div v-if="role.description" class="text-sm text-neutral-500">{{ role.description }}</div>
                </td>
                <td class="px-6 py-4">
                  <code class="px-2 py-1 bg-neutral-100 rounded text-sm">{{ role.slug }}</code>
                </td>
                <td class="px-6 py-4 text-neutral-600">{{ role.users_count }}</td>
                <td class="px-6 py-4">
                  <div class="flex items-center justify-end gap-2">
                    <Link
                      :href="`/admin/roles/${role.id}/edit`"
                      class="p-2 text-neutral-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                    >
                      <Pencil class="w-4 h-4" />
                    </Link>
                    <button
                      v-if="role.users_count === 0"
                      @click="confirmDelete(role)"
                      class="p-2 text-neutral-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!roles.data.length">
                <td colspan="4" class="px-6 py-12 text-center text-neutral-400">No roles found</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="roles.last_page > 1" class="px-6 py-4 border-t border-neutral-100 flex items-center justify-between">
          <div class="text-sm text-neutral-500">
            Showing {{ roles.from }} to {{ roles.to }} of {{ roles.total }} roles
          </div>
          <div class="flex gap-2">
            <Link
              v-for="link in roles.links"
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
  roles: Object,
  filters: Object,
});

const searchQuery = ref(props.filters?.search || '');
let searchTimeout = null;

const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get('/admin/roles', { search: searchQuery.value || undefined }, { preserveState: true });
  }, 300);
};

const confirmDelete = (role) => {
  if (confirm(`Delete role "${role.name}"? This cannot be undone.`)) {
    router.delete(`/admin/roles/${role.id}`);
  }
};
</script>

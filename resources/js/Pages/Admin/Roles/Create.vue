<template>
  <AppShell>
    <div class="max-w-3xl mx-auto space-y-6">
      <Link href="/admin/roles" class="inline-flex items-center gap-2 text-neutral-600 hover:text-neutral-900">
        <ArrowLeft class="w-4 h-4" />
        Back to Roles
      </Link>

      <div class="bg-white rounded-xl border border-neutral-200 p-6">
        <h1 class="font-serif text-2xl font-bold text-neutral-900 mb-6">Create Role</h1>

        <form @submit.prevent="submit" class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Name</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              placeholder="e.g. Mentor"
              required
            />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Slug</label>
            <input
              v-model="form.slug"
              type="text"
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              placeholder="e.g. mentor"
              required
            />
            <p v-if="form.errors.slug" class="mt-1 text-sm text-red-600">{{ form.errors.slug }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Description</label>
            <input
              v-model="form.description"
              type="text"
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              placeholder="Optional description"
            />
          </div>

          <div class="border-t border-neutral-200 pt-6">
            <h3 class="text-sm font-semibold text-neutral-700 mb-4">Permissions</h3>
            <p class="text-sm text-neutral-500 mb-4">Select which permissions this role should have. Permissions are grouped by module.</p>

            <div class="space-y-6">
              <div
                v-for="module in modules"
                :key="module.id"
                class="border border-neutral-200 rounded-lg p-4"
              >
                <div class="font-medium text-neutral-800 mb-3">{{ module.name }}</div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2">
                  <label
                    v-for="perm in module.permissions"
                    :key="perm.id"
                    class="flex items-center gap-2 cursor-pointer"
                  >
                    <input
                      v-model="form.permissions"
                      type="checkbox"
                      :value="perm.id"
                      class="h-4 w-4 text-primary-600 border-neutral-300 rounded focus:ring-primary-500"
                    />
                    <span class="text-sm text-neutral-700">{{ perm.name }}</span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-3 pt-4 border-t border-neutral-100">
            <button
              type="submit"
              :disabled="form.processing"
              class="px-6 py-2 bg-primary-900 text-white rounded-lg font-medium hover:bg-primary-800 transition-colors disabled:opacity-50"
            >
              {{ form.processing ? 'Creating...' : 'Create Role' }}
            </button>
            <Link href="/admin/roles" class="px-6 py-2 text-neutral-600 hover:text-neutral-900">Cancel</Link>
          </div>
        </form>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

defineProps({
  modules: Array,
});

const form = useForm({
  name: '',
  slug: '',
  description: '',
  permissions: [],
});

const submit = () => {
  form.post('/admin/roles');
};
</script>

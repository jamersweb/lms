<template>
  <AppShell>
    <div class="max-w-2xl mx-auto space-y-6">
      <!-- Back Button -->
      <Link href="/admin/users" class="inline-flex items-center gap-2 text-neutral-600 hover:text-neutral-900">
        <ArrowLeft class="w-4 h-4" />
        Back to Users
      </Link>

      <!-- Form -->
      <div class="bg-white rounded-xl border border-neutral-200 p-6">
        <h1 class="font-serif text-2xl font-bold text-neutral-900 mb-6">Edit User</h1>

        <form @submit.prevent="submit" class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Name</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              required
            />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Email</label>
            <input
              v-model="form.email"
              type="email"
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              required
            />
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
          </div>

          <div class="flex items-center gap-3">
            <input
              v-model="form.is_admin"
              type="checkbox"
              id="is_admin"
              class="h-4 w-4 text-primary-600 border-neutral-300 rounded focus:ring-primary-500"
            />
            <label for="is_admin" class="text-sm text-neutral-700">Admin privileges</label>
          </div>

          <div class="pt-4 border-t border-neutral-100">
            <h3 class="text-sm font-medium text-neutral-700 mb-4">Change Password (optional)</h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-neutral-700 mb-2">New Password</label>
                <input
                  v-model="form.password"
                  type="password"
                  class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
                  placeholder="Leave blank to keep current password"
                />
                <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-neutral-700 mb-2">Confirm New Password</label>
                <input
                  v-model="form.password_confirmation"
                  type="password"
                  class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
                  placeholder="Confirm new password"
                />
              </div>
            </div>
          </div>

          <div class="flex items-center gap-3 pt-4 border-t border-neutral-100">
            <button
              type="submit"
              :disabled="form.processing"
              class="px-6 py-2 bg-primary-900 text-white rounded-lg font-medium hover:bg-primary-800 transition-colors disabled:opacity-50"
            >
              {{ form.processing ? 'Saving...' : 'Save Changes' }}
            </button>
            <Link href="/admin/users" class="px-6 py-2 text-neutral-600 hover:text-neutral-900">
              Cancel
            </Link>
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

const props = defineProps({
  user: Object,
});

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  is_admin: props.user.is_admin,
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.put(`/admin/users/${props.user.id}`);
};
</script>

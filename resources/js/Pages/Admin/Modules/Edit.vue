<template>
  <AppShell>
    <div class="max-w-2xl mx-auto space-y-6">
      <!-- Back Button -->
      <Link href="/admin/modules" class="inline-flex items-center gap-2 text-neutral-600 hover:text-neutral-900">
        <ArrowLeft class="w-4 h-4" />
        Back to Modules
      </Link>

      <!-- Form -->
      <div class="bg-white rounded-xl border border-neutral-200 p-6">
        <h1 class="font-serif text-2xl font-bold text-neutral-900 mb-6">Edit Module</h1>

        <form @submit.prevent="submit" class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Course *</label>
            <select
              v-model="form.course_id"
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              required
            >
              <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.title }}</option>
            </select>
            <p v-if="form.errors.course_id" class="mt-1 text-sm text-red-600">{{ form.errors.course_id }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Module Title *</label>
            <input
              v-model="form.title"
              type="text"
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              required
            />
            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Description</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Sort Order</label>
            <input
              v-model="form.sort_order"
              type="number"
              min="0"
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
            />
          </div>

          <div class="flex items-center gap-3 pt-4 border-t border-neutral-100">
            <button
              type="submit"
              :disabled="form.processing"
              class="px-6 py-2 bg-primary-900 text-white rounded-lg font-medium hover:bg-primary-800 transition-colors disabled:opacity-50"
            >
              {{ form.processing ? 'Saving...' : 'Save Changes' }}
            </button>
            <Link href="/admin/modules" class="px-6 py-2 text-neutral-600 hover:text-neutral-900">
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
  module: Object,
  courses: Array,
});

const form = useForm({
  course_id: props.module.course_id,
  title: props.module.title,
  description: props.module.description || '',
  sort_order: props.module.sort_order,
});

const submit = () => {
  form.put(`/admin/modules/${props.module.id}`);
};
</script>

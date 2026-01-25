<template>
  <AppShell>
    <Head title="Admin - Create Course" />
    
    <div class="max-w-2xl">
      <!-- Header -->
      <div class="mb-8">
        <Link href="/admin/courses" class="inline-flex items-center gap-2 text-neutral-600 hover:text-primary-600 mb-4">
          <ArrowLeft class="w-4 h-4" />
          Back to Courses
        </Link>
        <h1 class="text-2xl font-serif font-bold text-primary-900">Create New Course</h1>
        <p class="text-neutral-600 mt-1">Add a new course to your catalog</p>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="bg-white rounded-xl border border-neutral-200 p-6 space-y-6">
        <!-- Title -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Course Title *</label>
          <input 
            v-model="form.title"
            type="text" 
            class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
            placeholder="e.g., Tazkiyah - Journey to Purity"
            required
          />
          <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
        </div>

        <!-- Slug -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">URL Slug *</label>
          <input 
            v-model="form.slug"
            type="text" 
            class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
            placeholder="e.g., tazkiyah-journey-to-purity"
            required
          />
          <p class="mt-1 text-xs text-neutral-500">This will be used in the course URL</p>
          <p v-if="form.errors.slug" class="mt-1 text-sm text-red-600">{{ form.errors.slug }}</p>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Description</label>
          <textarea 
            v-model="form.description"
            rows="4"
            class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
            placeholder="Describe what students will learn in this course..."
          ></textarea>
          <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
        </div>

        <!-- Sort Order -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Sort Order</label>
          <input 
            v-model="form.sort_order"
            type="number" 
            class="w-32 px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
          />
          <p class="mt-1 text-xs text-neutral-500">Lower numbers appear first</p>
        </div>

        <!-- Submit -->
        <div class="flex items-center gap-4 pt-4 border-t border-neutral-100">
          <button 
            type="submit"
            :disabled="form.processing"
            class="px-6 py-2.5 bg-primary-900 text-white rounded-lg font-medium hover:bg-primary-800 transition-colors disabled:opacity-50 flex items-center gap-2"
          >
            <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
            {{ form.processing ? 'Creating...' : 'Create Course' }}
          </button>
          <Link href="/admin/courses" class="px-6 py-2.5 text-neutral-600 hover:text-neutral-900">
            Cancel
          </Link>
        </div>
      </form>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Loader2 } from 'lucide-vue-next';

const form = useForm({
  title: '',
  slug: '',
  sort_order: 0,
  description: '',
});

function submit() {
  form.post('/admin/courses');
}
</script>

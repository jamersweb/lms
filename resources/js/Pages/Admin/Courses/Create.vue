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
        <!-- Titles & Descriptions (Multilingual) -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Course Title *</label>
          <input 
            v-model="form.title"
            type="text" 
            class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
            placeholder="e.g., Tazkiyah - Journey to Purity"
            required
          />
          <p class="mt-1 text-xs text-neutral-500">Used as default; you can override per language below.</p>
          <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
        </div>

        <div class="border border-neutral-100 rounded-xl p-4 bg-neutral-50 space-y-4">
          <div class="flex flex-wrap gap-2 text-xs font-medium text-neutral-600 mb-2">
            <span class="px-2 py-0.5 rounded-full bg-white border border-neutral-200">Content language versions</span>
          </div>

          <div class="grid gap-4 md:grid-cols-3">
            <div>
              <label class="block text-xs font-medium text-neutral-700 mb-1">Title (English)</label>
              <input
                v-model="form.title_en"
                type="text"
                class="w-full px-3 py-2 border border-neutral-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-neutral-700 mb-1">Title (Roman)</label>
              <input
                v-model="form.title_en_roman"
                type="text"
                class="w-full px-3 py-2 border border-neutral-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-neutral-700 mb-1">Title (Urdu)</label>
              <input
                v-model="form.title_ur"
                type="text"
                class="w-full px-3 py-2 border border-neutral-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              />
            </div>
          </div>

          <div class="grid gap-4 md:grid-cols-3">
            <div class="md:col-span-3">
              <label class="block text-xs font-medium text-neutral-700 mb-1">Description (English)</label>
              <textarea
                v-model="form.description_en"
                rows="3"
                class="w-full px-3 py-2 border border-neutral-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
                placeholder="Describe what students will learn in this course..."
              ></textarea>
            </div>
            <div class="md:col-span-3">
              <label class="block text-xs font-medium text-neutral-700 mb-1">Description (Roman)</label>
              <textarea
                v-model="form.description_en_roman"
                rows="3"
                class="w-full px-3 py-2 border border-neutral-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              ></textarea>
            </div>
            <div class="md:col-span-3">
              <label class="block text-xs font-medium text-neutral-700 mb-1">Description (Urdu)</label>
              <textarea
                v-model="form.description_ur"
                rows="3"
                class="w-full px-3 py-2 border border-neutral-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
                dir="rtl"
              ></textarea>
            </div>
          </div>

          <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
        </div>

        <!-- Thumbnail Image -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Course Thumbnail Image</label>
          <div class="space-y-4">
            <div v-if="imagePreview" class="relative w-full max-w-md">
              <img :src="imagePreview" alt="Preview" class="w-full h-48 object-cover rounded-xl border border-neutral-200" />
              <button
                type="button"
                @click="clearImage"
                class="absolute top-2 right-2 p-2 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors"
              >
                <X class="w-4 h-4" />
              </button>
            </div>
            <div>
              <label class="block w-full px-4 py-2.5 border-2 border-dashed border-neutral-300 rounded-xl hover:border-primary-400 cursor-pointer transition-colors bg-neutral-50 hover:bg-neutral-100">
                <input
                  type="file"
                  @change="handleImageChange"
                  accept="image/*"
                  class="hidden"
                />
                <div class="text-center">
                  <span class="text-sm font-medium text-neutral-700">Click to upload image</span>
                  <p class="text-xs text-neutral-500 mt-1">JPG, PNG, or WebP (max 5MB)</p>
                </div>
              </label>
              <p class="mt-1 text-xs text-neutral-500">Upload an image for the course thumbnail</p>
            </div>
          </div>
          <p v-if="form.errors.thumbnail" class="mt-1 text-sm text-red-600">{{ form.errors.thumbnail }}</p>
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
import { ArrowLeft, Loader2, X } from 'lucide-vue-next';
import { ref } from 'vue';

const form = useForm({
  title: '',
  sort_order: 0,
  description: '',
  title_en: '',
  title_en_roman: '',
  title_ur: '',
  description_en: '',
  description_en_roman: '',
  description_ur: '',
  thumbnail: null,
});

const imagePreview = ref(null);

function handleImageChange(event) {
  const file = event.target.files[0];
  if (file) {
    form.thumbnail = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
}

function clearImage() {
  form.thumbnail = null;
  imagePreview.value = null;
}

function submit() {
  form.post('/admin/courses', {
    forceFormData: true,
  });
}
</script>

<template>
  <AppShell>
    <Head title="Admin - Create Lesson" />
    
    <div class="max-w-2xl">
      <!-- Header -->
      <div class="mb-8">
        <Link href="/admin/lessons" class="inline-flex items-center gap-2 text-neutral-600 hover:text-primary-600 mb-4">
          <ArrowLeft class="w-4 h-4" />
          Back to Lessons
        </Link>
        <h1 class="text-2xl font-serif font-bold text-primary-900">Create New Lesson</h1>
        <p class="text-neutral-600 mt-1">Add a new lesson to a module</p>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="bg-white rounded-xl border border-neutral-200 p-6 space-y-6">
        <!-- Module -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Module *</label>
          <select 
            v-model="form.module_id"
            class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
            required
          >
            <option value="">Select a module...</option>
            <option v-for="mod in modules" :key="mod.id" :value="mod.id">
              {{ mod.course?.title }} → {{ mod.title }}
            </option>
          </select>
          <p v-if="form.errors.module_id" class="mt-1 text-sm text-red-600">{{ form.errors.module_id }}</p>
        </div>

        <!-- Title -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Lesson Title *</label>
          <input 
            v-model="form.title"
            type="text" 
            class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
            placeholder="e.g., Introduction to Tazkiyah"
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
            placeholder="e.g., introduction-to-tazkiyah"
            required
          />
          <p v-if="form.errors.slug" class="mt-1 text-sm text-red-600">{{ form.errors.slug }}</p>
        </div>

        <!-- Video Provider -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Video Provider</label>
          <div class="flex gap-4">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="form.video_provider" value="youtube" class="text-primary-600" />
              <Youtube class="w-5 h-5 text-red-600" />
              <span>YouTube</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="form.video_provider" value="external" class="text-primary-600" />
              <ExternalLink class="w-5 h-5 text-blue-600" />
              <span>External URL</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="form.video_provider" value="mp4" class="text-primary-600" />
              <Film class="w-5 h-5 text-neutral-600" />
              <span>Upload MP4</span>
            </label>
          </div>
        </div>

        <!-- YouTube Video ID -->
        <div v-if="form.video_provider === 'youtube'" class="bg-neutral-50 rounded-xl p-4 space-y-4">
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">YouTube Video ID</label>
            <input 
              v-model="form.youtube_video_id"
              type="text" 
              class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400 bg-white"
              placeholder="e.g., dQw4w9WgXcQ"
            />
            <p class="mt-1 text-xs text-neutral-500">The ID from the YouTube URL (e.g., youtube.com/watch?v=<strong>dQw4w9WgXcQ</strong>)</p>
          </div>
          <div v-if="form.youtube_video_id" class="rounded-lg overflow-hidden">
            <p class="text-sm font-medium text-neutral-700 mb-2">Preview:</p>
            <iframe 
              class="w-full aspect-video rounded-lg"
              :src="`https://www.youtube.com/embed/${form.youtube_video_id}`" 
              frameborder="0" 
              allowfullscreen
            ></iframe>
          </div>
        </div>

        <!-- External URL -->
        <div v-if="form.video_provider === 'external'" class="bg-neutral-50 rounded-xl p-4">
          <label class="block text-sm font-medium text-neutral-700 mb-2">External Video URL</label>
          <input 
            v-model="form.external_video_url"
            type="url" 
            class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400 bg-white"
            placeholder="https://tazkiyahtarbiyah.com/videos/..."
          />
        </div>

        <!-- MP4 Upload -->
        <div v-if="form.video_provider === 'mp4'" class="bg-neutral-50 rounded-xl p-4">
          <label class="block text-sm font-medium text-neutral-700 mb-2">Upload MP4 File</label>
          <input 
            type="file" 
            @change="e => form.video_file = e.target.files[0]" 
            accept="video/mp4"
            class="w-full"
          />
          <p v-if="form.video_file" class="mt-2 text-sm text-neutral-600">Selected: {{ form.video_file.name }}</p>
        </div>

        <!-- Sort Order & Free Preview -->
        <div class="grid grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Sort Order</label>
            <input 
              v-model="form.sort_order"
              type="number" 
              class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
            />
          </div>
          <div class="flex items-center pt-8">
            <label class="flex items-center gap-3 cursor-pointer">
              <input type="checkbox" v-model="form.is_free_preview" class="h-5 w-5 rounded text-primary-600" />
              <span class="text-sm font-medium text-neutral-700">Free Preview</span>
            </label>
          </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center gap-4 pt-4 border-t border-neutral-100">
          <button 
            type="submit"
            :disabled="form.processing"
            class="px-6 py-2.5 bg-primary-900 text-white rounded-lg font-medium hover:bg-primary-800 transition-colors disabled:opacity-50 flex items-center gap-2"
          >
            <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
            {{ form.processing ? 'Creating...' : 'Create Lesson' }}
          </button>
          <Link href="/admin/lessons" class="px-6 py-2.5 text-neutral-600 hover:text-neutral-900">
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
import { ArrowLeft, Loader2, Youtube, ExternalLink, Film } from 'lucide-vue-next';

defineProps({
  modules: Array,
});

const form = useForm({
  module_id: '',
  title: '',
  slug: '',
  video_provider: 'youtube',
  youtube_video_id: '',
  external_video_url: '',
  video_file: null,
  sort_order: 0,
  is_free_preview: false,
});

function submit() {
  form.post('/admin/lessons');
}
</script>

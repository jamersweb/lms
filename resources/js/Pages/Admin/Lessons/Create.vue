<template>
  <AppShell>
    <Head title="Admin - Create Lesson" />
    
    <div class="w-full">
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

        <!-- Titles (Multilingual) -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Lesson Title *</label>
          <input 
            v-model="form.title"
            type="text" 
            class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
            placeholder="e.g., Introduction to Tazkiyah"
            required
          />
          <p class="mt-1 text-xs text-neutral-500">Used as default; you can override per language below.</p>
          <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
        </div>

        <div class="border border-neutral-100 rounded-xl p-4 bg-neutral-50 space-y-4">
          <div class="flex flex-wrap gap-2 text-xs font-medium text-neutral-600 mb-2">
            <span class="px-2 py-0.5 rounded-full bg-white border border-neutral-200">Content language titles</span>
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
                dir="rtl"
              />
            </div>
          </div>
        </div>

        <!-- Lesson Image -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Lesson Image</label>
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
              <p class="mt-1 text-xs text-neutral-500">Upload an image for the lesson</p>
            </div>
          </div>
          <p v-if="form.errors.image" class="mt-1 text-sm text-red-600">{{ form.errors.image }}</p>
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

        <!-- Transcript Upload -->
        <div class="bg-neutral-50 rounded-xl p-4 space-y-2">
          <div class="flex items-center justify-between">
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">Transcript File (.vtt or .srt)</label>
              <p class="text-xs text-neutral-500">
                Uploading a new file will replace existing transcript segments.
              </p>
            </div>
          </div>

          <input
            type="file"
            accept=".vtt,.srt"
            class="w-full"
            @change="e => form.transcript_file = e.target.files[0]"
          />
          <p v-if="form.transcript_file" class="mt-1 text-sm text-neutral-600">
            New transcript: {{ form.transcript_file.name }}
          </p>
          <p v-if="form.errors.transcript_file" class="mt-1 text-sm text-red-600">
            {{ form.errors.transcript_file }}
          </p>
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

        <!-- Release Schedule (Drip Release) -->
        <div class="bg-neutral-50 rounded-xl p-4 space-y-4 border border-neutral-200">
          <div>
            <h3 class="text-sm font-semibold text-neutral-900 mb-1">Release Schedule</h3>
            <p class="text-xs text-neutral-600">
              Control when this lesson becomes available to students. Absolute release applies to all students. Relative offset releases per-student based on enrollment start date.
            </p>
          </div>

          <!-- Absolute Release -->
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">
              Absolute Release Date/Time (Optional)
            </label>
            <input
              v-model="form.release_at"
              type="datetime-local"
              class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400 bg-white"
            />
            <p class="mt-1 text-xs text-neutral-500">
              If set, this overrides the day offset. All students will see this lesson at this exact time.
            </p>
          </div>

          <!-- Relative Day Offset -->
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">
              Day Offset from Enrollment (Optional)
            </label>
            <input
              v-model.number="form.release_day_offset"
              type="number"
              min="0"
              max="365"
              class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400 bg-white"
              placeholder="e.g., 1 = next day after enrollment"
            />
            <p class="mt-1 text-xs text-neutral-500">
              Number of days after student enrollment start date (0 = immediately, 1 = next day, etc.). Ignored if absolute release is set.
            </p>
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

      <!-- Content Rule -->
      <div class="mt-8">
        <p class="text-sm text-neutral-500 mb-4">
          Content rules, resources, and tasks can be added after creating the lesson. You will be redirected to the edit page.
        </p>
      </div>

      <!-- Lesson Resources -->
      <div class="mt-8 bg-white rounded-xl border border-neutral-200 p-6">
        <h3 class="text-lg font-semibold text-neutral-900 mb-4">Sunnah & Dua Resources</h3>
        <p class="text-sm text-neutral-600 mb-4">
          Add Sunnah pointers and Duas that will be shown to students after completing this lesson.
        </p>

        <form @submit.prevent="submitResources" class="space-y-4" enctype="multipart/form-data">
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">
              Sunnah Pointers
            </label>
            <textarea
              v-model="resourceForm.sunnah_pointers"
              rows="4"
              class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              placeholder="Enter Sunnah pointers and reminders..."
            ></textarea>
            <p class="mt-1 text-xs text-neutral-500">
              Short pointers about Sunnah practices related to this lesson
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">
              Duas (Arabic/Urdu/English)
            </label>
            <textarea
              v-model="resourceForm.duas_text"
              rows="6"
              class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              placeholder="Enter Duas text..."
              dir="rtl"
            ></textarea>
            <p class="mt-1 text-xs text-neutral-500">
              Duas related to this lesson (supports Arabic, Urdu, and English)
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">
              Dua Audio File (Optional)
            </label>
            <input
              type="file"
              @change="e => resourceForm.audio_file = e.target.files[0]"
              accept="audio/mp3,audio/wav,audio/ogg"
              class="w-full"
            />
            <p class="mt-1 text-xs text-neutral-500">
              Upload audio file for Dua pronunciation (MP3, WAV, or OGG, max 10MB)
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">
              Lesson Notes PDF (Optional)
            </label>
            <input
              type="file"
              @change="e => resourceForm.pdf_file = e.target.files[0]"
              accept="application/pdf"
              class="w-full"
            />
            <p class="mt-1 text-xs text-neutral-500">
              Upload PDF file with lesson notes (max 5MB)
            </p>
          </div>

          <div class="flex gap-3">
            <button
              type="submit"
              :disabled="resourceForm.processing"
              class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 disabled:opacity-50"
            >
              Save Resources
            </button>
          </div>
        </form>
      </div>

      <!-- Task Management -->
      <div class="mt-8 bg-white rounded-xl border border-neutral-200 p-6">
        <h3 class="text-lg font-semibold text-neutral-900 mb-4">Practice Task</h3>
        <p class="text-sm text-neutral-600 mb-4">
          Attach a task that students must complete before accessing the next lesson.
        </p>

        <form @submit.prevent="submitTask" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">
              Task Title <span class="text-red-500">*</span>
            </label>
            <input
              v-model="taskForm.title"
              type="text"
              required
              maxlength="255"
              class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              placeholder="e.g., Practice patience for 7 days"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">
              Required Days <span class="text-red-500">*</span>
            </label>
            <input
              v-model.number="taskForm.required_days"
              type="number"
              required
              min="1"
              max="365"
              class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
            />
            <p class="mt-1 text-xs text-neutral-500">
              Number of days students must check in to complete this task (1-365)
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">
              Instructions (Optional)
            </label>
            <textarea
              v-model="taskForm.instructions"
              rows="4"
              class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              placeholder="Provide guidance on what students should practice..."
            ></textarea>
          </div>

          <div class="flex items-center gap-2">
            <input
              v-model="taskForm.unlock_next_lesson"
              type="checkbox"
              id="unlock_next_lesson"
              class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
            />
            <label for="unlock_next_lesson" class="text-sm text-neutral-700 cursor-pointer">
              Block next lesson until task is completed
            </label>
          </div>

          <div class="flex gap-3">
            <button
              type="submit"
              :disabled="taskForm.processing"
              class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 disabled:opacity-50"
            >
              Create Task
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import ContentRuleForm from '@/Components/Admin/ContentRuleForm.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ArrowLeft, Loader2, Youtube, ExternalLink, Film, X } from 'lucide-vue-next';
import { getCurrentInstance, inject, ref } from 'vue';

defineProps({
  modules: Array,
});

// Get route helper
const route = inject('route', null) ||
  getCurrentInstance()?.appContext.config.globalProperties.route ||
  (typeof window !== 'undefined' && window.route) ||
  ((name, params) => {
    console.error('Route helper not available. Route name:', name, params);
    return '#';
  });

const form = useForm({
  module_id: '',
  title: '',
  title_en: '',
  title_en_roman: '',
  title_ur: '',
  image: null,
  video_provider: 'youtube',
  youtube_video_id: '',
  external_video_url: '',
  video_file: null,
  transcript_file: null,
  sort_order: 0,
  is_free_preview: false,
  release_at: null,
  release_day_offset: null,
});

const imagePreview = ref(null);

function handleImageChange(event) {
  const file = event.target.files[0];
  if (file) {
    form.image = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
}

function clearImage() {
  form.image = null;
  imagePreview.value = null;
}

function submit() {
  form.post('/admin/lessons', {
    forceFormData: true,
    // Will redirect to edit page after creation
  });
}

const resourceForm = useForm({
  sunnah_pointers: '',
  duas_text: '',
  audio_file: null,
  pdf_file: null,
});

function submitResources() {
  alert('Please create the lesson first. After creation, you will be redirected to the edit page where you can add resources.');
}

const taskForm = useForm({
  title: '',
  required_days: 1,
  instructions: '',
  unlock_next_lesson: true,
});

function submitTask() {
  alert('Please create the lesson first. After creation, you will be redirected to the edit page where you can add a task.');
}
</script>

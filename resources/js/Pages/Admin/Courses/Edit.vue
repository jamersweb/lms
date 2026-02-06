<template>
  <AppShell>
    <Head :title="`Admin - Edit ${course.title}`" />

    <div class="max-w-2xl">
      <!-- Header -->
      <div class="mb-8">
        <Link href="/admin/courses" class="inline-flex items-center gap-2 text-neutral-600 hover:text-primary-600 mb-4">
          <ArrowLeft class="w-4 h-4" />
          Back to Courses
        </Link>
        <h1 class="text-2xl font-serif font-bold text-primary-900">Edit Course</h1>
        <p class="text-neutral-600 mt-1">Update course details</p>
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
            required
          />
          <p class="mt-1 text-xs text-neutral-500">URL slug will be automatically updated when title changes</p>
          <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Description</label>
          <textarea
            v-model="form.description"
            rows="4"
            class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
          ></textarea>
          <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
        </div>

        <!-- Thumbnail Image -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Course Thumbnail Image</label>
          <div class="space-y-4">
            <div v-if="imagePreview || course.thumbnail" class="relative w-full max-w-md">
              <img 
                :src="imagePreview || course.thumbnail" 
                alt="Preview" 
                class="w-full h-48 object-cover rounded-xl border border-neutral-200" 
              />
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
            {{ form.processing ? 'Saving...' : 'Save Changes' }}
          </button>
          <Link href="/admin/courses" class="px-6 py-2.5 text-neutral-600 hover:text-neutral-900">
            Cancel
          </Link>
        </div>
      </form>

      <!-- Content Rule -->
      <div class="mt-8">
        <ContentRuleForm
          type="courses"
          :entity-id="course.id"
          :initial-rule="contentRule"
        />
      </div>

      <!-- Danger Zone -->
      <div class="mt-8 bg-red-50 rounded-xl border border-red-200 p-6">
        <h3 class="font-semibold text-red-800 mb-2">Danger Zone</h3>
        <p class="text-sm text-red-600 mb-4">Deleting this course will remove all modules, lessons, and student enrollments.</p>
        <button
          @click="deleteCourse"
          class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors"
        >
          Delete Course
        </button>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ArrowLeft, Loader2, X } from 'lucide-vue-next';
import ContentRuleForm from '@/Components/Admin/ContentRuleForm.vue';
import { ref } from 'vue';

const props = defineProps({
  course: Object,
  contentRule: Object,
});

const form = useForm({
  title: props.course.title,
  sort_order: props.course.sort_order,
  description: props.course.description || '',
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
  form.post(`/admin/courses/${props.course.id}`, {
    forceFormData: true,
    _method: 'PUT',
  });
}

function deleteCourse() {
  if (confirm(`Are you sure you want to delete "${props.course.title}"? This cannot be undone.`)) {
    router.delete(`/admin/courses/${props.course.id}`);
  }
}
</script>

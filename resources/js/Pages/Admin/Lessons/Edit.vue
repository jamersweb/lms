<template>
  <div style="padding: 20px; font-family: sans-serif;">
    <h1>Edit Lesson: {{ lesson.title }}</h1>
    <form @submit.prevent="submit">
      <div style="margin-bottom: 15px;">
        <label>Module:</label>
        <select v-model="form.module_id" required>
            <option v-for="mod in modules" :key="mod.id" :value="mod.id">
                {{ mod.course?.title }} - {{ mod.title }}
            </option>
        </select>
      </div>

      <div style="margin-bottom: 15px;">
        <label>Title:</label>
        <input v-model="form.title" type="text" required />
      </div>

      <div style="margin-bottom: 15px;">
        <label>Slug:</label>
        <input v-model="form.slug" type="text" required />
      </div>

       <div style="margin-bottom: 15px;">
        <label>Video Provider:</label>
        <select v-model="form.video_provider">
            <option value="youtube">YouTube</option>
            <option value="mp4">Hosted MP4</option>
        </select>
      </div>

      <div v-if="form.video_provider === 'youtube'" style="margin-bottom: 15px; border: 1px solid #ccc; padding: 10px;">
        <label>YouTube Video ID:</label>
        <input v-model="form.youtube_video_id" type="text" placeholder="dQw4w9WgXcQ" />
        <div v-if="form.youtube_video_id" style="margin-top: 10px;">
            <p>Preview:</p>
            <iframe 
                width="300" 
                height="170" 
                :src="`https://www.youtube.com/embed/${form.youtube_video_id}`" 
                frameborder="0" 
                allowfullscreen>
            </iframe>
        </div>
      </div>

      <div v-if="form.video_provider === 'mp4'" style="margin-bottom: 15px; border: 1px solid #ccc; padding: 10px;">
        <label>MP4 File (Upload to replace):</label>
        <input type="file" @change="e => form.video_file = e.target.files[0]" accept="video/mp4" />
        
        <div v-if="lesson.video_path && !form.video_file" style="margin-top: 10px;">
            <p>Current Video:</p>
            <video width="300" controls :src="`/storage/${lesson.video_path}`"></video>
        </div>
         <div v-if="form.video_file" style="margin-top: 10px;">
             <p>Selected for upload: {{ form.video_file.name }}</p>
        </div>
      </div>

      <div style="margin-bottom: 15px;">
        <label>Sort Order:</label>
        <input v-model="form.sort_order" type="number" />
      </div>
      
       <div style="margin-bottom: 15px;">
        <label>
            <input v-model="form.is_free_preview" type="checkbox" />
            Free Preview
        </label>
      </div>

      <button type="submit">Update Lesson</button>
    </form>
  </div>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3'

const props = defineProps({
  lesson: Object,
  modules: Array,
})

const form = useForm({
  module_id: props.lesson.module_id,
  title: props.lesson.title,
  slug: props.lesson.slug,
  video_provider: props.lesson.video_provider,
  youtube_video_id: props.lesson.youtube_video_id,
  video_file: null,
  sort_order: props.lesson.sort_order,
  is_free_preview: Boolean(props.lesson.is_free_preview),
  // method spoofing for file upload
  _method: 'put',
})

function submit() {
    // inertia form helper doesn't support forcing form data easily with put?
    // actually it does if we use form.post and _method: put
  form.post(`/admin/lessons/${props.lesson.id}`, {
      forceFormData: true,
  })
}
</script>

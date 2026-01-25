<template>
  <div style="padding: 20px; font-family: sans-serif;">
    <h1>Create Lesson</h1>
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
        <label>MP4 File:</label>
        <input type="file" @change="e => form.video_file = e.target.files[0]" accept="video/mp4" />
        <div v-if="form.video_file" style="margin-top: 10px;">
             <!-- Preview specific file object not easily possible without converting to URL, ignoring strictly for now but could use URL.createObjectURL -->
             <p>Selected: {{ form.video_file.name }}</p>
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

      <button type="submit">Create Lesson</button>
    </form>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'

defineProps({
  modules: Array,
})

const form = useForm({
  module_id: '',
  title: '',
  slug: '',
  video_provider: 'youtube',
  youtube_video_id: '',
  video_file: null,
  sort_order: 0,
  is_free_preview: false,
})

function submit() {
  form.post('/admin/lessons')
}
</script>

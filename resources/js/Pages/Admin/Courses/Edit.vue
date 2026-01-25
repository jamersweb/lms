<template>
  <div style="padding: 20px; font-family: sans-serif;">
    <h1>Edit Course: {{ course.title }}</h1>
    <form @submit.prevent="submit">
      <div style="margin-bottom: 15px;">
        <label>Title:</label>
        <input v-model="form.title" type="text" required />
      </div>
      <div style="margin-bottom: 15px;">
        <label>Slug:</label>
        <input v-model="form.slug" type="text" required />
      </div>
      <div style="margin-bottom: 15px;">
        <label>Sort Order:</label>
        <input v-model="form.sort_order" type="number" />
      </div>
      <div style="margin-bottom: 15px;">
        <label>Description:</label>
        <textarea v-model="form.description"></textarea>
      </div>
      <button type="submit">Update</button>
    </form>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  course: Object,
})

const form = useForm({
  title: props.course.title,
  slug: props.course.slug,
  sort_order: props.course.sort_order,
  description: props.course.description,
})

function submit() {
  form.put(`/admin/courses/${props.course.id}`)
}
</script>

<template>
  <div style="padding: 20px; font-family: sans-serif;">
    <h1>Admin Lessons</h1>
    <a href="/admin/lessons/create" style="margin-bottom: 20px; display: inline-block;">+ Create New Lesson</a>
    
    <table border="1" cellpadding="10" style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr>
          <th>Title</th>
          <th>Module</th>
          <th>Provider</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="lesson in lessons" :key="lesson.id">
          <td>{{ lesson.title }}</td>
          <td>{{ lesson.module?.title }}</td>
          <td>{{ lesson.video_provider }}</td>
          <td>
            <a :href="`/admin/lessons/${lesson.id}/edit`">Edit</a>
          </td>
        </tr>
         <tr v-if="lessons.length === 0">
          <td colspan="4">No lessons found.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
defineProps({
  lessons: Array,
})
</script>

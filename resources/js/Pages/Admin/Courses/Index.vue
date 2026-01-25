<template>
  <div style="padding: 20px; font-family: sans-serif;">
    <h1>Admin Courses</h1>
    <a href="/admin/courses/create" style="margin-bottom: 20px; display: inline-block;">+ Create New Course</a>
    
    <table border="1" cellpadding="10" style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr>
          <th>Title</th>
          <th>Modules</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="course in courses" :key="course.id">
          <td>{{ course.title }}</td>
          <td>{{ course.modules_count }}</td>
          <td>
            <a :href="`/admin/courses/${course.id}/edit`">Edit</a>
          </td>
        </tr>
        <tr v-if="courses.length === 0">
          <td colspan="3">No courses found.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
defineProps({
  courses: Array,
})
</script>

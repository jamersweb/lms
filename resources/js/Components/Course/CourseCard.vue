<template>
  <div class="group flex flex-col bg-white rounded-xl border border-neutral-100 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden cursor-pointer h-full">
    <!-- Thumbnail -->
    <div class="aspect-video bg-neutral-100 relative overflow-hidden">
      <img :src="course.thumbnail" :alt="course.title" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" />
      <div class="absolute top-3 left-3">
        <span class="bg-white/90 backdrop-blur-sm px-2 py-0.5 rounded text-xs font-medium">{{ course.level }}</span>
      </div>
    </div>

    <!-- Content -->
    <div class="flex-1 p-5 flex flex-col">
      <div class="mb-2 text-xs font-semibold text-primary-600">
        {{ course.instructor }}
      </div>

      <h3 class="text-lg font-serif font-bold text-neutral-900 mb-2">
        {{ course.title }}
      </h3>
      
      <p class="text-sm text-neutral-500 line-clamp-2 mb-4 flex-1">
        {{ course.description }}
      </p>

      <!-- Meta Data -->
      <div class="flex items-center text-xs text-neutral-400 gap-4 mb-4 border-t pt-3">
        <span>{{ course.lessons_count }} Lessons</span>
        <span>{{ course.duration }}</span>
      </div>

      <!-- Action -->
      <div class="mt-auto">
        <div class="w-full py-2 text-center text-sm font-medium text-primary-700 bg-primary-50 rounded-lg group-hover:bg-primary-600 group-hover:text-white transition-colors">
           View Course
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  course: Object
});
</script>

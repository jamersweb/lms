<template>
  <AppShell>
    <!-- Header & Filters -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
       <div>
         <h1 class="font-serif text-3xl font-bold text-primary-900">Explore Courses</h1>
         <p class="text-neutral-500 mt-1">Discover knowledge to purify the heart and soul.</p>
       </div>

       <div class="flex flex-col sm:flex-row gap-3">
          <!-- Search -->
          <div class="relative">
             <div class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400">🔍</div>
             <input
               type="text"
               placeholder="Search courses..."
               class="pl-10 pr-4 py-2 border border-neutral-300 rounded-lg focus:ring-primary-300 focus:border-primary-900 text-sm w-full sm:w-64"
             />
          </div>

          <!-- Filter -->
          <select class="py-2 pl-3 pr-8 border border-neutral-300 rounded-lg focus:ring-primary-300 focus:border-primary-900 text-sm bg-white">
             <option>All Levels</option>
             <option>Beginner</option>
             <option>Intermediate</option>
             <option>Advanced</option>
          </select>
       </div>
    </div>

    <!-- Course Grid -->
    <div v-if="courses && courses.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
       <Link v-for="course in courses" :key="course.id" :href="'/courses/' + course.id" class="group block">
          <div class="flex flex-col bg-white rounded-xl border border-neutral-100 shadow-sm hover:shadow-lg hover:border-primary-200 transition-all duration-300 overflow-hidden h-full">
            <!-- Thumbnail -->
            <div class="aspect-video bg-gradient-to-br from-primary-800 to-primary-950 relative overflow-hidden">
              <img
                v-if="course.thumbnail && !course.thumbnail.includes('faker')"
                :src="course.thumbnail"
                :alt="course.title"
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
              />
              <!-- Placeholder with initials for courses without thumbnail -->
              <div v-else class="w-full h-full flex items-center justify-center">
                <span class="text-5xl font-serif font-bold text-white/80">
                  {{ getInitials(course.title) }}
                </span>
              </div>
              <div class="absolute top-3 left-3 flex gap-2">
                <span :class="[
                  'backdrop-blur-sm px-2.5 py-1 rounded-full text-xs font-semibold',
                  course.level === 'Beginner' ? 'bg-emerald-500/90 text-white' : '',
                  course.level === 'Intermediate' ? 'bg-secondary-500/90 text-white' : '',
                  course.level === 'Advanced' ? 'bg-primary-900/90 text-white' : '',
                  !course.level ? 'bg-white/90 text-neutral-700' : ''
                ]">{{ course.level || 'All Levels' }}</span>
                <span v-if="course.is_locked" class="backdrop-blur-sm px-2.5 py-1 rounded-full text-xs font-semibold bg-red-500/90 text-white flex items-center gap-1">
                  <Lock class="w-3 h-3" />
                  Locked
                </span>
              </div>
            </div>

            <!-- Content -->
            <div class="flex-1 p-5 flex flex-col">
              <div class="mb-2 text-xs font-semibold text-primary-900 uppercase tracking-wide">
                {{ course.instructor }}
              </div>

              <h3 class="text-lg font-serif font-bold text-neutral-900 mb-2 group-hover:text-primary-900 transition-colors line-clamp-2">
                {{ course.title }}
              </h3>

              <p v-if="course.is_locked && course.lock_message" class="text-xs text-red-600 mb-2 flex items-center gap-1">
                <Lock class="w-3 h-3" />
                {{ course.lock_message }}
              </p>

              <p class="text-sm text-neutral-500 line-clamp-2 mb-4 flex-1">
                {{ course.description }}
              </p>

              <!-- Meta Data -->
              <div class="flex items-center text-xs text-neutral-400 gap-4 mb-4 border-t border-neutral-100 pt-3">
                <span class="flex items-center gap-1">
                  <BookOpen class="w-3.5 h-3.5" />
                  {{ course.lessons_count }} Lessons
                </span>
                <span class="flex items-center gap-1">
                  <Clock class="w-3.5 h-3.5" />
                  {{ course.duration }}
                </span>
              </div>

              <!-- Action -->
              <div class="mt-auto">
                <div :class="[
                  'w-full py-2.5 text-center text-sm font-semibold rounded-lg transition-colors',
                  course.is_locked
                    ? 'text-neutral-500 bg-neutral-100 cursor-not-allowed'
                    : 'text-primary-900 bg-primary-50 group-hover:bg-primary-900 group-hover:text-white'
                ]">
                   {{ course.is_locked ? 'Locked' : 'View Course' }}
                </div>
              </div>
            </div>
          </div>
       </Link>
    </div>
    <div v-else class="text-center py-20 bg-white rounded-xl border border-dashed border-neutral-300">
       <BookOpen class="w-12 h-12 mx-auto text-neutral-300 mb-4" />
       <h3 class="text-lg font-medium text-neutral-900">No courses found</h3>
       <p class="text-neutral-500 mt-1">Check back soon for new content.</p>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Clock, Lock } from 'lucide-vue-next';

defineProps({
    courses: Array
});

// Get initials from course title for placeholder
const getInitials = (title) => {
    return title
        .split(' ')
        .filter(word => word.length > 2) // Skip small words
        .slice(0, 2)
        .map(word => word[0])
        .join('')
        .toUpperCase();
};
</script>

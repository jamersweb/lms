<template>
  <AppShell>
    <div v-if="course" class="max-w-5xl mx-auto">
        <!-- Course Header -->
        <div class="bg-white rounded-2xl border border-neutral-100 shadow-sm p-8 mb-8 flex flex-col md:flex-row gap-8">
           <!-- Thumbnail -->
           <div class="w-full md:w-1/3 aspect-video rounded-xl overflow-hidden shrink-0 border border-neutral-100 bg-gradient-to-br from-primary-800 to-primary-950">
              <img
                v-if="course.thumbnail && !course.thumbnail.includes('ui-avatars')"
                :src="course.thumbnail"
                alt="Course Thumbnail"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center">
                <span class="text-5xl font-serif font-bold text-white/80">
                  {{ getInitials(course.title) }}
                </span>
              </div>
           </div>

           <!-- Info -->
           <div class="flex-1 flex flex-col">
              <div class="flex items-center gap-2 mb-2">
                 <span class="bg-primary-50 text-primary-900 px-2 py-0.5 rounded text-xs font-bold">{{ course.level }}</span>
                 <span class="text-sm text-neutral-500">• {{ course.duration }}</span>
                 <span class="text-sm text-neutral-500">• {{ course.lessons_count }} Lessons</span>
              </div>

              <h1 class="font-serif text-3xl font-bold text-neutral-900 mb-3">{{ course.title }}</h1>

              <div v-if="course.is_locked && course.lock_message" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center gap-2 text-sm text-red-700">
                  <Lock class="w-4 h-4" />
                  <span class="font-medium">{{ course.lock_message }}</span>
                </div>
              </div>

              <p class="text-neutral-600 mb-6 leading-relaxed flex-1">
                 {{ course.description }}
              </p>

              <div class="flex items-center justify-between border-t border-neutral-100 pt-6 mt-auto">
                 <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center font-bold text-primary-900 uppercase">
                       {{ course.instructor ? course.instructor.substring(0,2) : 'IN' }}
                    </div>
                    <div>
                       <div class="text-xs text-neutral-500">Instructor</div>
                       <div class="text-sm font-medium text-neutral-900">{{ course.instructor }}</div>
                    </div>
                 </div>

                 <!-- Enroll / Continue CTA -->
                 <div v-if="course.is_enrolled">
                    <div class="text-right text-xs text-neutral-500 mb-1">{{ course.progress }}% Completed</div>
                    <div class="w-32 bg-neutral-100 rounded-full h-2 mb-2">
                        <div class="bg-primary-900 h-2 rounded-full transition-all" :style="{ width: course.progress + '%' }"></div>
                    </div>
                    <Link
                      v-if="course.next_lesson"
                      :href="course.next_lesson.url"
                      class="text-sm font-medium text-primary-900 hover:text-primary-700"
                    >
                      Continue: {{ course.next_lesson.title }} →
                    </Link>
                 </div>
                 <div v-else>
                    <button
                      @click="enrollInCourse"
                      :disabled="enrolling"
                      class="px-6 py-2.5 bg-primary-900 text-white rounded-lg font-semibold hover:bg-primary-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    >
                      <Loader2 v-if="enrolling" class="w-4 h-4 animate-spin" />
                      {{ enrolling ? 'Enrolling...' : 'Enroll Now' }}
                    </button>
                 </div>
              </div>
           </div>
        </div>

        <!-- Curriculum Content -->
        <div class="space-y-6">
           <h2 class="text-2xl font-serif font-bold text-neutral-900">Course Curriculum</h2>

           <div class="space-y-4">
              <!-- Module Item -->
              <div v-for="module in course.modules" :key="module.id" class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                 <!-- Module Header -->
                 <div class="bg-neutral-50 px-6 py-4 flex items-center justify-between border-b border-neutral-100">
                    <div class="flex items-center gap-2">
                       <h3 class="font-medium text-neutral-900">{{ module.title }}</h3>
                       <span v-if="module.is_locked" class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-medium">
                          <Lock class="w-3 h-3" />
                          Locked
                       </span>
                    </div>
                    <span class="text-xs text-neutral-500">{{ module.lessons.length }} Lessons</span>
                 </div>

                 <!-- Module lock message -->
                 <div v-if="module.is_locked && module.lock_message" class="px-6 py-2 bg-red-50 border-b border-red-100">
                    <p class="text-xs text-red-600">{{ module.lock_message }}</p>
                 </div>

                 <!-- Lessons List -->
                 <div class="divide-y divide-neutral-100">
                    <component
                      :is="(course.is_enrolled && !lesson.is_locked) ? Link : 'div'"
                      v-for="(lesson, index) in module.lessons"
                      :key="lesson.id"
                      :href="(course.is_enrolled && !lesson.is_locked) ? '/courses/' + course.id + '/lessons/' + lesson.id : undefined"
                      :class="[
                        'group flex items-center justify-between px-6 py-4 transition-colors',
                        (course.is_enrolled && !lesson.is_locked) ? 'hover:bg-neutral-50 cursor-pointer' : 'opacity-75',
                        lesson.is_locked ? 'bg-red-50/50' : ''
                      ]"
                    >
                       <div class="flex items-center gap-4 flex-1">
                          <div :class="[
                             'h-8 w-8 rounded-full flex items-center justify-center shrink-0 border',
                             lesson.is_completed
                                 ? 'bg-emerald-50 border-emerald-200 text-emerald-600'
                                 : lesson.is_locked
                                   ? 'bg-red-100 border-red-200 text-red-600'
                                   : lesson.is_next
                                     ? 'bg-primary-100 border-primary-300 text-primary-700 ring-2 ring-primary-200'
                                     : course.is_enrolled
                                       ? 'bg-white border-neutral-200 text-neutral-400 group-hover:border-primary-200 group-hover:text-primary-500'
                                       : 'bg-neutral-100 border-neutral-200 text-neutral-400'
                          ]">
                              <Check v-if="lesson.is_completed" class="w-4 h-4" />
                              <Lock v-else-if="lesson.is_locked || !course.is_enrolled" class="w-3 h-3" />
                              <Play v-else class="w-3 h-3 ml-0.5" />
                          </div>

                          <div class="flex-1">
                             <div :class="[
                               'text-sm font-medium transition-colors flex items-center gap-2',
                               lesson.is_locked ? 'text-red-700' : lesson.is_next ? 'text-primary-900 font-semibold' : course.is_enrolled ? 'text-neutral-700 group-hover:text-primary-900' : 'text-neutral-500'
                             ]">
                                 {{ index + 1 }}. {{ lesson.title }}
                                 <span v-if="lesson.is_next && !lesson.is_locked" class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-primary-100 text-primary-700 rounded text-xs font-medium">
                                    Next
                                 </span>
                                 <span v-else-if="lesson.is_locked" class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-red-100 text-red-700 rounded text-xs font-medium">
                                    <Lock class="w-2.5 h-2.5" />
                                    <span v-if="lesson.lock_reason_codes?.includes('previous_lesson_incomplete')">Complete previous first</span>
                                    <span v-else-if="lesson.lock_reason_codes?.includes('reflection_required')">Reflection required</span>
                                    <span v-else-if="lesson.lock_reason_codes?.includes('task_incomplete')">Task required</span>
                                    <span v-else-if="lesson.lock_reason_codes?.includes('not_released_yet')">Not released yet</span>
                                    <span v-else-if="lesson.lock_reason_codes?.includes('not_next_lesson')">Complete in order</span>
                                    <span v-else>Locked</span>
                                 </span>
                             </div>
                             <p v-if="lesson.is_locked && lesson.lock_message" class="text-xs text-red-600 mt-1">
                                {{ lesson.lock_message }}
                             </p>
                             <p v-if="lesson.is_locked && lesson.lock_reason_codes?.includes('task_incomplete') && lesson.task_required_days && lesson.task_days_done !== null" class="text-xs text-neutral-600 mt-1">
                                Task progress: {{ lesson.task_days_done }} / {{ lesson.task_required_days }} days
                             </p>
                             <p v-if="!lesson.is_released && lesson.release_human" class="text-xs text-primary-600 mt-1 font-medium">
                                {{ lesson.release_human }}
                             </p>
                          </div>
                       </div>

                       <div class="flex items-center gap-4 text-xs text-neutral-400">
                          <span>Video • {{ lesson.duration }}</span>
                       </div>
                    </component>
                 </div>
              </div>
           </div>
        </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Check, Play, Lock, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    course: Object
});

const enrolling = ref(false);

// Get initials from course title for placeholder
const getInitials = (title) => {
    return title
        .split(' ')
        .filter(word => word.length > 2)
        .slice(0, 2)
        .map(word => word[0])
        .join('')
        .toUpperCase();
};

// Enroll in course
const enrollInCourse = () => {
    enrolling.value = true;

    router.post(`/courses/${props.course.id}/enroll`, {}, {
        preserveScroll: true,
        onFinish: () => {
            enrolling.value = false;
        },
        onError: (errors) => {
            console.error('Enrollment error:', errors);
            enrolling.value = false;
        }
    });
};
</script>

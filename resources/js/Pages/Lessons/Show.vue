<template>
  <AppShell>
    <div class="h-[calc(100vh-8rem)] flex flex-col lg:flex-row gap-6">
       <!-- Main Content Area -->
       <div class="flex-1 flex flex-col min-w-0">
          <!-- Video Player -->
          <div class="shrink-0 mb-6">
             <VideoPlayer :src="lesson.video_url" :provider="lesson.video_provider" />
          </div>

          <!-- Lesson Info & Tabs -->
          <div class="flex-1 flex flex-col bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-sm">
             <!-- Tabs Header -->
             <div class="flex border-b border-neutral-200">
                <button 
                  v-for="tab in tabs" 
                  :key="tab"
                  @click="activeTab = tab"
                  :class="[
                    'px-6 py-4 text-sm font-medium border-b-2 transition-colors',
                    activeTab === tab 
                      ? 'border-primary-600 text-primary-700' 
                      : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:bg-neutral-50'
                  ]"
                >
                  {{ tab }}
                </button>
             </div>

             <!-- Tab Content -->
             <div class="flex-1 overflow-y-auto p-6">
                <!-- Overview -->
                <div v-if="activeTab === 'Overview'">
                   <h1 class="text-2xl font-serif font-bold text-neutral-900 mb-2">{{ lesson.title }}</h1>
                   <p class="text-neutral-600 leading-relaxed max-w-prose">
                     {{ lesson.description }}
                   </p>
                   
                   <div class="mt-8 flex items-center gap-4">
                      <Button v-if="lesson.prev_lesson_id" variant="secondary" :href="route('lessons.show', { course: course.id, lesson: lesson.prev_lesson_id })">
                        Previous Lesson
                      </Button>
                      <Button v-if="lesson.next_lesson_id" variant="primary" :href="route('lessons.show', { course: course.id, lesson: lesson.next_lesson_id })">
                        Next Lesson
                      </Button>
                   </div>
                </div>

                <!-- Transcript -->
                <div v-if="activeTab === 'Transcript'" class="prose prose-sm max-w-none text-neutral-600 font-serif leading-loose">
                   <p class="whitespace-pre-line">{{ lesson.transcript }}</p>
                </div>

                <!-- Notes (Coming Soon) -->
                <div v-if="activeTab === 'Notes'" class="flex items-center justify-center h-full text-neutral-400 italic">
                   Notes feature coming in Phase 2
                </div>
             </div>
          </div>
       </div>

       <!-- Playlist Sidebar -->
       <div class="w-full lg:w-96 flex flex-col shrink-0 bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-sm h-full max-h-[600px] lg:max-h-none">
          <div class="p-4 border-b border-neutral-100 bg-neutral-50">
             <h3 class="font-bold text-neutral-900">{{ course.title }}</h3>
             <p class="text-xs text-neutral-500 mt-1">Course Content</p>
          </div>

          <div class="overflow-y-auto flex-1 divide-y divide-neutral-100">
             <Link 
               v-for="(item, index) in playlist" 
               :key="item.id"
               :href="route('lessons.show', { course: course.id, lesson: item.id })"
               :class="[
                 'p-4 flex gap-3 hover:bg-neutral-50 transition-colors group',
                 item.is_current ? 'bg-primary-50 hover:bg-primary-50' : ''
               ]"
             >
                <div class="py-1">
                   <div :class="[
                      'w-5 h-5 rounded-full border flex items-center justify-center',
                      item.is_completed ? 'bg-emerald-500 border-emerald-500 text-white' : 
                      item.is_current ? 'border-primary-500 text-primary-600' : 'border-neutral-300 text-transparent'
                   ]">
                      <Check v-if="item.is_completed" class="w-3 h-3" />
                      <div v-if="item.is_current && !item.is_completed" class="w-2 h-2 rounded-full bg-primary-500"></div>
                   </div>
                </div>
                
                <div class="flex-1">
                   <div :class="[
                     'text-sm font-medium mb-0.5',
                     item.is_current ? 'text-primary-800' : 'text-neutral-700'
                   ]">
                     {{ index + 1 }}. {{ item.title }}
                   </div>
                   <div class="text-xs text-neutral-400 flex items-center gap-2">
                      <Play class="w-3 h-3" /> {{ item.duration }}
                   </div>
                </div>
             </Link>
          </div>
       </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import VideoPlayer from '@/Components/Course/VideoPlayer.vue';
import Button from '@/Components/Common/Button.vue';
import { Check, Play } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';

defineProps({
    course: Object,
    lesson: Object,
    playlist: Array
});

const tabs = ['Overview', 'Transcript', 'Notes'];
const activeTab = ref('Overview');
</script>

<template>
  <AppShell>
    <!-- Welcome Hero -->
    <div class="mb-8">
      <h1 class="font-serif text-3xl font-bold text-primary-900 mb-2">
        Welcome back, {{ userName }}
      </h1>
      <p class="text-neutral-600">
        "The best of deeds is that which is done consistently, even if it is small." — Prophet Muhammad (ﷺ)
      </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Left Column: Main Progress -->
      <div class="lg:col-span-2 space-y-8">
        <!-- Continue Learning Card -->
        <Card hoverable class="border-l-4 border-l-primary-900">
          <div class="flex flex-col sm:flex-row gap-6">
            <!-- Course Image/Thumbnail -->
            <div class="w-full sm:w-48 aspect-video bg-gradient-to-br from-primary-800 to-primary-950 rounded-lg overflow-hidden shrink-0 relative">
               <img :src="continue_learning.image" alt="Course Thumbnail" class="w-full h-full object-cover" />
               <div class="absolute inset-0 bg-black/10"></div>
               <div class="absolute inset-0 flex items-center justify-center">
                 <div class="bg-white/90 rounded-full p-2 shadow-sm backdrop-blur-sm">
                    <Play class="w-6 h-6 text-primary-900 ml-1" />
                 </div>
               </div>
            </div>

            <div class="flex-1 py-1">
              <div class="flex items-center justify-between mb-2">
                <Badge variant="primary">In Progress</Badge>
                <span class="text-sm font-medium text-primary-900">{{ continue_learning.progress }}% Complete</span>
              </div>
              
              <h3 class="text-xl font-serif font-bold text-neutral-900 mb-1">
                {{ continue_learning.title }}
              </h3>
              <p class="text-sm text-neutral-500 mb-4">
                Next: <span class="font-medium text-neutral-700">{{ continue_learning.current_lesson }}</span>
              </p>

              <!-- Progress Bar -->
              <div class="w-full bg-neutral-100 rounded-full h-2 mb-4">
                <div 
                  class="bg-primary-900 h-2 rounded-full transition-all duration-500 ease-out" 
                  :style="{ width: continue_learning.progress + '%' }"
                ></div>
              </div>

              <div class="flex">
                <Button size="sm" variant="primary">
                  Resume Lesson
                </Button>
              </div>
            </div>
          </div>
        </Card>

        <!-- Recent Activity Section -->
        <div>
           <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-bold text-neutral-900">Recent Activity</h2>
              <Link href="#" class="text-sm font-medium text-primary-900 hover:text-primary-700">View All</Link>
           </div>
           
           <div class="bg-white rounded-xl border border-neutral-100 shadow-sm divide-y divide-neutral-100">
              <div v-for="activity in recent_activity" :key="activity.id" class="p-4 flex items-center gap-4 hover:bg-neutral-50 transition-colors">
                 <div :class="[
                    'h-10 w-10 rounded-full flex items-center justify-center',
                    activity.type === 'lesson' ? 'bg-primary-50 text-primary-900' : 'bg-secondary-50 text-secondary-700'
                 ]">
                    <PlayCircle v-if="activity.type === 'lesson'" class="w-5 h-5" />
                    <CheckCircle v-else class="w-5 h-5" />
                 </div>
                 
                 <div class="flex-1">
                    <h4 class="text-sm font-medium text-neutral-900">{{ activity.title }}</h4>
                    <p class="text-xs text-neutral-500" v-if="activity.course">{{ activity.course }}</p>
                 </div>
                 
                 <span class="text-xs text-neutral-400">{{ activity.completed_at }}</span>
              </div>
           </div>
        </div>
      </div>

      <!-- Right Column: Stats & Quick Actions -->
      <div class="space-y-6">
         <!-- Stats Grid -->
         <div class="grid grid-cols-2 gap-4">
            <Card class="bg-primary-50 border-primary-100" noPadding>
                <div class="p-4 text-center">
                    <div class="text-3xl font-serif font-bold text-primary-900 mb-1">{{ stats.lessons_watched }}</div>
                    <div class="text-xs font-medium text-primary-700 uppercase tracking-wide">Lessons</div>
                </div>
            </Card>
            <Card class="bg-secondary-50 border-secondary-200" noPadding>
                <div class="p-4 text-center">
                    <div class="text-3xl font-serif font-bold text-secondary-700 mb-1">{{ stats.current_streak }}</div>
                    <div class="text-xs font-medium text-secondary-600 uppercase tracking-wide">Day Streak</div>
                </div>
            </Card>
         </div>

         <!-- Quick Habit Check (Mini) -->
         <Card>
            <template #header>
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-neutral-900">Today's Sunnah</h3>
                    <span class="text-xs text-neutral-500">{{ new Date().toLocaleDateString() }}</span>
                </div>
            </template>
            
            <div class="space-y-3">
                <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-neutral-50 cursor-pointer group">
                    <input type="checkbox" class="w-5 h-5 rounded border-neutral-300 text-primary-900 focus:ring-primary-300" />
                    <span class="text-sm text-neutral-700 group-hover:text-neutral-900">Read Surah Kahf</span>
                </label>
                <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-neutral-50 cursor-pointer group">
                    <input type="checkbox" class="w-5 h-5 rounded border-neutral-300 text-primary-900 focus:ring-primary-300" />
                    <span class="text-sm text-neutral-700 group-hover:text-neutral-900">Morning Adhkar</span>
                </label>
            </div>
            
            <template #footer>
                <Button variant="ghost" size="sm" fullWidth href="/habits">View All Habits</Button>
            </template>
         </Card>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/Common/Card.vue';
import Button from '@/Components/Common/Button.vue';
import Badge from '@/Components/Common/Badge.vue';
import { Play, PlayCircle, CheckCircle } from 'lucide-vue-next';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

defineProps({
    stats: Object,
    recent_activity: Array,
    continue_learning: Object,
});

// Get first name from authenticated user
const userName = computed(() => {
    const fullName = page.props.auth?.user?.name || 'Guest';
    return fullName.split(' ')[0];
});
</script>

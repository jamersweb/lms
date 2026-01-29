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
        <Card v-if="continue_learning" hoverable class="border-l-4 border-l-primary-900">
          <div class="flex flex-col sm:flex-row gap-6">
            <!-- Course Image/Thumbnail -->
            <div class="w-full sm:w-48 aspect-video bg-gradient-to-br from-primary-800 to-primary-950 rounded-lg overflow-hidden shrink-0 relative cursor-pointer" @click="goToLesson">
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
                {{ continue_learning.course_title }}
              </h3>
              <p class="text-sm text-neutral-500 mb-4">
                Next: <span class="font-medium text-neutral-700">{{ continue_learning.lesson_title }}</span>
              </p>

              <!-- Progress Bar -->
              <div class="w-full bg-neutral-100 rounded-full h-2 mb-4">
                <div
                  class="bg-primary-900 h-2 rounded-full transition-all duration-500 ease-out"
                  :style="{ width: continue_learning.progress + '%' }"
                ></div>
              </div>

              <div class="flex">
                <Button size="sm" variant="primary" @click="goToLesson">
                  Resume Lesson
                </Button>
              </div>
            </div>
          </div>
        </Card>

        <!-- Latest Notes Section -->
        <div v-if="latest_notes && latest_notes.length > 0">
           <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-bold text-neutral-900">Latest Notes</h2>
              <Link :href="route('notes.index')" class="text-sm font-medium text-primary-900 hover:text-primary-700">View All</Link>
           </div>

           <div class="bg-white rounded-xl border border-neutral-100 shadow-sm divide-y divide-neutral-100">
              <Link
                v-for="note in latest_notes"
                :key="note.id"
                :href="route('notes.index')"
                class="p-4 flex items-start gap-4 hover:bg-neutral-50 transition-colors block"
              >
                 <div class="h-10 w-10 rounded-full bg-primary-50 text-primary-900 flex items-center justify-center shrink-0">
                    <FileText class="w-5 h-5" />
                 </div>

                 <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-medium text-neutral-900 mb-1">{{ note.title }}</h4>
                    <p class="text-xs text-neutral-500 mb-1">{{ note.preview }}</p>
                    <div class="flex items-center gap-2 text-xs text-neutral-400">
                      <Tag class="w-3 h-3" />
                      <span>{{ note.scope }}</span>
                      <span v-if="note.related">• {{ note.related }}</span>
                    </div>
                 </div>

                 <span class="text-xs text-neutral-400 shrink-0">{{ note.created_at }}</span>
              </Link>
           </div>
        </div>

        <!-- Community Posts Section -->
        <div v-if="latest_community_posts && latest_community_posts.length > 0">
           <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-bold text-neutral-900">Community Posts</h2>
              <Link href="#" class="text-sm font-medium text-primary-900 hover:text-primary-700">View All</Link>
           </div>

           <div class="space-y-4">
              <Card
                v-for="post in latest_community_posts"
                :key="post.id"
                hoverable
                class="cursor-pointer"
                @click="goToDiscussion(post.id)"
              >
                <div class="flex items-start gap-4">
                   <div class="h-10 w-10 rounded-full bg-secondary-50 text-secondary-700 flex items-center justify-center shrink-0">
                      <MessageCircle class="w-5 h-5" />
                   </div>

                   <div class="flex-1 min-w-0">
                      <h4 class="text-sm font-medium text-neutral-900 mb-1">{{ post.title }}</h4>
                      <p class="text-xs text-neutral-500 mb-2 line-clamp-2">{{ post.body }}</p>
                      <div class="flex items-center gap-3 text-xs text-neutral-400">
                         <span>{{ post.author }}</span>
                         <span v-if="post.related">• {{ post.related }}</span>
                         <span>• {{ post.replies_count }} {{ post.replies_count === 1 ? 'reply' : 'replies' }}</span>
                      </div>
                   </div>

                   <span class="text-xs text-neutral-400 shrink-0">{{ post.created_at }}</span>
                </div>
              </Card>
           </div>
        </div>

        <!-- Recent Activity Section -->
        <div v-if="recent_activity && recent_activity.length > 0">
           <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-bold text-neutral-900">Recent Activity</h2>
              <Link href="#" class="text-sm font-medium text-primary-900 hover:text-primary-700">View All</Link>
           </div>

           <div class="bg-white rounded-xl border border-neutral-100 shadow-sm divide-y divide-neutral-100">
              <div
                v-for="activity in recent_activity"
                :key="`${activity.type}-${activity.id}`"
                class="p-4 flex items-center gap-4 hover:bg-neutral-50 transition-colors cursor-pointer"
                @click="handleActivityClick(activity)"
              >
                 <div :class="[
                    'h-10 w-10 rounded-full flex items-center justify-center shrink-0',
                    activity.type === 'lesson_completed' ? 'bg-primary-50 text-primary-900' :
                    activity.type === 'note_created' ? 'bg-emerald-50 text-emerald-700' :
                    'bg-secondary-50 text-secondary-700'
                 ]">
                    <PlayCircle v-if="activity.type === 'lesson_completed'" class="w-5 h-5" />
                    <FileText v-else-if="activity.type === 'note_created'" class="w-5 h-5" />
                    <MessageCircle v-else class="w-5 h-5" />
                 </div>

                 <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-medium text-neutral-900">{{ activity.title }}</h4>
                    <p class="text-xs text-neutral-500" v-if="activity.related || activity.course">
                      {{ activity.related || activity.course }}
                    </p>
                 </div>

                 <span class="text-xs text-neutral-400 shrink-0">{{ activity.time }}</span>
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

         <!-- Points Card -->
         <Card class="bg-gradient-to-br from-primary-600 to-primary-800 text-white border-0" noPadding>
            <div class="p-6 text-center">
                <div class="text-4xl font-serif font-bold mb-2">{{ stats.total_points || 0 }}</div>
                <div class="text-sm font-medium opacity-90 uppercase tracking-wide">Total Points</div>
                <div class="mt-4 pt-4 border-t border-white/20">
                    <Link :href="route('leaderboard.index')" class="text-xs font-medium opacity-80 hover:opacity-100 transition-opacity">
                        View Leaderboard →
                    </Link>
                </div>
            </div>
         </Card>

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
                <Button variant="ghost" size="sm" fullWidth :href="route('habits.index')">View All Habits</Button>
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
import { Play, PlayCircle, FileText, MessageCircle, Tag } from 'lucide-vue-next';
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';

const page = usePage();

const props = defineProps({
    stats: Object,
    recent_activity: Array,
    continue_learning: Object,
    latest_notes: Array,
    latest_community_posts: Array,
});

// Get first name from authenticated user
const userName = computed(() => {
    const fullName = page.props.auth?.user?.name || 'Guest';
    return fullName.split(' ')[0];
});

function goToLesson() {
    if (!props.continue_learning || !props.continue_learning.lesson_id) {
        // If no lesson, go to course
        if (props.continue_learning?.course_id) {
            router.visit(route('courses.show', props.continue_learning.course_id));
        }
        return;
    }

    router.visit(route('lessons.show', {
        course: props.continue_learning.course_id,
        lesson: props.continue_learning.lesson_id
    }));
}

function goToDiscussion(discussionId) {
    router.visit(route('discussions.show', discussionId));
}

function handleActivityClick(activity) {
    if (activity.type === 'lesson_completed' && activity.lesson_id && activity.course_id) {
        router.visit(route('lessons.show', {
            course: activity.course_id,
            lesson: activity.lesson_id
        }));
    } else if (activity.type === 'note_created') {
        router.visit(route('notes.index'));
    } else if (activity.type === 'discussion_created') {
        router.visit(route('discussions.show', activity.id));
    }
}
</script>

<template>
  <AppShell>
    <!-- Welcome Hero (uses primary brand color) -->
    <div class="mb-8 relative overflow-hidden rounded-2xl bg-primary-900 p-8 text-white shadow-lg">
      <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PHBhdGggZD0iTTM2IDM0YzAtMi4yMDktMS43OTEtNC00LTRzLTQgMS43OTEtNCA0IDEuNzkxIDQgNCA0IDQtMS43OTEgNC00eiIvPjwvZz48L2c+PC9zdmc+')] opacity-20"></div>
      <div class="relative z-10">
        <h1
          class="font-serif text-4xl font-bold mb-2 drop-shadow-sm"
          :class="$page?.props?.locale === 'ur' ? 'text-right' : ''"
        >
          {{ t('dashboard.welcome', { name: userName }) }}
        </h1>
        <p
          class="text-white/90 text-lg"
          :class="$page?.props?.locale === 'ur' ? 'text-right' : ''"
        >
          {{ t('dashboard.quote') }}
        </p>
      </div>
    </div>

    <!-- Top KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <!-- Watched Lessons (solid – override Card default bg) -->
      <Card class="relative overflow-hidden !bg-primary-600 border-2 border-primary-400 hover:border-primary-300 hover:shadow-xl hover:scale-105 transition-all duration-300 group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
        <div class="relative z-10 flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-white/90 uppercase tracking-wide mb-1">
              {{ t('dashboard.cards.watched_label') }}
            </p>
            <p class="text-4xl font-serif font-bold text-white drop-shadow-sm">{{ stats.watched_lessons }}</p>
            <p class="text-xs text-white/80 mt-1">
              {{ t('dashboard.cards.watched_sub', { total: stats.total_lessons }) }}
            </p>
          </div>
          <div class="h-14 w-14 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center group-hover:bg-white/30 transition-colors shadow-lg">
            <CheckCircle class="w-7 h-7 text-white" />
          </div>
        </div>
      </Card>

      <!-- Remaining Lessons -->
      <Card class="relative overflow-hidden bg-white border-2 border-primary-200 hover:border-primary-400 hover:shadow-xl hover:scale-105 transition-all duration-300 group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-primary-100 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
        <div class="relative z-10 flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-primary-700 uppercase tracking-wide mb-1">
              {{ t('dashboard.cards.remaining_label') }}
            </p>
            <p class="text-4xl font-serif font-bold text-primary-900">{{ stats.remaining_lessons }}</p>
            <p class="text-xs text-primary-600 mt-1">
              {{ t('dashboard.cards.remaining_sub') }}
            </p>
          </div>
          <div class="h-14 w-14 rounded-full bg-primary-200 flex items-center justify-center group-hover:bg-primary-300 transition-all shadow-md">
            <BookOpen class="w-7 h-7 text-primary-700" />
          </div>
        </div>
      </Card>

      <!-- Total Watch Time (solid gold – override Card’s default bg) -->
      <Card class="relative overflow-hidden !bg-secondary-500 border-2 border-secondary-300 hover:border-secondary-200 hover:shadow-xl hover:scale-105 transition-all duration-300 group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/20 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
        <div class="relative z-10 flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-white/90 uppercase tracking-wide mb-1">
              {{ t('dashboard.cards.watch_time_label') }}
            </p>
            <p class="text-4xl font-serif font-bold text-white drop-shadow-sm">{{ stats.total_watch_time_formatted }}</p>
            <p class="text-xs text-white/80 mt-1">
              {{ t('dashboard.cards.watch_time_sub') }}
            </p>
          </div>
          <div class="h-14 w-14 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center group-hover:bg-white/30 transition-colors shadow-lg">
            <Clock class="w-7 h-7 text-white" />
          </div>
        </div>
      </Card>

      <!-- Current Streak (solid – override Card default bg) -->
      <Card class="relative overflow-hidden !bg-primary-800 text-white border-2 border-primary-500 hover:border-primary-400 hover:shadow-2xl hover:scale-105 transition-all duration-300 group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-secondary-500/20 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
        <div class="relative z-10 flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-white/90 uppercase tracking-wide mb-1">
              {{ t('dashboard.cards.streak_label') }}
            </p>
            <p class="text-4xl font-serif font-bold text-white drop-shadow-sm">{{ stats.current_streak }}</p>
            <p class="text-xs text-white/80 mt-1">
              {{ stats.current_streak === 1 ? t('dashboard.cards.streak_sub_singular') : t('dashboard.cards.streak_sub_plural') }}
            </p>
          </div>
          <div class="h-14 w-14 rounded-full bg-secondary-500 flex items-center justify-center group-hover:bg-secondary-400 transition-all shadow-lg animate-pulse">
            <Flame class="w-7 h-7 text-white" />
          </div>
        </div>
      </Card>
    </div>

    <!-- Quiz Stats Row (if any quizzes exist) -->
    <div v-if="stats.total_quizzes_available > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
      <Card class="border-2 border-emerald-200 bg-emerald-50 hover:shadow-lg transition-all">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-emerald-700 uppercase tracking-wide mb-1">
              {{ t('dashboard.cards.quizzes_completed_label') }}
            </p>
            <p class="text-3xl font-serif font-bold text-emerald-900">{{ stats.completed_quizzes || 0 }}</p>
            <p class="text-xs text-emerald-600 mt-1">
              {{ t('dashboard.cards.quizzes_completed_sub', { total: stats.total_quizzes_available }) }}
            </p>
          </div>
          <div class="h-12 w-12 rounded-full bg-emerald-100 flex items-center justify-center">
            <CheckCircle class="w-6 h-6 text-emerald-700" />
          </div>
        </div>
      </Card>
      <Card class="border-2 border-amber-200 bg-amber-50 hover:shadow-lg transition-all">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-amber-700 uppercase tracking-wide mb-1">
              {{ t('dashboard.cards.quizzes_remaining_label') }}
            </p>
            <p class="text-3xl font-serif font-bold text-amber-900">{{ stats.remaining_quizzes || 0 }}</p>
            <p class="text-xs text-amber-600 mt-1">
              {{ t('dashboard.cards.quizzes_remaining_sub') }}
            </p>
          </div>
          <div class="h-12 w-12 rounded-full bg-amber-100 flex items-center justify-center">
            <BookOpen class="w-6 h-6 text-amber-700" />
          </div>
        </div>
      </Card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Left Column: Main Content (SVG pattern on left) -->
      <div class="lg:col-span-2 space-y-8 relative">
        <div
          class="absolute inset-y-0 left-0 w-full max-w-lg bg-repeat-y bg-left bg-[length:80px_80px] opacity-20 pointer-events-none hidden lg:block"
          :style="{ backgroundImage: `url('/images/download.svg')` }"
          aria-hidden="true"
        />
        <div class="relative z-10 space-y-8">
        <!-- Continue Learning Card -->
        <Card v-if="continue_learning" hoverable class="border-l-4 border-l-primary-600 bg-primary-50 shadow-lg hover:shadow-xl transition-all duration-300 group">
          <div class="flex flex-col sm:flex-row gap-6">
            <!-- Course Image/Thumbnail -->
            <div class="w-full sm:w-48 aspect-video bg-primary-800 rounded-lg overflow-hidden shrink-0 relative cursor-pointer group" @click="goToLesson">
               <img :src="continue_learning.image" alt="Course Thumbnail" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
               <div class="absolute inset-0 bg-black/10 group-hover:bg-black/5 transition-colors"></div>
               <div class="absolute inset-0 flex items-center justify-center">
                 <div class="bg-white/90 rounded-full p-2 shadow-sm backdrop-blur-sm group-hover:scale-110 transition-transform">
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

              <!-- Video Progress Bar (if available) -->
              <div v-if="continue_learning.video_progress && continue_learning.video_progress.percent_complete > 0" class="mb-2">
                <div class="flex items-center justify-between text-xs text-neutral-600 mb-1">
                  <span>Video Progress</span>
                  <span>{{ Math.round(continue_learning.video_progress.percent_complete) }}%</span>
                </div>
                <div class="w-full bg-neutral-200 rounded-full h-2 overflow-hidden">
                  <div
                    class="bg-primary-600 h-2 rounded-full transition-all duration-300"
                    :style="{ width: Math.min(continue_learning.video_progress.percent_complete, 100) + '%' }"
                  ></div>
                </div>
                <p class="text-xs text-neutral-500 mt-1">
                  Resume from {{ formatTime(continue_learning.video_progress.last_position_seconds) }}
                </p>
              </div>

              <!-- Course Progress Bar -->
              <div class="w-full bg-neutral-100 rounded-full h-2 mb-4 overflow-hidden">
                <div
                  class="bg-primary-900 h-2 rounded-full transition-all duration-500 ease-out"
                  :style="{ width: continue_learning.progress + '%' }"
                ></div>
              </div>

              <div class="flex">
                <Button size="sm" variant="primary" @click="goToLesson" class="hover:scale-105 transition-transform">
                  {{ continue_learning.video_progress && continue_learning.video_progress.percent_complete > 0 ? 'Resume Lesson' : 'Continue Learning' }}
                </Button>
              </div>
            </div>
          </div>
        </Card>

        <!-- Continue Watching Widget -->
        <Card v-if="continue_watching && continue_watching.length > 0" class="shadow-lg border-t-4 border-t-primary-600 bg-primary-50">
          <template #header>
            <div class="flex items-center justify-between pb-2 border-b border-primary-100">
              <h2 class="text-lg font-bold text-primary-900 flex items-center gap-2">
                <div class="h-1 w-1 rounded-full bg-primary-600 animate-pulse"></div>
                {{ t('dashboard.sections.continue_watching_title') }}
              </h2>
              <Link :href="route('courses.index')" class="text-sm font-medium text-primary-600 hover:text-primary-800 transition-colors font-semibold">
                {{ t('dashboard.sections.continue_watching_view_all') }}
              </Link>
            </div>
          </template>

          <div class="space-y-3">
            <div
              v-for="lesson in continue_watching"
              :key="lesson.lesson_id"
              class="p-4 rounded-xl border-2 border-primary-100 hover:border-primary-400 hover:bg-primary-50 transition-all duration-200 cursor-pointer group shadow-sm hover:shadow-md"
              @click="goToLessonFromContinue(lesson)"
            >
              <div class="flex items-start gap-4">
                <div class="flex-1 min-w-0">
                  <h4 class="text-sm font-semibold text-neutral-900 mb-1 group-hover:text-primary-900 transition-colors">
                    {{ lesson.lesson_title }}
                  </h4>
                  <p class="text-xs text-neutral-500 mb-2">{{ lesson.course_title }}</p>
                  
                  <!-- Progress Bar -->
                  <div class="w-full bg-neutral-200 rounded-full h-2 mb-2 overflow-hidden shadow-inner">
                    <div
                      class="bg-primary-600 h-2 rounded-full transition-all duration-500 shadow-sm"
                      :style="{ width: Math.min(lesson.percent_complete, 100) + '%' }"
                    ></div>
                  </div>
                  
                  <div class="flex items-center justify-between text-xs text-neutral-500">
                    <span>{{ Math.round(lesson.percent_complete) }}% complete</span>
                    <span>{{ lesson.last_watched_at }}</span>
                  </div>
                </div>
                <Button size="sm" variant="ghost" class="shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                  {{ t('dashboard.sections.continue_learning_resume') }}
                </Button>
              </div>
            </div>
          </div>
        </Card>

        <!-- Remaining Quizzes (lesson completed, quiz not taken) -->
        <Card v-if="remaining_quizzes_list && remaining_quizzes_list.length > 0" class="shadow-lg border-t-4 border-t-amber-500 bg-amber-50">
          <template #header>
            <div class="flex items-center justify-between pb-2 border-b border-amber-100">
              <h2 class="text-lg font-bold text-amber-900 flex items-center gap-2">
                <div class="h-1 w-1 rounded-full bg-amber-500 animate-pulse"></div>
                {{ t('dashboard.sections.quizzes_to_complete_title') }}
              </h2>
            </div>
          </template>
          <div class="space-y-3">
            <div
              v-for="item in remaining_quizzes_list"
              :key="item.lesson_id"
              class="p-4 rounded-xl border-2 border-amber-100 hover:border-amber-300 hover:bg-amber-50/50 transition-all cursor-pointer group"
              @click="goToLessonQuiz(item)"
            >
              <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                  <h4 class="text-sm font-semibold text-neutral-900 group-hover:text-amber-900">
                    {{ item.lesson_title }}
                  </h4>
                  <p class="text-xs text-neutral-500 mt-0.5">{{ item.course_title }}</p>
                </div>
                <span class="text-xs font-medium text-amber-700 shrink-0">
                  {{ t('dashboard.sections.quizzes_to_complete_cta') }}
                </span>
              </div>
            </div>
          </div>
        </Card>

        <!-- Course Milestones -->
        <Card v-if="course_milestones && course_milestones.length > 0" class="shadow-lg border-t-4 border-t-primary-500 bg-primary-50">
          <template #header>
            <div class="flex items-center justify-between pb-2 border-b border-primary-100">
              <h2 class="text-lg font-bold text-primary-900 flex items-center gap-2">
                <div class="h-1 w-1 rounded-full bg-primary-600 animate-pulse"></div>
                Course Milestones
              </h2>
            </div>
          </template>
          <div class="space-y-3">
            <div
              v-for="course in course_milestones"
              :key="course.course_id"
              class="p-4 rounded-xl border-2 border-primary-100 hover:border-primary-400 hover:bg-primary-50/40 transition-all cursor-pointer"
              @click="goToCourse(course)"
            >
              <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                  <h4 class="text-sm font-semibold text-neutral-900 mb-1">
                    {{ course.course_title }}
                  </h4>
                  <p class="text-xs text-primary-700 font-medium">
                    {{ course.label }} • {{ course.progress }}%
                  </p>
                </div>
                <div class="h-10 w-10 rounded-full bg-primary-600 text-white flex items-center justify-center text-xs font-semibold">
                  {{ course.progress }}%
                </div>
              </div>
            </div>
          </div>
        </Card>

        <!-- My Notes Widget -->
        <Card class="shadow-lg border-t-4 border-t-secondary-500 bg-secondary-50">
          <template #header>
            <div class="flex items-center justify-between pb-2 border-b border-secondary-100">
              <h2 class="text-lg font-bold text-secondary-900 flex items-center gap-2">
                <div class="h-1 w-1 rounded-full bg-secondary-500 animate-pulse"></div>
                My Notes
              </h2>
              <div class="flex items-center gap-2">
                <Button size="sm" variant="ghost" @click="showQuickNoteModal = true" class="text-secondary-700 hover:text-secondary-900 hover:bg-secondary-100">
                  <Plus class="w-4 h-4 mr-1" />
                  Quick Note
                </Button>
                <Link :href="route('notes.index')" class="text-sm font-medium text-secondary-700 hover:text-secondary-900 transition-colors font-semibold">View All →</Link>
              </div>
            </div>
          </template>

          <div v-if="recent_notes && recent_notes.length > 0" class="space-y-3">
            <div
              v-for="note in recent_notes"
              :key="note.id"
              class="p-4 rounded-xl border-2 border-secondary-100 hover:border-secondary-400 hover:bg-secondary-50 transition-all duration-200 cursor-pointer group shadow-sm hover:shadow-md"
              @click="goToNote(note)"
            >
              <div class="flex items-start gap-3">
                <div class="h-12 w-12 rounded-full bg-secondary-500 text-white flex items-center justify-center shrink-0 group-hover:bg-secondary-400 transition-all shadow-md">
                  <FileText class="w-6 h-6" />
                </div>
                <div class="flex-1 min-w-0">
                  <h4 class="text-sm font-medium text-neutral-900 mb-1 group-hover:text-primary-900 transition-colors">
                    {{ note.title || 'Untitled Note' }}
                  </h4>
                  <p class="text-xs text-neutral-500 mb-1 line-clamp-2">{{ note.preview }}</p>
                  <div class="flex items-center gap-2 text-xs text-neutral-400">
                    <span v-if="note.related">{{ note.related }}</span>
                    <span v-if="note.related">•</span>
                    <span>{{ note.updated_at }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-neutral-500">
            <FileText class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p class="text-sm">No notes yet</p>
            <Button size="sm" variant="ghost" @click="showQuickNoteModal = true" class="mt-2">
              Create your first note
            </Button>
          </div>
        </Card>

        <!-- Community Posts Section -->
        <div v-if="latest_community_posts && latest_community_posts.length > 0">
           <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-bold text-neutral-900">Community Posts</h2>
              <Link href="#" class="text-sm font-medium text-primary-900 hover:text-primary-700 transition-colors">View All</Link>
           </div>

           <div class="space-y-4">
              <Card
                v-for="post in latest_community_posts"
                :key="post.id"
                hoverable
                class="cursor-pointer shadow-sm hover:shadow-md transition-all duration-200"
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
        </div>
      </div>

      <!-- Right Column: Stats & Quick Actions -->
      <div class="space-y-6">
         <!-- Learning Habits / Streak Widget -->
         <Card v-if="watch_time" class="shadow-lg bg-primary-50 border-2 border-primary-200">
            <template #header>
            <div class="flex items-center justify-between pb-2 border-b border-primary-200">
                <h3 class="font-bold text-primary-900 flex items-center gap-2">
                  <div class="h-1 w-1 rounded-full bg-primary-600 animate-pulse"></div>
                  Daily Goal
                </h3>
                    <Flame v-if="streak?.badge" :class="[
                      'w-6 h-6',
                      streak.badge.color === 'secondary' ? 'text-secondary-600' : 'text-primary-600'
                    ]" />
                </div>
            </template>

            <div class="space-y-4">
              <!-- Daily Goal Progress -->
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium text-neutral-700">Watch {{ watch_time?.daily_goal_minutes ?? 0 }} min/day</span>
                  <span class="text-xs font-semibold text-primary-900">{{ Math.round(watch_time?.daily_goal_progress ?? 0) }}%</span>
                </div>
                <div class="w-full bg-neutral-200 rounded-full h-4 overflow-hidden shadow-inner">
                  <div
                    class="bg-primary-600 h-4 rounded-full transition-all duration-700 shadow-sm"
                    :style="{ width: Math.min(watch_time?.daily_goal_progress ?? 0, 100) + '%' }"
                  ></div>
                </div>
                <p class="text-xs text-neutral-500 mt-1">
                  {{ watch_time?.today_minutes ?? 0 }} / {{ watch_time?.daily_goal_minutes ?? 0 }} minutes today
                </p>
              </div>

              <!-- Streak Badge -->
              <div v-if="streak?.badge" class="pt-4 border-t border-neutral-200">
                <div class="flex items-center gap-3">
                  <div :class="[
                    'h-12 w-12 rounded-full flex items-center justify-center',
                    streak.badge.color === 'secondary' 
                      ? 'bg-secondary-500' 
                      : 'bg-primary-700'
                  ]">
                    <Flame class="w-6 h-6 text-white" />
                  </div>
                  <div>
                    <p class="text-xs font-medium text-neutral-600 uppercase tracking-wide">{{ streak.badge.label }}</p>
                    <p class="text-lg font-serif font-bold text-neutral-900">{{ streak?.days ?? 0 }} day streak</p>
                  </div>
                </div>
              </div>
            </div>
          </Card>

         <!-- Points Card -->
         <Card class="relative overflow-hidden !bg-primary-800 text-white border-2 border-primary-500 shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-300 group" noPadding>
            <div class="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -mr-20 -mt-20 group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10 p-6 text-center">
                <div class="text-5xl font-serif font-bold mb-2 drop-shadow-lg">{{ stats.total_points || 0 }}</div>
                <div class="text-sm font-medium opacity-90 uppercase tracking-wide">Total Points</div>
                <div class="mt-4 pt-4 border-t border-white/20">
                    <Link :href="route('leaderboard.index')" class="text-xs font-semibold opacity-90 hover:opacity-100 transition-opacity inline-flex items-center gap-1">
                        View Leaderboard <span class="group-hover:translate-x-1 transition-transform">→</span>
                    </Link>
                </div>
            </div>
         </Card>

         <!-- Quick Habit Check (Mini) -->
         <Card class="shadow-lg border-t-4 border-t-secondary-400 bg-secondary-50">
            <template #header>
                <div class="flex items-center justify-between pb-2 border-b border-secondary-100">
                    <h3 class="font-bold text-secondary-900 flex items-center gap-2">
                      <div class="h-1 w-1 rounded-full bg-secondary-500 animate-pulse"></div>
                      Today's Sunnah
                    </h3>
                    <span class="text-xs text-secondary-600 font-medium">{{ new Date().toLocaleDateString() }}</span>
                </div>
            </template>

            <div class="space-y-3">
                <label class="flex items-center gap-3 p-3 rounded-xl hover:bg-secondary-50 cursor-pointer group transition-all border-2 border-transparent hover:border-secondary-200 shadow-sm hover:shadow-md">
                    <input type="checkbox" class="w-5 h-5 rounded border-secondary-300 text-secondary-600 focus:ring-secondary-400 focus:ring-2" />
                    <span class="text-sm text-neutral-700 group-hover:text-secondary-900 font-medium">Read Surah Kahf</span>
                </label>
                <label class="flex items-center gap-3 p-3 rounded-xl hover:bg-secondary-50 cursor-pointer group transition-all border-2 border-transparent hover:border-secondary-200 shadow-sm hover:shadow-md">
                    <input type="checkbox" class="w-5 h-5 rounded border-secondary-300 text-secondary-600 focus:ring-secondary-400 focus:ring-2" />
                    <span class="text-sm text-neutral-700 group-hover:text-secondary-900 font-medium">Morning Adhkar</span>
                </label>
            </div>

            <template #footer>
                <Button variant="ghost" size="sm" fullWidth :href="route('habits.index')">View All Habits</Button>
            </template>
         </Card>
      </div>
    </div>

    <!-- Quick Note Modal -->
    <Modal :show="showQuickNoteModal" @close="showQuickNoteModal = false">
      <div class="p-6">
        <h3 class="text-lg font-bold text-neutral-900 mb-4">Quick Note</h3>
        
        <form @submit.prevent="saveQuickNote">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-2">Title</label>
              <input
                v-model="quickNoteForm.title"
                type="text"
                class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                placeholder="Note title (optional)"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-2">Content</label>
              <textarea
                v-model="quickNoteForm.content"
                rows="4"
                class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                placeholder="Write your note here..."
                required
              ></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-2">Attach to Lesson (optional)</label>
              <select
                v-model="quickNoteForm.lesson_id"
                class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              >
                <option :value="null">No lesson</option>
                <option v-for="lesson in availableLessons" :key="lesson.id" :value="lesson.id">
                  {{ lesson.title }} ({{ lesson.course_title }})
                </option>
              </select>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 mt-6">
            <Button type="button" variant="secondary" @click="showQuickNoteModal = false">
              Cancel
            </Button>
            <Button type="submit" :loading="quickNoteForm.processing">
              Save Note
            </Button>
          </div>
        </form>
      </div>
    </Modal>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/Common/Card.vue';
import Button from '@/Components/Common/Button.vue';
import Badge from '@/Components/Common/Badge.vue';
import Modal from '@/Components/Modal.vue';
import { Play, PlayCircle, FileText, MessageCircle, Tag, CheckCircle, BookOpen, Clock, Flame, Plus } from 'lucide-vue-next';
import { Link, usePage, useForm, router } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { route } from 'ziggy-js';
import { useI18n } from '@/i18n';

const page = usePage();
const { t } = useI18n();

const props = defineProps({
    stats: Object,
    continue_watching: {
        type: Array,
        default: () => [],
    },
    watch_time: Object,
    streak: Object,
    recent_notes: {
        type: Array,
        default: () => [],
    },
    continue_learning: Object,
    remaining_quizzes_list: {
        type: Array,
        default: () => [],
    },
    course_milestones: {
        type: Array,
        default: () => [],
    },
    recent_activity: Array,
    latest_community_posts: Array,
});

// Get first name from authenticated user
const userName = computed(() => {
    const fullName = page.props.auth?.user?.name || 'Guest';
    return fullName.split(' ')[0];
});

// Quick Note Modal
const showQuickNoteModal = ref(false);
const quickNoteForm = useForm({
    title: '',
    content: '',
    lesson_id: null,
    scope: 'personal',
});

// Available lessons for quick note (from continue watching + continue learning)
const availableLessons = computed(() => {
    const lessons = [];
    
    if (props.continue_learning?.lesson_id) {
        lessons.push({
            id: props.continue_learning.lesson_id,
            title: props.continue_learning.lesson_title,
            course_title: props.continue_learning.course_title,
        });
    }
    
    props.continue_watching?.forEach(lesson => {
        if (!lessons.find(l => l.id === lesson.lesson_id)) {
            lessons.push({
                id: lesson.lesson_id,
                title: lesson.lesson_title,
                course_title: lesson.course_title,
            });
        }
    });
    
    return lessons;
});

function formatTime(seconds) {
    if (!seconds || seconds < 0) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
}

function goToLesson() {
    if (!props.continue_learning || !props.continue_learning.lesson_id) {
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

function goToLessonFromContinue(lesson) {
    router.visit(route('lessons.show', {
        course: lesson.course_id,
        lesson: lesson.lesson_id
    }));
}

function goToLessonQuiz(item) {
    router.visit(route('lessons.show', {
        course: item.course_id,
        lesson: item.lesson_id
    }));
}

function goToNote(note) {
    router.visit(route('notes.index'));
}

function goToDiscussion(discussionId) {
    router.visit(route('discussions.show', discussionId));
}

function goToCourse(course) {
    router.visit(route('courses.show', course.course_id));
}

function saveQuickNote() {
    const data = {
        title: quickNoteForm.title || 'Untitled Note',
        content: quickNoteForm.content,
    };
    
    if (quickNoteForm.lesson_id) {
        data.noteable_type = 'App\\Models\\Lesson';
        data.noteable_id = quickNoteForm.lesson_id;
    }
    
    quickNoteForm.transform(() => data).post(route('notes.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showQuickNoteModal.value = false;
            quickNoteForm.reset();
            // Reload dashboard to show new note
            router.reload({ only: ['recent_notes'] });
        }
    });
}
</script>

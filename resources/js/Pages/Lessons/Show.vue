<template>
  <AppShell>
    <div class="flex flex-col h-full bg-neutral-50">
        <!-- Main Content Area: Left Sidebar (4/12) + Right Video (8/12). On mobile: video first so it's visible. -->
        <div class="flex flex-col md:flex-row flex-1 min-h-0 overflow-hidden">
          <!-- Left Sidebar: Tabs (4/12) - order-2 on mobile so video shows first -->
          <aside class="w-full md:w-4/12 flex flex-col bg-white border-r border-neutral-200 overflow-hidden order-2 md:order-1">
          <!-- Tabs Header -->
          <div class="flex border-b border-neutral-200 shrink-0 bg-white">
            <button
              v-for="tab in tabs"
              :key="tab"
              @click="activeTab = tab"
              :class="[
                'flex-1 px-4 py-4 text-sm font-medium border-b-2 transition-colors',
                activeTab === tab
                  ? 'border-primary-600 text-primary-700 bg-primary-50/30'
                  : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:bg-neutral-50'
              ]"
            >
              {{ tab }}
            </button>
          </div>

          <!-- Tab Content - Scrollable (extra pb on mobile so content isn't cut off by bottom bar/browser chrome) -->
          <div class="flex-1 overflow-y-auto p-6 pb-[max(7rem,calc(env(safe-area-inset-bottom,0px)+5.5rem))] md:pb-6">
            <div
              v-if="$page.props.errors?.completion"
              class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-xs text-red-700"
            >
              {{ $page.props.errors.completion }}
            </div>
            <!-- Overview -->
            <div v-if="activeTab === 'Overview'">
              <h1 class="text-xl font-serif font-bold text-neutral-900 mb-2">{{ lesson.title }}</h1>
              <p class="text-neutral-600 leading-relaxed text-sm mb-4">
                Part of <span class="font-semibold">{{ course.title }}</span>
              </p>

              <!-- Completion Section -->
              <div v-if="!lesson.is_locked" class="mt-4 p-4 bg-neutral-50 rounded-xl border border-neutral-200">
                <div class="flex items-center justify-between mb-3">
                  <div>
                    <h3 class="font-semibold text-neutral-900 mb-1 text-sm">Lesson Progress</h3>
                    <div v-if="(lesson.progress && lesson.duration_seconds) || (lesson.video_progress && lesson.video_progress.duration_seconds)" class="text-xs text-neutral-600">
                      <span v-if="lesson.video_progress && lesson.video_progress.duration_seconds > 0">
                        Watched: {{ formatTime(lesson.video_progress.last_position_seconds || 0) }} / {{ formatTime(lesson.video_progress.duration_seconds) }}
                      </span>
                      <span v-else-if="lesson.progress && lesson.duration_seconds">
                        Watched: {{ formatTime(lesson.progress.watched_seconds) }} / {{ formatTime(lesson.duration_seconds) }}
                      </span>
                      <span class="mx-2">•</span>
                      <span>
                        {{ Math.round(
                          (lesson.video_progress && lesson.video_progress.duration_seconds > 0)
                            ? (lesson.video_progress.percent_complete || 0)
                            : (lesson.progress && lesson.duration_seconds)
                              ? ((lesson.progress.watched_seconds / lesson.duration_seconds) * 100)
                              : 0
                        ) }}%
                      </span>
                    </div>
                    <div v-else-if="lesson.duration_seconds || lesson.video_duration_seconds" class="text-xs text-neutral-500">
                      Start watching to track progress
                    </div>
                  </div>
                  <div>
                    <Button
                      v-if="!lesson.is_completed"
                      @click="markComplete"
                      :loading="completionForm.processing"
                      variant="primary"
                      size="sm"
                    >
                      Mark Complete
                    </Button>
                    <div v-else class="flex items-center gap-2 text-emerald-700">
                      <Check class="w-4 h-4" />
                      <span class="font-medium text-sm">Completed</span>
                    </div>
                  </div>
                </div>
                <!-- Progress Bar -->
                <div v-if="(lesson.progress && lesson.duration_seconds) || (lesson.video_progress && lesson.video_progress.duration_seconds)" class="mt-3">
                  <div class="w-full bg-neutral-200 rounded-full h-2">
                    <div
                      class="bg-primary-600 h-2 rounded-full transition-all"
                      :style="{ width: `${Math.min(
                        (lesson.video_progress && lesson.video_progress.duration_seconds > 0) 
                          ? (lesson.video_progress.percent_complete || 0)
                          : (lesson.progress && lesson.duration_seconds)
                            ? ((lesson.progress.watched_seconds / lesson.duration_seconds) * 100)
                            : 0
                      , 100)}%` }"
                    ></div>
                  </div>
                </div>
                <div v-if="lesson.progress && lesson.progress.seek_attempts > 0" class="mt-2 text-xs text-amber-700">
                  ⚠ Skipping detected: {{ lesson.progress.seek_attempts }} attempt(s)
                </div>
                <div v-if="lesson.progress && lesson.progress.max_playback_rate > 1.5" class="mt-2 text-xs text-amber-700">
                  ⚠ Speed exceeded: {{ lesson.progress.max_playback_rate }}x
                </div>
              </div>

              <!-- Generic Lesson Objectives -->
              <div class="mt-4 p-4 bg-white rounded-xl border border-neutral-200">
                <h3 class="text-sm font-semibold text-neutral-900 mb-2">
                  {{ t('lessons.overview.objectives_title') }}
                </h3>
                <ul class="list-disc pl-5 space-y-1 text-sm text-neutral-700">
                  <li>{{ t('lessons.overview.objective_1') }}</li>
                  <li>{{ t('lessons.overview.objective_2') }}</li>
                  <li>{{ t('lessons.overview.objective_3') }}</li>
                </ul>
              </div>

              <div class="mt-6 flex flex-col gap-2">
                <Button 
                  v-if="lesson.prev_lesson_id" 
                  variant="secondary" 
                  size="sm"
                  @click="goToLesson(lesson.prev_lesson_id)"
                  class="w-full"
                >
                  Previous Lesson
                </Button>
                <Button 
                  v-if="lesson.next_lesson_id" 
                  variant="primary" 
                  size="sm"
                  @click="goToLesson(lesson.next_lesson_id)"
                  class="w-full"
                >
                  Next Lesson
                </Button>
              </div>
            </div>

            <!-- Dua & Resources -->
            <div v-if="activeTab === 'Dua & Resources'" class="space-y-4 max-w-none text-neutral-700 text-sm">
              <div v-if="resourcesLoading" class="text-center py-8">
                <Loader2 class="w-8 h-8 text-neutral-400 mx-auto mb-3 animate-spin" />
                <p class="text-neutral-500">Loading resources...</p>
              </div>
              <template v-else-if="lessonResources">
                <div v-if="lessonResources.can_view === false" class="bg-amber-50 border border-amber-100 text-amber-800 px-4 py-3 rounded-lg">
                  Complete the lesson to view Sunnah & Dua resources.
                </div>
                <template v-else>
                  <div v-if="lessonResources.sunnah_pointers" class="mb-4">
                    <h3 class="font-semibold text-primary-800 mb-2 flex items-center gap-2">
                      <BookOpen class="w-4 h-4" />
                      Sunnah Pointers
                    </h3>
                    <div class="bg-primary-50 border border-primary-100 rounded-lg p-4">
                      <p class="whitespace-pre-wrap leading-relaxed">{{ lessonResources.sunnah_pointers }}</p>
                    </div>
                  </div>
                  <div v-if="lessonResources.duas_text" class="mb-4">
                    <h3 class="font-semibold text-primary-800 mb-2 flex items-center gap-2">
                      <Heart class="w-4 h-4" />
                      Duas
                    </h3>
                    <div class="bg-amber-50 border border-amber-100 rounded-lg p-4">
                      <p class="whitespace-pre-wrap leading-relaxed text-right" dir="rtl">{{ lessonResources.duas_text }}</p>
                    </div>
                  </div>
                  <div v-if="lessonResources.audio_path" class="mb-4">
                    <h3 class="font-semibold text-primary-800 mb-2 flex items-center gap-2">
                      <Volume2 class="w-4 h-4" />
                      Dua Pronunciation
                    </h3>
                    <audio controls class="w-full rounded-lg">
                      <source :src="lessonResources.audio_path" type="audio/mpeg">
                      Your browser does not support the audio element.
                    </audio>
                  </div>
                  <!-- PDF viewer (shown after lesson complete) -->
                  <div v-if="lessonResources.can_view && (lessonResources.sunnah_pointers || lessonResources.duas_text)" class="mb-4">
                    <h3 class="font-semibold text-primary-800 mb-2 flex items-center gap-2">
                      <FileText class="w-4 h-4" />
                      Sunnah & Dua PDF
                    </h3>
                    <div class="rounded-lg border border-neutral-200 overflow-hidden bg-neutral-100" style="min-height: 420px;">
                      <iframe
                        :src="resourcesPdfViewUrl"
                        title="Sunnah & Dua Resources PDF"
                        class="w-full border-0"
                        style="height: 420px;"
                      />
                    </div>
                    <Button
                      variant="secondary"
                      size="sm"
                      @click="downloadResourcesPdf"
                      :disabled="resourcesPdfDownloading"
                      class="w-full mt-2"
                    >
                      <Download v-if="!resourcesPdfDownloading" class="w-4 h-4 mr-2 inline" />
                      <Loader2 v-else class="w-4 h-4 mr-2 inline animate-spin" />
                      {{ resourcesPdfDownloading ? 'Downloading...' : 'Download PDF' }}
                    </Button>
                  </div>
                  <div v-if="!lessonResources.sunnah_pointers && !lessonResources.duas_text && !lessonResources.audio_path" class="text-center py-6 text-neutral-500">
                    <FileText class="w-12 h-12 mx-auto mb-2 opacity-50" />
                    <p>No resources available for this lesson.</p>
                  </div>
                </template>
              </template>
              <div v-else class="text-center py-6 text-neutral-500">
                <FileText class="w-12 h-12 mx-auto mb-2 opacity-50" />
                <p>No resources available for this lesson.</p>
              </div>
            </div>

            <!-- Reflection -->
            <div v-if="activeTab === t('lessons.tabs.reflection')" class="space-y-4">
                   <div v-if="!lesson.is_completed" class="bg-amber-50 border border-amber-100 text-amber-800 text-sm px-4 py-3 rounded-lg">
                     {{ t('lessons.reflection.complete_first') }}
                   </div>
                   <div v-else-if="!reflection" class="bg-blue-50 border border-blue-100 text-blue-800 text-sm px-4 py-3 rounded-lg">
                     {{ t('lessons.reflection.prompt_submit') }}
                   </div>
                   <div v-else class="bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm px-4 py-3 rounded-lg">
                     {{ t('lessons.reflection.submitted') }}
                   </div>

                   <div>
                     <label class="block text-sm font-medium text-neutral-700 mb-2">
                       {{ t('lessons.reflection.label_takeaway') }} <span class="text-red-500">*</span>
                     </label>
                     <textarea
                       v-model="reflectionForm.takeaway"
                       rows="6"
                       :disabled="!lesson.is_completed"
                       class="w-full rounded-xl border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 p-3 text-sm disabled:bg-neutral-100 disabled:cursor-not-allowed"
                       :placeholder="t('lessons.reflection.placeholder_takeaway')"
                       minlength="30"
                       maxlength="5000"
                     ></textarea>
                     <div class="flex items-center justify-between mt-1">
                       <p v-if="reflectionForm.errors.takeaway" class="text-xs text-red-600">
                         {{ reflectionForm.errors.takeaway }}
                       </p>
                       <p v-else-if="reflectionForm.takeaway.length > 0 && reflectionForm.takeaway.length < 30" class="text-xs text-amber-600">
                         {{ t('lessons.reflection.min_chars') }} ({{ reflectionForm.takeaway.length }}/30)
                       </p>
                       <p v-else class="text-xs text-neutral-500">
                         {{ reflectionForm.takeaway.length }} / 5,000 {{ t('lessons.reflection.characters') }}
                       </p>
                     </div>
                   </div>

                   <div class="flex items-center justify-between">
                     <div v-if="reflection">
                       <p class="text-xs text-neutral-500">
                         {{ t('lessons.reflection.status_label') }}
                         <span class="font-medium capitalize" :class="reflectionStatusClass">
                           {{ reflection.review_status }}
                         </span>
                       </p>
                       <p v-if="reflection.teacher_note" class="text-xs text-neutral-600 mt-1">
                         {{ t('lessons.reflection.teacher_note_label') }} {{ reflection.teacher_note }}
                       </p>
                     </div>
                     <Button
                       :loading="reflectionForm.processing"
                       :disabled="!lesson.is_completed || reflectionForm.takeaway.length < 30"
                       @click="submitReflection"
                     >
                       {{ reflection ? t('lessons.reflection.update_button') : t('lessons.reflection.submit_button') }}
                     </Button>
                   </div>
                </div>

            <!-- Task -->
            <div v-if="activeTab === 'Overview' && task && lesson.is_completed && reflection" class="mt-6 p-4 bg-white rounded-xl border border-neutral-200">
                  <h3 class="text-lg font-semibold text-neutral-900 mb-2">{{ task.title }}</h3>
                  <p v-if="task.instructions" class="text-sm text-neutral-700 mb-4 whitespace-pre-line">
                    {{ task.instructions }}
                  </p>

                  <div class="space-y-4">
                    <!-- Progress Display -->
                    <div>
                      <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-neutral-700">
                          Day {{ task.progress?.days_done || 0 }} of {{ task.required_days }}
                        </span>
                        <span v-if="task.progress?.status === 'completed'" class="text-xs px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 font-medium">
                          Completed
                        </span>
                        <span v-else-if="task.progress?.status === 'in_progress'" class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-700 font-medium">
                          In Progress
                        </span>
                      </div>
                      <div class="w-full bg-neutral-200 rounded-full h-2">
                        <div
                          class="bg-primary-600 h-2 rounded-full transition-all"
                          :style="{ width: `${Math.min((task.progress?.days_done || 0) / task.required_days * 100, 100)}%` }"
                        ></div>
                      </div>
                    </div>

                    <!-- Check-in Button -->
                    <div v-if="task.progress?.status !== 'completed'">
                      <Button
                        @click="checkIn"
                        :disabled="task.progress?.has_checked_in_today || checkinForm.processing"
                        :loading="checkinForm.processing"
                        variant="primary"
                        class="w-full"
                      >
                        <span v-if="task.progress?.has_checked_in_today">
                          ✓ Already checked in today - Come back tomorrow!
                        </span>
                        <span v-else>
                          Mark today as done
                        </span>
                      </Button>
                    </div>

                    <div v-else class="text-sm text-emerald-700 flex items-center gap-2">
                      <Check class="w-5 h-5" />
                      <span>Task completed! You can now proceed to the next lesson.</span>
                    </div>
                  </div>
                </div>

            <!-- Quiz Tab -->
            <div v-if="activeTab === t('lessons.tabs.quiz') && hasQuiz" class="space-y-4">
              <div v-if="!lesson.is_completed" class="bg-amber-50 border border-amber-100 text-amber-800 text-sm px-4 py-3 rounded-lg">
                {{ t('lessons.quiz.complete_first') }}
              </div>
              <template v-else>
                <div v-if="quiz_attempt && !showRetakeQuizForm" class="bg-neutral-50 rounded-xl border border-neutral-200 p-4 mb-4">
                  <h3 class="font-semibold text-neutral-900 mb-2">
                    {{ t('lessons.quiz.result_title') }}
                  </h3>
                  <p class="text-sm text-neutral-700">
                    You scored <strong>{{ quiz_attempt.score }} / {{ quiz_attempt.total_questions }}</strong>
                    ({{ quiz_attempt.total_questions ? Math.round((quiz_attempt.score / quiz_attempt.total_questions) * 100) : 0 }}%).
                  </p>
                  <p class="mt-2">
                    <span v-if="quiz_attempt.passed" class="inline-flex items-center gap-1 text-emerald-700 font-medium">
                      <Check class="w-4 h-4" /> {{ t('lessons.quiz.passed') }}
                    </span>
                    <span v-else class="text-amber-700 font-medium">
                      {{ t('lessons.quiz.not_passed') }}
                    </span>
                  </p>
                  <Button variant="secondary" size="sm" class="mt-3" @click="showRetakeQuizForm = true">
                    {{ t('lessons.quiz.retake_button') }}
                  </Button>
                </div>
                <form v-else @submit.prevent="submitQuiz" class="space-y-6">
                  <div v-for="(q, qIndex) in quiz" :key="q.id" class="border border-neutral-200 rounded-xl p-4 bg-white">
                    <p class="font-medium text-neutral-900 mb-3">{{ qIndex + 1 }}. {{ q.question_text }}</p>
                    <p class="text-xs text-neutral-500 mb-2">
                      {{ t('lessons.quiz.select_all') }}
                    </p>
                    <div class="space-y-2">
                      <label
                        v-for="(opt, optIndex) in (q.options || [])"
                        :key="optIndex"
                        class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
                        :class="getQuizAnswer(q.id).includes(optIndex) ? 'border-primary-500 bg-primary-50' : 'border-neutral-200 hover:border-primary-300'"
                      >
                        <input
                          type="checkbox"
                          :checked="getQuizAnswer(q.id).includes(optIndex)"
                          @change="toggleQuizAnswer(q.id, optIndex)"
                          class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
                        />
                        <span class="text-sm text-neutral-800">{{ opt }}</span>
                      </label>
                    </div>
                  </div>
                  <Button
                    type="submit"
                    :loading="quizSubmitting"
                    variant="primary"
                    class="w-full"
                  >
                    {{ t('lessons.quiz.submit_button') }}
                  </Button>
                </form>
              </template>
            </div>

            <!-- Notes Tab -->
            <div v-if="activeTab === 'Notes'" class="space-y-4">
                  <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-lg text-neutral-900">Lesson Notes</h3>
                    <Button @click="showNoteModal = true" size="sm" class="flex items-center gap-2">
                      <Plus class="w-4 h-4" />
                      New Note
                    </Button>
                  </div>

                  <div v-if="notes.length === 0" class="text-center py-12 text-neutral-400">
                    <FileText class="w-12 h-12 mx-auto mb-3 opacity-50" />
                    <p>No notes yet for this lesson</p>
                    <p class="text-sm mt-1">Create a note to capture your thoughts and learnings</p>
                  </div>

                  <div v-else class="space-y-3">
                    <div
                      v-for="note in notes"
                      :key="note.id"
                      class="bg-neutral-50 rounded-lg border border-neutral-200 p-4 hover:shadow-md transition-shadow"
                    >
                      <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                          <div class="flex items-center gap-2">
                            <h4 class="font-semibold text-neutral-900">{{ note.title }}</h4>
                            <Pin v-if="note.pinned" class="w-4 h-4 text-primary-600" />
                          </div>
                          <p class="text-xs text-neutral-500 mt-1">{{ note.updated_at }}</p>
                        </div>
                        <button
                          @click="deleteNote(note.id)"
                          class="text-red-500 hover:text-red-700 transition-colors"
                          title="Delete note"
                        >
                          <Trash2 class="w-4 h-4" />
                        </button>
                      </div>
                      <p class="text-neutral-700 text-sm whitespace-pre-wrap">{{ note.content }}</p>
                    </div>
                  </div>
                </div>
            </div>
          </aside>

          <!-- Right Column: Video Player (8/12) - order-1 on mobile so video at top, not cut off -->
          <div class="w-full md:w-8/12 flex flex-col bg-neutral-50 overflow-hidden order-1 md:order-2">
            <!-- Video Player - Fixed Height, No Scroll; min-h on mobile so video isn't cut off -->
            <div class="flex-1 flex items-start justify-center p-4 md:p-6 min-h-[min(40vh,280px)] md:min-h-0 overflow-hidden">
              <div v-if="lesson.is_locked" class="w-full max-w-5xl rounded-xl border border-dashed border-neutral-300 bg-neutral-50 px-4 py-6 text-center text-sm text-neutral-600">
                This lesson is locked. Please complete the previous lessons to unlock it.
              </div>
              <div v-else class="w-full max-w-5xl h-full">
                <VideoGuardPlayer
                  :provider="lesson.video_provider"
                  :video-url="lesson.video_url"
                  :youtube-id="lesson.youtube_video_id"
                  :start-seconds="playerStartSeconds"
                  :lesson-id="lesson.id"
                  :title="lesson.title"
                  :duration-seconds="lesson.duration_seconds"
                  :allow-free-seek="lesson.is_completed"
                  @ready="onPlayerReady"
                  @heartbeat="onPlayerHeartbeat"
                  @ended="onPlayerEnded"
                  @stateChange="onPlayerStateChange"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Bottom: Course Videos Playlist (mobile: taller + safe-area so content isn't cut off) -->
        <div class="shrink-0 border-t border-neutral-200 bg-white flex flex-col h-24 md:h-20 z-10 pb-[env(safe-area-inset-bottom,0px)]">
          <div class="p-1.5 border-b border-neutral-100 bg-neutral-50 shrink-0">
            <h3 class="font-bold text-neutral-900 text-[10px]">{{ course.title }}</h3>
            <p class="text-[9px] text-neutral-500">Course Content</p>
          </div>
          <div class="flex-1 overflow-x-auto overflow-y-hidden min-h-0">
            <div class="flex gap-1.5 p-1.5 min-w-max h-full items-center">
              <Link
                v-for="(item, index) in playlist"
                :key="item.id"
                :href="route('lessons.show', { course: course.id, lesson: item.id })"
                :class="[
                  'px-2.5 py-1.5 rounded-md border transition-all group whitespace-nowrap text-xs',
                  item.is_current 
                    ? 'bg-primary-50 border-primary-500 text-primary-900 font-semibold' 
                    : item.is_completed
                      ? 'bg-emerald-50 border-emerald-300 text-emerald-900 hover:bg-emerald-100'
                      : item.is_locked
                        ? 'bg-neutral-50 border-neutral-200 text-neutral-400 cursor-not-allowed'
                        : 'bg-white border-neutral-200 text-neutral-700 hover:bg-neutral-50 hover:border-primary-300'
                ]"
              >
                <div class="flex items-center gap-1.5">
                  <div :class="[
                    'w-4 h-4 rounded-full border flex items-center justify-center shrink-0',
                    item.is_completed ? 'bg-emerald-500 border-emerald-500 text-white' :
                    item.is_current ? 'border-primary-500 text-primary-600' : 'border-neutral-300 text-transparent'
                  ]">
                    <Check v-if="item.is_completed" class="w-2.5 h-2.5" />
                    <div v-if="item.is_current && !item.is_completed" class="w-1.5 h-1.5 rounded-full bg-primary-500"></div>
                  </div>
                  <span class="text-xs">{{ index + 1 }}. {{ item.title }}</span>
                  <span v-if="item.is_locked" class="text-[9px] uppercase tracking-wide text-neutral-400 ml-1">Locked</span>
                  <span v-else class="text-[10px] text-neutral-400 flex items-center gap-0.5">
                    <Play class="w-2.5 h-2.5" /> {{ item.duration }}
                  </span>
                </div>
              </Link>
            </div>
          </div>
        </div>
    </div>

    <!-- Note Creation Modal -->
    <Modal :show="showNoteModal" @close="showNoteModal = false" max-width="2xl">
      <div class="p-6">
        <h2 class="font-serif text-2xl font-bold text-neutral-900 mb-6">Create Lesson Note</h2>
        <form @submit.prevent="saveNote">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-2">
                Title <span class="text-red-500">*</span>
              </label>
              <input
                v-model="noteForm.title"
                type="text"
                required
                maxlength="255"
                class="w-full rounded-xl border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                placeholder="Enter note title..."
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-2">
                Content <span class="text-red-500">*</span>
              </label>
              <textarea
                v-model="noteForm.content"
                rows="8"
                required
                maxlength="10000"
                class="w-full rounded-xl border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                placeholder="Write your note here..."
              ></textarea>
              <p class="text-xs text-neutral-500 mt-1">
                {{ noteForm.content.length }} / 10,000 characters
              </p>
            </div>
            <div class="flex items-center gap-2">
              <input
                v-model="noteForm.pinned"
                type="checkbox"
                id="note-pinned"
                class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
              />
              <label for="note-pinned" class="text-sm text-neutral-700 cursor-pointer">
                Pin this note
              </label>
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <Button type="button" variant="secondary" @click="showNoteModal = false">
              Cancel
            </Button>
            <Button type="submit" :loading="noteForm.processing">
              Create Note
            </Button>
          </div>
        </form>
      </div>
    </Modal>

    <!-- Post-Lesson Summary Card -->
    <PostLessonSummaryCard
      :show="showPostLessonSummary"
      :lesson-id="lesson.id"
      :lesson-title="lesson.title"
      @close="showPostLessonSummary = false"
    />

    <!-- Side Panel Notebook -->
    <SidePanelNotebook
      :is-open="showSidePanel"
      :lesson-id="lesson.id"
      @open="showSidePanel = true"
      @close="showSidePanel = false"
    />
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import VideoGuardPlayer from '@/Components/VideoGuardPlayer.vue';
import Button from '@/Components/Common/Button.vue';
import Modal from '@/Components/Modal.vue';
import PostLessonSummaryCard from '@/Components/PostLessonSummaryCard.vue';
import SidePanelNotebook from '@/Components/SidePanelNotebook.vue';
import { Check, Play, Plus, FileText, Pin, Trash2, BookOpen, Heart, Volume2, Download, Loader2 } from 'lucide-vue-next';
import { Link, usePage, useForm, router } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted, onBeforeUnmount, getCurrentInstance, inject } from 'vue';
import axios from 'axios';
import { useI18n } from '@/i18n';

const props = defineProps({
  course: Object,
  lesson: Object,
  playlist: Array,
  completedLessonIds: {
    type: Array,
    default: () => [],
  },
  reflection: {
    type: Object,
    default: null,
  },
  task: {
    type: Object,
    default: null,
  },
  notes: {
    type: Array,
    default: () => [],
  },
  quiz: {
    type: Array,
    default: null,
  },
  quiz_attempt: {
    type: Object,
    default: null,
  },
});

const { t, locale } = useI18n();

const hasQuiz = computed(() => props.quiz && props.quiz.length > 0);
const tabs = computed(() => {
  const baseTabs = [
    t('lessons.tabs.overview'),
    t('lessons.tabs.resources'),
    t('lessons.tabs.reflection'),
  ];
  if (hasQuiz.value) baseTabs.push(t('lessons.tabs.quiz'));
  baseTabs.push(t('lessons.tabs.notes'));
  return baseTabs;
});
const activeTab = ref(t('lessons.tabs.overview'));
const showNoteModal = ref(false);
const showPostLessonSummary = ref(false);
const showSidePanel = ref(false);

const lessonResources = ref(null);
const resourcesLoading = ref(false);
const resourcesPdfDownloading = ref(false);

const resourcesPdfViewUrl = computed(() => {
  if (!lessonResources.value || !lessonResources.value.can_view) return '';
  if (!lessonResources.value.sunnah_pointers && !lessonResources.value.duas_text) return '';
  return route('lessons.resources.pdf.view', { lesson: props.lesson.id });
});

const page = usePage();

// Get route helper - try inject first (provided by ZiggyVue), then global property, then fallback
const route = inject('route', null) || 
    getCurrentInstance()?.appContext.config.globalProperties.route ||
    (typeof window !== 'undefined' && window.route) ||
    ((name, params) => {
        console.error('Route helper not available. Route name:', name, params);
        return '#';
    });

const noteForm = useForm({
  title: '',
  content: '',
  noteable_type: 'App\\Models\\Lesson',
  noteable_id: null,
  pinned: false,
});

const startSeconds = computed(() => {
  try {
    const url = new URL(page.url, window.location.origin);
    const t = url.searchParams.get('t');
    const value = t ? Number(t) : 0;
    return Number.isNaN(value) ? 0 : value;
  } catch {
    return 0;
  }
});

let lessonSeekHandler = null;
const playerStartSeconds = ref(0);

const searchQuery = computed(() => {
  try {
    const url = new URL(page.url, window.location.origin);
    const q = url.searchParams.get('q');
    return q ? String(q) : '';
  } catch {
    return '';
  }
});

// Keyboard shortcuts handler
const handleKeyPress = (e) => {
  // Don't trigger if user is typing in an input/textarea
  if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) {
    return;
  }

  // Ctrl/Cmd + N to toggle notes panel
  if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
    e.preventDefault();
    showSidePanel.value = !showSidePanel.value;
  }
  // Escape to close side panel
  if (e.key === 'Escape' && showSidePanel.value) {
    showSidePanel.value = false;
  }
};

onMounted(() => {
  // Initialise player start from URL (?t=) or video progress
  const urlStartSeconds = startSeconds.value || 0;
  
  // Check for saved video progress (resume functionality)
  if (props.lesson.video_progress && !props.lesson.video_progress.is_completed) {
    const savedPosition = props.lesson.video_progress.last_position_seconds || 0;
    // Only resume if there's a saved position > 5 seconds (avoid resuming from very start)
    if (savedPosition > 5) {
      playerStartSeconds.value = savedPosition;
    } else {
      playerStartSeconds.value = urlStartSeconds;
    }
  } else {
    playerStartSeconds.value = urlStartSeconds;
  }

  lessonSeekHandler = (event) => {
    const seconds = event?.detail?.seconds ?? 0;
    playerStartSeconds.value = Math.max(0, Number(seconds || 0));
  };

  window.addEventListener('lesson-seek', lessonSeekHandler);
  window.addEventListener('keydown', handleKeyPress);
});

onBeforeUnmount(() => {
  if (lessonSeekHandler) {
    window.removeEventListener('lesson-seek', lessonSeekHandler);
    lessonSeekHandler = null;
  }
  window.removeEventListener('keydown', handleKeyPress);
});

const onPlayerReady = (payload) => {
  console.log('YouTubePlayer ready', payload);
  if (payload?.duration) {
    // Store duration if not already set
    if (!props.lesson.video_duration_seconds) {
      axios.post(route('lessons.duration', { lesson: props.lesson.id }), {
        duration_seconds: Math.round(payload.duration),
      }).catch(() => {});
    }
    // Also update video progress immediately with duration
    if (props.lesson.id) {
      axios.post(route('lesson-progress.update', { lesson: props.lesson.id }), {
        duration_seconds: Math.round(payload.duration),
        last_position_seconds: 0,
        percent_complete: 0,
        provider: 'youtube',
      }).catch(() => {});
    }
  }
};

// Track last reload time
let lastProgressReload = 0;
const PROGRESS_RELOAD_INTERVAL = 10000; // Reload every 10 seconds

const onPlayerHeartbeat = (payload) => {
  console.log('YouTubePlayer heartbeat', payload);
  // The VideoGuardPlayer component handles progress updates via updateVideoProgress
  // Reload progress data periodically to update the display
  if (payload && payload.duration && payload.duration > 0) {
    const now = Date.now();
    if (now - lastProgressReload > PROGRESS_RELOAD_INTERVAL) {
      // Reload only lesson data to update progress display
      router.reload({ only: ['lesson'], preserveScroll: true });
      lastProgressReload = now;
    }
  }
};

const onPlayerEnded = async () => {
  console.log('YouTubePlayer ended');
  // Show post-lesson summary card only if lesson is completed
  // The completion check happens server-side, so we show the popup
  // The PostLessonSummaryCard component will handle access control
  showPostLessonSummary.value = true;
};

function saveNote() {
  noteForm.noteable_id = props.lesson.id;
  noteForm.post(route('notes.store'), {
    preserveScroll: true,
    onSuccess: () => {
      showNoteModal.value = false;
      noteForm.reset();
    }
  });
}

function deleteNote(noteId) {
  if (confirm('Are you sure you want to delete this note?')) {
    router.delete(route('notes.destroy', noteId), {
      preserveScroll: true
    });
  }
}

const onPlayerStateChange = (payload) => {
  console.log('YouTubePlayer stateChange', payload);
};

const reflectionForm = useForm({
  takeaway: props.reflection?.takeaway || props.reflection?.content || '',
});

const completionForm = useForm({});
const checkinForm = useForm({});

// Quiz state: per question id -> array of selected option indices (multiple answers)
const quizAnswers = ref({});
const quizSubmitting = ref(false);
const showRetakeQuizForm = ref(false);

function getQuizAnswer(questionId) {
  const a = quizAnswers.value[questionId];
  return Array.isArray(a) ? a : [];
}
function toggleQuizAnswer(questionId, optIndex) {
  if (!quizAnswers.value[questionId]) {
    quizAnswers.value[questionId] = [];
  }
  const arr = quizAnswers.value[questionId];
  const i = arr.indexOf(optIndex);
  if (i === -1) {
    arr.push(optIndex);
  } else {
    arr.splice(i, 1);
  }
  quizAnswers.value = { ...quizAnswers.value };
}

async function submitQuiz() {
  const answers = {};
  props.quiz.forEach(q => {
    answers[q.id] = getQuizAnswer(q.id);
  });
  quizSubmitting.value = true;
  try {
    const { data } = await axios.post(route('lessons.quiz.store', { lesson: props.lesson.id }), { answers });
    if (data) {
      router.reload({ only: ['quiz_attempt'] });
    }
  } catch (err) {
    const msg = err.response?.data?.message || t('lessons.quiz.submit_error');
    alert(msg);
  } finally {
    quizSubmitting.value = false;
  }
}

const markComplete = () => {
  completionForm.post(route('lessons.complete', { lesson: props.lesson.id }), {
    preserveScroll: true,
    onSuccess: () => {
      // Refresh page to update completion status
      router.reload({ only: ['lesson', 'task'] });
    },
    onError: (errors) => {
      // Errors are shown via Inertia flash/errors
      console.error('Completion failed:', errors);
    },
  });
};

const checkIn = () => {
  if (!props.task) return;

  checkinForm.post(route('tasks.checkin', { task: props.task.id }), {
    preserveScroll: true,
    onSuccess: (response) => {
      // Refresh page to update task progress
      router.reload({ only: ['lesson', 'task'] });
    },
    onError: (errors) => {
      console.error('Check-in failed:', errors);
    },
  });
};

const submitReflection = () => {
  reflectionForm.post(route('lessons.reflection', { lesson: props.lesson.id }), {
    preserveScroll: true,
  });
};

const reflectionStatusClass = computed(() => {
  if (!props.reflection) return '';
  if (props.reflection.review_status === 'approved') return 'text-emerald-700';
  if (props.reflection.review_status === 'needs_clarification') return 'text-amber-700';
  return 'text-neutral-600';
});

const formatTime = (seconds) => {
  const s = Math.max(0, Number(seconds || 0));
  const mins = Math.floor(s / 60);
  const secs = Math.floor(s % 60);
  return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const escapeHtml = (str) => {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
};

const highlightText = (text) => {
  const q = searchQuery.value.trim();
  if (!q) {
    return escapeHtml(text || '');
  }

  const escaped = escapeHtml(text || '');

  try {
    const pattern = new RegExp(q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
    return escaped.replace(pattern, (match) => `<mark>${match}</mark>`);
  } catch {
    return escaped;
  }
};

const seekTo = (seconds) => {
  window.dispatchEvent(new CustomEvent('lesson-seek', {
    detail: { seconds: Math.max(0, Number(seconds || 0)) },
  }));
};

const goToLesson = (lessonId) => {
  if (!lessonId) return;
  router.visit(route('lessons.show', { 
    course: props.course.id, 
    lesson: lessonId 
  }));
};

function fetchLessonResources() {
  if (!props.lesson?.id) return;
  resourcesLoading.value = true;
  lessonResources.value = null;
  axios.get(route('lessons.resources.show', { lesson: props.lesson.id }))
    .then((res) => {
      lessonResources.value = res.data;
    })
    .catch(() => {
      lessonResources.value = null;
    })
    .finally(() => {
      resourcesLoading.value = false;
    });
}

function downloadResourcesPdf() {
  if (!props.lesson?.id) return;
  resourcesPdfDownloading.value = true;
  const url = route('lessons.resources.pdf', { lesson: props.lesson.id });
  axios.get(url, { responseType: 'blob' })
    .then((response) => {
      const blob = new Blob([response.data], { type: 'application/pdf' });
      const link = document.createElement('a');
      link.href = window.URL.createObjectURL(blob);
      link.download = `lesson-resources-${(props.lesson.title || props.lesson.id).replace(/\s+/g, '-')}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(link.href);
    })
    .catch(() => {
      alert('Failed to download PDF.');
    })
    .finally(() => {
      resourcesPdfDownloading.value = false;
    });
}

watch(activeTab, (tab) => {
  if (tab === 'Dua & Resources') {
    fetchLessonResources();
  }
});
</script>

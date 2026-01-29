<template>
  <AppShell>
    <div class="h-[calc(100vh-8rem)] flex flex-col lg:flex-row gap-6">
       <!-- Main Content Area - Scrollable -->
       <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
          <!-- Video Player -->
          <div class="shrink-0 mb-6">
             <div v-if="lesson.is_locked" class="rounded-xl border border-dashed border-neutral-300 bg-neutral-50 px-4 py-6 text-center text-sm text-neutral-600">
               This lesson is locked. Please complete the previous lessons to unlock it.
             </div>
             <TrackedVideoPlayer
               v-else
               :provider="lesson.video_provider"
               :video-url="lesson.video_url"
               :youtube-id="lesson.youtube_video_id"
               :start-seconds="playerStartSeconds"
               :lesson-id="lesson.id"
               :title="lesson.title"
               @ready="onPlayerReady"
               @heartbeat="onPlayerHeartbeat"
               @ended="onPlayerEnded"
               @stateChange="onPlayerStateChange"
             />
          </div>

          <!-- Lesson Info & Tabs -->
          <div class="flex flex-col bg-white rounded-xl border border-neutral-200 shadow-sm min-h-0">
             <!-- Tabs Header -->
             <div class="flex border-b border-neutral-200 shrink-0">
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
             <div class="p-6 min-h-0">
                <div
                  v-if="$page.props.errors?.completion"
                  class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-xs text-red-700"
                >
                  {{ $page.props.errors.completion }}
                </div>
                <!-- Overview -->
                <div v-if="activeTab === 'Overview'">
                   <h1 class="text-2xl font-serif font-bold text-neutral-900 mb-2">{{ lesson.title }}</h1>
                   <p class="text-neutral-600 leading-relaxed max-w-prose">
                     Part of <span class="font-semibold">{{ course.title }}</span>
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
                <div v-if="activeTab === 'Transcript'" class="max-w-none text-neutral-700 font-serif leading-relaxed text-sm">
                  <div v-if="!lesson.transcript_segments || !lesson.transcript_segments.length" class="text-sm text-neutral-500">
                    Transcript is not available for this lesson yet.
                  </div>
                  <div v-else class="space-y-2">
                    <div
                      v-for="segment in lesson.transcript_segments"
                      :key="segment.id"
                      class="flex items-start gap-3"
                    >
                      <button
                        type="button"
                        class="mt-0.5 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-neutral-100 text-neutral-700 hover:bg-primary-100 hover:text-primary-800"
                        @click="seekTo(segment.start_seconds)"
                      >
                        {{ formatTime(segment.start_seconds) }}
                      </button>
                      <p
                        class="flex-1"
                        v-html="highlightText(segment.text)"
                      ></p>
                    </div>
                  </div>
                </div>

                <!-- Reflection -->
                <div v-if="activeTab === 'Reflection'" class="space-y-4">
                   <div class="bg-amber-50 border border-amber-100 text-amber-800 text-sm px-4 py-3 rounded-lg">
                     This lesson requires a short written reflection before you can advance to the next step.
                   </div>

                   <div>
                     <label class="block text-sm font-medium text-neutral-700 mb-2">
                       Your reflection
                     </label>
                     <textarea
                       v-model="reflectionForm.content"
                       rows="6"
                       class="w-full rounded-xl border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 p-3 text-sm"
                       placeholder="What did you take from this lesson? How will you apply it in your life?"
                     ></textarea>
                   </div>

                   <div class="flex items-center justify-between">
                     <div v-if="reflection">
                       <p class="text-xs text-neutral-500">
                         Status:
                         <span class="font-medium" :class="reflectionStatusClass">
                           {{ reflection.review_status }}
                         </span>
                       </p>
                       <p v-if="reflection.mentor_note" class="text-xs text-neutral-600 mt-1">
                         Mentor note: {{ reflection.mentor_note }}
                       </p>
                     </div>
                     <Button :loading="reflectionForm.processing" @click="submitReflection">
                       Submit reflection
                     </Button>
                   </div>
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
          </div>
       </div>

       <!-- Playlist Sidebar -->
       <div class="w-full lg:w-96 flex flex-col shrink-0 bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-sm h-full max-h-[600px] lg:max-h-[calc(100vh-8rem)]">
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
                   <div class="flex items-center justify-between">
                     <div :class="[
                       'text-sm font-medium mb-0.5',
                       item.is_current ? 'text-primary-800' : 'text-neutral-700'
                     ]">
                       {{ index + 1 }}. {{ item.title }}
                     </div>
                     <span v-if="item.is_locked" class="text-[11px] uppercase tracking-wide text-neutral-400">
                       Locked
                     </span>
                   </div>
                   <div class="text-xs text-neutral-400 flex items-center gap-2">
                      <Play class="w-3 h-3" /> {{ item.duration }}
                   </div>
                </div>
             </Link>
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
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import TrackedVideoPlayer from '@/Components/Course/TrackedVideoPlayer.vue';
import Button from '@/Components/Common/Button.vue';
import Modal from '@/Components/Modal.vue';
import { Check, Play, Plus, FileText, Pin, Trash2 } from 'lucide-vue-next';
import { Link, usePage, useForm, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { route } from 'ziggy-js';

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
  notes: {
    type: Array,
    default: () => [],
  },
});

const tabs = ['Overview', 'Transcript', 'Reflection', 'Notes'];
const activeTab = ref('Overview');
const showNoteModal = ref(false);

const noteForm = useForm({
  title: '',
  content: '',
  noteable_type: 'App\\Models\\Lesson',
  noteable_id: null,
  pinned: false,
});

const page = usePage();
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

onMounted(() => {
  // Initialise player start from URL (?t=)
  playerStartSeconds.value = startSeconds.value || 0;

  lessonSeekHandler = (event) => {
    const seconds = event?.detail?.seconds ?? 0;
    playerStartSeconds.value = Math.max(0, Number(seconds || 0));
  };

  window.addEventListener('lesson-seek', lessonSeekHandler);
});

onBeforeUnmount(() => {
  if (lessonSeekHandler) {
    window.removeEventListener('lesson-seek', lessonSeekHandler);
    lessonSeekHandler = null;
  }
});

const onPlayerReady = (payload) => {
  console.log('YouTubePlayer ready', payload);
  if (!props.lesson.video_duration_seconds && payload?.duration) {
    axios.post(route('lessons.duration', { lesson: props.lesson.id }), {
      duration_seconds: Math.round(payload.duration),
    }).catch(() => {});
  }
};

const onPlayerHeartbeat = (payload) => {
  console.log('YouTubePlayer heartbeat', payload);
};

const onPlayerEnded = () => {
  console.log('YouTubePlayer ended');
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
  content: props.reflection?.content || '',
});

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
</script>

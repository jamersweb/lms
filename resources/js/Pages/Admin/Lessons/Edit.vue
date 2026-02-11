<template>
  <AppShell>
    <Head :title="`Admin - Edit ${lesson.title}`" />

    <div class="w-full">
      <!-- Header -->
      <div class="mb-8">
        <Link href="/admin/lessons" class="inline-flex items-center gap-2 text-neutral-600 hover:text-primary-600 mb-4">
          <ArrowLeft class="w-4 h-4" />
          Back to Lessons
        </Link>
        <h1 class="text-2xl font-serif font-bold text-primary-900">Edit Lesson</h1>
        <p class="text-neutral-600 mt-1">Update lesson details</p>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="bg-white rounded-xl border border-neutral-200 p-6 space-y-6">
        <!-- Module -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Module *</label>
          <select
            v-model="form.module_id"
            class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
            required
          >
            <option v-for="mod in modules" :key="mod.id" :value="mod.id">
              {{ mod.course?.title }} → {{ mod.title }}
            </option>
          </select>
          <p v-if="form.errors.module_id" class="mt-1 text-sm text-red-600">{{ form.errors.module_id }}</p>
        </div>

        <!-- Titles (Multilingual) -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Lesson Title *</label>
          <input
            v-model="form.title"
            type="text"
            class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
            required
          />
          <p class="mt-1 text-xs text-neutral-500">Used as default; you can override per language below.</p>
          <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
        </div>

        <div class="border border-neutral-100 rounded-xl p-4 bg-neutral-50 space-y-4">
          <div class="flex flex-wrap gap-2 text-xs font-medium text-neutral-600 mb-2">
            <span class="px-2 py-0.5 rounded-full bg-white border border-neutral-200">Content language titles</span>
          </div>

          <div class="grid gap-4 md:grid-cols-3">
            <div>
              <label class="block text-xs font-medium text-neutral-700 mb-1">Title (English)</label>
              <input
                v-model="form.title_en"
                type="text"
                class="w-full px-3 py-2 border border-neutral-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-neutral-700 mb-1">Title (Roman)</label>
              <input
                v-model="form.title_en_roman"
                type="text"
                class="w-full px-3 py-2 border border-neutral-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-neutral-700 mb-1">Title (Urdu)</label>
              <input
                v-model="form.title_ur"
                type="text"
                class="w-full px-3 py-2 border border-neutral-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
                dir="rtl"
              />
            </div>
          </div>
        </div>

        <!-- Lesson Image -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Lesson Image</label>
          <div class="space-y-4">
            <div v-if="imagePreview || lesson.image" class="relative w-full max-w-md">
              <img 
                :src="imagePreview || lesson.image" 
                alt="Preview" 
                class="w-full h-48 object-cover rounded-xl border border-neutral-200" 
              />
              <button
                type="button"
                @click="clearImage"
                class="absolute top-2 right-2 p-2 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors"
              >
                <X class="w-4 h-4" />
              </button>
            </div>
            <div>
              <label class="block w-full px-4 py-2.5 border-2 border-dashed border-neutral-300 rounded-xl hover:border-primary-400 cursor-pointer transition-colors bg-neutral-50 hover:bg-neutral-100">
                <input
                  type="file"
                  @change="handleImageChange"
                  accept="image/*"
                  class="hidden"
                />
                <div class="text-center">
                  <span class="text-sm font-medium text-neutral-700">Click to upload image</span>
                  <p class="text-xs text-neutral-500 mt-1">JPG, PNG, or WebP (max 5MB)</p>
                </div>
              </label>
              <p class="mt-1 text-xs text-neutral-500">Upload an image for the lesson</p>
            </div>
          </div>
          <p v-if="form.errors.image" class="mt-1 text-sm text-red-600">{{ form.errors.image }}</p>
        </div>

        <!-- Video Provider -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Video Provider</label>
          <div class="flex gap-4">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="form.video_provider" value="youtube" class="text-primary-600" />
              <Youtube class="w-5 h-5 text-red-600" />
              <span>YouTube</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="form.video_provider" value="external" class="text-primary-600" />
              <ExternalLink class="w-5 h-5 text-blue-600" />
              <span>External URL</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="form.video_provider" value="mp4" class="text-primary-600" />
              <Film class="w-5 h-5 text-neutral-600" />
              <span>Upload MP4</span>
            </label>
          </div>
        </div>

        <!-- YouTube Video ID -->
        <div v-if="form.video_provider === 'youtube'" class="bg-neutral-50 rounded-xl p-4 space-y-4">
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">YouTube Video ID</label>
            <input
              v-model="form.youtube_video_id"
              type="text"
              class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400 bg-white"
              placeholder="e.g., dQw4w9WgXcQ"
            />
          </div>
          <div v-if="form.youtube_video_id" class="rounded-lg overflow-hidden">
            <p class="text-sm font-medium text-neutral-700 mb-2">Preview:</p>
            <iframe
              class="w-full aspect-video rounded-lg"
              :src="`https://www.youtube.com/embed/${form.youtube_video_id}`"
              frameborder="0"
              allowfullscreen
            ></iframe>
          </div>
        </div>

        <!-- External URL -->
        <div v-if="form.video_provider === 'external'" class="bg-neutral-50 rounded-xl p-4">
          <label class="block text-sm font-medium text-neutral-700 mb-2">External Video URL</label>
          <input
            v-model="form.external_video_url"
            type="url"
            class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400 bg-white"
            placeholder="https://tazkiyahtarbiyah.com/videos/..."
          />
        </div>

        <!-- MP4 Upload -->
        <div v-if="form.video_provider === 'mp4'" class="bg-neutral-50 rounded-xl p-4">
          <label class="block text-sm font-medium text-neutral-700 mb-2">Upload MP4 File</label>
          <input
            type="file"
            @change="e => form.video_file = e.target.files[0]"
            accept="video/mp4"
            class="w-full"
          />
          <div v-if="lesson.video_path && !form.video_file" class="mt-3">
            <p class="text-sm text-neutral-600 mb-2">Current video:</p>
            <video class="w-full rounded-lg" controls :src="`/storage/${lesson.video_path}`"></video>
          </div>
          <p v-if="form.video_file" class="mt-2 text-sm text-neutral-600">New file: {{ form.video_file.name }}</p>
        </div>

        <!-- Transcript Upload -->
        <div class="bg-neutral-50 rounded-xl p-4 space-y-2">
          <div class="flex items-center justify-between">
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">Transcript File (.vtt or .srt)</label>
              <p class="text-xs text-neutral-500">
                Uploading a new file will replace existing transcript segments.
              </p>
            </div>
            <div v-if="lesson.transcript_segments_count" class="text-xs text-neutral-600 text-right">
              Transcript segments: <span class="font-semibold">{{ lesson.transcript_segments_count }}</span>
            </div>
          </div>

          <input
            type="file"
            accept=".vtt,.srt"
            class="w-full"
            @change="e => form.transcript_file = e.target.files[0]"
          />
          <p v-if="form.transcript_file" class="mt-1 text-sm text-neutral-600">
            New transcript: {{ form.transcript_file.name }}
          </p>
          <p v-if="form.errors.transcript_file" class="mt-1 text-sm text-red-600">
            {{ form.errors.transcript_file }}
          </p>

          <div
            v-if="lesson.transcript_preview && lesson.transcript_preview.length"
            class="mt-3 border border-dashed border-neutral-200 rounded-lg p-3 bg-white"
          >
            <p class="text-xs font-semibold text-neutral-700 mb-2">Preview (first 5 segments)</p>
            <ul class="space-y-1 text-xs text-neutral-600 max-h-32 overflow-y-auto">
              <li v-for="seg in lesson.transcript_preview" :key="seg.id">
                <span class="font-mono text-[11px] text-neutral-400">
                  [{{ Math.round(seg.start_seconds) }}s-{{ Math.round(seg.end_seconds) }}s]
                </span>
                <span class="ml-1">{{ seg.text }}</span>
              </li>
            </ul>
          </div>
        </div>

        <!-- Sort Order & Free Preview -->
        <div class="grid grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Sort Order</label>
            <input
              v-model="form.sort_order"
              type="number"
              class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
            />
          </div>
          <div class="flex items-center pt-8">
            <label class="flex items-center gap-3 cursor-pointer">
              <input type="checkbox" v-model="form.is_free_preview" class="h-5 w-5 rounded text-primary-600" />
              <span class="text-sm font-medium text-neutral-700">Free Preview</span>
            </label>
          </div>
        </div>

        <!-- Release Schedule (Drip Release) -->
        <div class="bg-neutral-50 rounded-xl p-4 space-y-4 border border-neutral-200">
          <div>
            <h3 class="text-sm font-semibold text-neutral-900 mb-1">Release Schedule</h3>
            <p class="text-xs text-neutral-600">
              Control when this lesson becomes available to students. Absolute release applies to all students. Relative offset releases per-student based on enrollment start date.
            </p>
          </div>

          <!-- Absolute Release -->
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">
              Absolute Release Date/Time (Optional)
            </label>
            <input
              v-model="form.release_at"
              type="datetime-local"
              class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400 bg-white"
            />
            <p class="mt-1 text-xs text-neutral-500">
              If set, this overrides the day offset. All students will see this lesson at this exact time.
            </p>
          </div>

          <!-- Relative Day Offset -->
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">
              Day Offset from Enrollment (Optional)
            </label>
            <input
              v-model.number="form.release_day_offset"
              type="number"
              min="0"
              max="365"
              class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400 bg-white"
              placeholder="e.g., 1 = next day after enrollment"
            />
            <p class="mt-1 text-xs text-neutral-500">
              Number of days after student enrollment start date (0 = immediately, 1 = next day, etc.). Ignored if absolute release is set.
            </p>
          </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center gap-4 pt-4 border-t border-neutral-100">
          <button
            type="submit"
            :disabled="form.processing"
            class="px-6 py-2.5 bg-primary-900 text-white rounded-lg font-medium hover:bg-primary-800 transition-colors disabled:opacity-50 flex items-center gap-2"
          >
            <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
            {{ form.processing ? 'Saving...' : 'Save Changes' }}
          </button>
          <Link href="/admin/lessons" class="px-6 py-2.5 text-neutral-600 hover:text-neutral-900">
            Cancel
          </Link>
        </div>
      </form>

      <!-- Content Rule -->
      <div class="mt-8">
        <ContentRuleForm
          type="lessons"
          :entity-id="lesson.id"
          :initial-rule="contentRule"
        />
      </div>

      <!-- Lesson Resources -->
      <div class="mt-8 bg-white rounded-xl border border-neutral-200 p-6">
        <h3 class="text-lg font-semibold text-neutral-900 mb-4">Sunnah & Dua Resources</h3>
        <p class="text-sm text-neutral-600 mb-4">
          Add Sunnah pointers and Duas in different languages. Students will see them based on their content language preference.
        </p>

        <form @submit.prevent="submitResources" class="space-y-4" enctype="multipart/form-data">
          <div class="grid gap-4 md:grid-cols-3">
            <div class="md:col-span-3">
              <label class="block text-sm font-medium text-neutral-700 mb-2">
                Sunnah Pointers (English)
              </label>
              <textarea
                v-model="resourceForm.sunnah_pointers_en"
                rows="3"
                class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              ></textarea>
            </div>
            <div class="md:col-span-3">
              <label class="block text-sm font-medium text-neutral-700 mb-2">
                Sunnah Pointers (Roman)
              </label>
              <textarea
                v-model="resourceForm.sunnah_pointers_en_roman"
                rows="3"
                class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              ></textarea>
            </div>
            <div class="md:col-span-3">
              <label class="block text-sm font-medium text-neutral-700 mb-2">
                Sunnah Pointers (Urdu)
              </label>
              <textarea
                v-model="resourceForm.sunnah_pointers_ur"
                rows="3"
                class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
                dir="rtl"
              ></textarea>
            </div>
          </div>

          <div class="grid gap-4 md:grid-cols-3">
            <div class="md:col-span-3">
              <label class="block text-sm font-medium text-neutral-700 mb-2">
                Duas (English)
              </label>
              <textarea
                v-model="resourceForm.duas_text_en"
                rows="4"
                class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              ></textarea>
            </div>
            <div class="md:col-span-3">
              <label class="block text-sm font-medium text-neutral-700 mb-2">
                Duas (Roman)
              </label>
              <textarea
                v-model="resourceForm.duas_text_en_roman"
                rows="4"
                class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              ></textarea>
            </div>
            <div class="md:col-span-3">
              <label class="block text-sm font-medium text-neutral-700 mb-2">
                Duas (Urdu / Arabic)
              </label>
              <textarea
                v-model="resourceForm.duas_text_ur"
                rows="4"
                class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
                dir="rtl"
              ></textarea>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">
              Dua Audio File (Optional)
            </label>
            <input
              ref="audioFileInput"
              type="file"
              @change="e => { resourceForm.audio_file = e.target.files?.[0] ?? null; }"
              accept=".mp3,.wav,.ogg,.m4a,audio/mpeg,audio/mp3,audio/wav,audio/ogg,audio/x-m4a"
              class="w-full"
            />
            <p v-if="resource?.audio_path" class="mt-2 text-sm text-neutral-600">
              Current: {{ resource.audio_path }}
            </p>
            <p class="mt-1 text-xs text-neutral-500">
              Upload audio file for Dua pronunciation (MP3, WAV, OGG, M4A — max 10MB)
            </p>
            <p v-if="resourceForm.errors.audio_file" class="mt-1 text-sm text-red-600">{{ resourceForm.errors.audio_file }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">
              Lesson Notes PDF (Optional)
            </label>
            <input
              ref="pdfFileInput"
              type="file"
              @change="e => { resourceForm.pdf_file = e.target.files?.[0] ?? null; }"
              accept=".pdf,application/pdf"
              class="w-full"
            />
            <p v-if="resource?.pdf_path" class="mt-2 text-sm text-neutral-600">
              Current: {{ resource.pdf_path }}
            </p>
            <p class="mt-1 text-xs text-neutral-500">
              Upload PDF file with lesson notes (max 5MB)
            </p>
            <p v-if="resourceForm.errors.pdf_file" class="mt-1 text-sm text-red-600">{{ resourceForm.errors.pdf_file }}</p>
          </div>

          <div class="flex gap-3">
            <button
              type="submit"
              :disabled="resourceForm.processing"
              class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 disabled:opacity-50"
            >
              {{ resource ? 'Update Resources' : 'Save Resources' }}
            </button>
            <button
              v-if="resource"
              type="button"
              @click="deleteResources"
              :disabled="resourceForm.processing"
              class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 disabled:opacity-50"
            >
              Delete Resources
            </button>
          </div>
        </form>
      </div>

      <!-- Quiz Questions -->
      <div class="mt-8 bg-white rounded-xl border border-neutral-200 p-6">
        <h3 class="text-lg font-semibold text-neutral-900 mb-4">Lesson Quiz</h3>
        <p class="text-sm text-neutral-600 mb-4">
          Add multiple-choice questions. Students can select one or more answers. Tick the checkboxes for each correct option.
        </p>

        <form @submit.prevent="submitQuiz" class="space-y-6">
          <div
            v-for="(q, qIdx) in quizForm.questions"
            :key="qIdx"
            class="border border-neutral-200 rounded-xl p-4 bg-neutral-50 space-y-3"
          >
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-neutral-600">Question {{ qIdx + 1 }}</span>
              <button
                type="button"
                @click="removeQuizQuestion(qIdx)"
                class="text-red-600 hover:text-red-800 text-sm"
              >
                Remove
              </button>
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">Question text</label>
              <textarea
                v-model="q.question_text"
                rows="2"
                class="w-full px-4 py-2 border border-neutral-200 rounded-lg text-sm"
                placeholder="e.g., What is Sunnat?"
              ></textarea>
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">Options (one per line)</label>
              <textarea
                :value="(q.options || []).join('\n')"
                @input="q.options = $event.target.value.split('\n').filter(Boolean)"
                rows="4"
                class="w-full px-4 py-2 border border-neutral-200 rounded-lg text-sm"
                placeholder="Option A&#10;Option B&#10;Option C"
              ></textarea>
            </div>
            <div v-if="(q.options || []).length > 0">
              <label class="block text-sm font-medium text-neutral-700 mb-2">Correct option(s) — select all that apply</label>
              <div class="flex flex-wrap gap-3">
                <label
                  v-for="(opt, optIndex) in (q.options || [])"
                  :key="optIndex"
                  class="flex items-center gap-2 px-3 py-2 rounded-lg border border-neutral-200 bg-white cursor-pointer hover:border-primary-300"
                >
                  <input
                    type="checkbox"
                    :checked="(q.correct_indices || []).includes(optIndex)"
                    @change="toggleQuizCorrectIndex(q, optIndex)"
                    class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
                  />
                  <span class="text-sm text-neutral-700">{{ opt }}</span>
                </label>
              </div>
            </div>
          </div>
          <button
            type="button"
            @click="addQuizQuestion"
            class="px-4 py-2 border-2 border-dashed border-neutral-300 rounded-xl text-sm font-medium text-neutral-600 hover:border-primary-400 hover:text-primary-700"
          >
            + Add Question
          </button>
          <div class="flex gap-3">
            <button
              type="submit"
              :disabled="quizForm.processing"
              class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 disabled:opacity-50"
            >
              {{ quizForm.processing ? 'Saving...' : 'Save Quiz' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Task Management -->
      <div class="mt-8 bg-white rounded-xl border border-neutral-200 p-6">
        <h3 class="text-lg font-semibold text-neutral-900 mb-4">Practice Task</h3>
        <p class="text-sm text-neutral-600 mb-4">
          Attach a task that students must complete before accessing the next lesson.
        </p>

        <form @submit.prevent="submitTask" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">
              Task Title <span class="text-red-500">*</span>
            </label>
            <input
              v-model="taskForm.title"
              type="text"
              required
              maxlength="255"
              class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              placeholder="e.g., Practice patience for 7 days"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">
              Required Days <span class="text-red-500">*</span>
            </label>
            <input
              v-model.number="taskForm.required_days"
              type="number"
              required
              min="1"
              max="365"
              class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
            />
            <p class="mt-1 text-xs text-neutral-500">
              Number of days students must check in to complete this task (1-365)
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">
              Instructions (Optional)
            </label>
            <textarea
              v-model="taskForm.instructions"
              rows="4"
              class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              placeholder="Provide guidance on what students should practice..."
            ></textarea>
          </div>

          <div class="flex items-center gap-2">
            <input
              v-model="taskForm.unlock_next_lesson"
              type="checkbox"
              id="unlock_next_lesson"
              class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
            />
            <label for="unlock_next_lesson" class="text-sm text-neutral-700 cursor-pointer">
              Block next lesson until task is completed
            </label>
          </div>

          <div class="flex gap-3">
            <button
              type="submit"
              :disabled="taskForm.processing"
              class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 disabled:opacity-50"
            >
              {{ task ? 'Update Task' : 'Create Task' }}
            </button>
            <button
              v-if="task"
              type="button"
              @click="deleteTask"
              :disabled="taskForm.processing"
              class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 disabled:opacity-50"
            >
              Delete Task
            </button>
          </div>
        </form>
      </div>

      <!-- Danger Zone -->
      <div class="mt-8 bg-red-50 rounded-xl border border-red-200 p-6">
        <h3 class="font-semibold text-red-800 mb-2">Danger Zone</h3>
        <p class="text-sm text-red-600 mb-4">Deleting this lesson will remove all progress tracking for students.</p>
        <button
          @click="deleteLesson"
          class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors"
        >
          Delete Lesson
        </button>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ArrowLeft, Loader2, Youtube, ExternalLink, Film, X } from 'lucide-vue-next';
import { ref } from 'vue';
import ContentRuleForm from '@/Components/Admin/ContentRuleForm.vue';

const props = defineProps({
  lesson: Object,
  modules: Array,
  contentRule: Object,
  task: Object,
  resource: Object,
});

// Format release_at for datetime-local input (ISO string without timezone)
const formatReleaseAt = (isoString) => {
  if (!isoString) return null;
  const date = new Date(isoString);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const form = useForm({
  module_id: props.lesson.module_id,
  title: props.lesson.title,
  title_en: props.lesson.title_en || '',
  title_en_roman: props.lesson.title_en_roman || '',
  title_ur: props.lesson.title_ur || '',
  image: null,
  video_provider: props.lesson.video_provider || 'youtube',
  youtube_video_id: props.lesson.youtube_video_id || '',
  external_video_url: props.lesson.external_video_url || '',
  video_file: null,
   transcript_file: null,
  sort_order: props.lesson.sort_order,
  is_free_preview: Boolean(props.lesson.is_free_preview),
  release_at: formatReleaseAt(props.lesson.release_at),
  release_day_offset: props.lesson.release_day_offset ?? null,
});

const quizForm = useForm({
  questions: (props.lesson.quiz_questions || []).length
    ? props.lesson.quiz_questions.map((q) => ({
        question_text: q.question_text,
        options: Array.isArray(q.options) ? [...q.options] : [],
        correct_indices: Array.isArray(q.correct_indices) ? [...q.correct_indices] : (q.correct_index !== undefined && q.correct_index !== null ? [q.correct_index] : []),
      }))
    : [],
});

const resourceForm = useForm({
  sunnah_pointers: props.resource?.sunnah_pointers || '',
  sunnah_pointers_en: props.resource?.sunnah_pointers_en || '',
  sunnah_pointers_en_roman: props.resource?.sunnah_pointers_en_roman || '',
  sunnah_pointers_ur: props.resource?.sunnah_pointers_ur || '',
  duas_text: props.resource?.duas_text || '',
  duas_text_en: props.resource?.duas_text_en || '',
  duas_text_en_roman: props.resource?.duas_text_en_roman || '',
  duas_text_ur: props.resource?.duas_text_ur || '',
  audio_file: null,
  pdf_file: null,
});
function addQuizQuestion() {
  quizForm.questions.push({ question_text: '', options: [], correct_indices: [] });
}
function removeQuizQuestion(index) {
  quizForm.questions.splice(index, 1);
}
function toggleQuizCorrectIndex(q, optIndex) {
  if (!q.correct_indices) q.correct_indices = [];
  const i = q.correct_indices.indexOf(optIndex);
  if (i === -1) {
    q.correct_indices.push(optIndex);
  } else {
    q.correct_indices.splice(i, 1);
  }
}
function submitQuiz() {
  const payload = {
    questions: quizForm.questions
      .filter((q) => q.question_text && (q.options || []).length >= 2)
      .map((q) => ({
        question_text: q.question_text,
        options: Array.isArray(q.options) ? q.options : [],
        correct_indices: Array.isArray(q.correct_indices)
          ? q.correct_indices.filter((idx) => idx >= 0 && idx < (q.options || []).length).map((idx) => Number(idx))
          : [],
      })),
  };
  quizForm.transform(() => payload).put(route('admin.lessons.quiz.update', { lesson: props.lesson.id }), {
    preserveScroll: true,
    onSuccess: () => router.reload({ only: ['lesson'] }),
  });
}

const imagePreview = ref(null);

function handleImageChange(event) {
  const file = event.target.files[0];
  if (file) {
    form.image = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
}

function clearImage() {
  form.image = null;
  imagePreview.value = null;
}

function submit() {
  form.transform((data) => {
    return {
      ...data,
      _method: 'PUT',
    };
  }).post(`/admin/lessons/${props.lesson.id}`, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      // Clear image preview after successful update
      imagePreview.value = null;
      // Reload the page to get updated lesson data including new image
      router.reload({ only: ['lesson'] });
    },
  });
}

const taskForm = useForm({
  title: props.task?.title || '',
  required_days: props.task?.required_days || 1,
  instructions: props.task?.instructions || '',
  unlock_next_lesson: props.task?.unlock_next_lesson ?? true,
});

function submitTask() {
  taskForm.put(route('admin.lessons.task.upsert', { lesson: props.lesson.id }), {
    preserveScroll: true,
    onSuccess: () => {
      router.reload({ only: ['task'] });
    },
  });
}

function deleteTask() {
  if (confirm('Are you sure you want to delete this task? Students will no longer need to complete it.')) {
    router.delete(route('admin.lessons.task.destroy', { lesson: props.lesson.id }), {
      preserveScroll: true,
      onSuccess: () => {
        router.reload({ only: ['task'] });
      },
    });
  }
}

function deleteLesson() {
  if (confirm(`Are you sure you want to delete "${props.lesson.title}"? This cannot be undone.`)) {
    router.delete(`/admin/lessons/${props.lesson.id}`);
  }
}

const audioFileInput = ref(null);
const pdfFileInput = ref(null);

function submitResources() {
  resourceForm.post(route('admin.lessons.resources.store', { lesson: props.lesson.id }), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      router.reload({ only: ['resource'] });
      resourceForm.reset();
      resourceForm.audio_file = null;
      resourceForm.pdf_file = null;
      if (audioFileInput.value) audioFileInput.value.value = '';
      if (pdfFileInput.value) pdfFileInput.value.value = '';
    },
  });
}

function deleteResources() {
  if (confirm('Are you sure you want to delete these resources?')) {
    router.delete(route('admin.lessons.resources.destroy', { lesson: props.lesson.id }), {
      preserveScroll: true,
      onSuccess: () => {
        router.reload({ only: ['resource'] });
      },
    });
  }
}
</script>

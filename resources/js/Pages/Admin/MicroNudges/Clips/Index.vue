<template>
  <AppShell>
    <Head title="Admin - Audio Clips" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-serif font-bold text-primary-900">Audio Clips Library</h1>
          <p class="text-neutral-600 mt-1">Manage audio clips for micro-nudges</p>
        </div>
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-900 text-white rounded-lg font-medium hover:bg-primary-800 transition-colors"
        >
          <Plus class="w-5 h-5" />
          Add Audio Clip
        </button>
      </div>

      <!-- Clips Table -->
      <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <table class="w-full">
          <thead class="bg-neutral-50 border-b border-neutral-200">
            <tr>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Title</th>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Source</th>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Duration</th>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Status</th>
              <th class="text-right px-6 py-4 text-sm font-semibold text-neutral-700">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-neutral-100">
            <tr v-for="clip in clips" :key="clip.id" class="hover:bg-neutral-50 transition-colors">
              <td class="px-6 py-4">
                <div class="font-medium text-neutral-900">{{ clip.title }}</div>
                <div v-if="clip.description" class="text-sm text-neutral-500 mt-1">{{ clip.description }}</div>
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium"
                  :class="clip.source_type === 'upload' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'">
                  {{ clip.source_type === 'upload' ? 'Upload' : 'URL' }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-neutral-600">
                {{ clip.duration_seconds ? formatDuration(clip.duration_seconds) : '—' }}
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium"
                  :class="clip.is_active ? 'bg-green-100 text-green-700' : 'bg-neutral-100 text-neutral-600'">
                  {{ clip.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="editClip(clip)"
                    class="p-2 text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                    title="Edit"
                  >
                    <Pencil class="w-4 h-4" />
                  </button>
                  <button
                    @click="deleteClip(clip)"
                    class="p-2 text-neutral-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                    title="Delete"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="clips.length === 0">
              <td colspan="5" class="px-6 py-12 text-center text-neutral-500">
                No audio clips yet. Click "Add Audio Clip" to create one.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Modal :show="showCreateModal || editingClip" @close="closeModal">
      <form @submit.prevent="saveClip" class="space-y-6">
        <div>
          <h2 class="text-xl font-serif font-bold text-primary-900">
            {{ editingClip ? 'Edit Audio Clip' : 'Add Audio Clip' }}
          </h2>
        </div>

        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Title *</label>
          <input
            v-model="form.title"
            type="text"
            required
            class="w-full px-4 py-2.5 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Description</label>
          <textarea
            v-model="form.description"
            rows="3"
            class="w-full px-4 py-2.5 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
          ></textarea>
        </div>

        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Source Type *</label>
          <select
            v-model="form.source_type"
            required
            class="w-full px-4 py-2.5 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
          >
            <option value="upload">Upload File</option>
            <option value="url">External URL</option>
          </select>
        </div>

        <div v-if="form.source_type === 'upload'">
          <label class="block text-sm font-medium text-neutral-700 mb-2">Audio File *</label>
          <input
            @change="handleFileChange"
            type="file"
            accept="audio/*"
            :required="!editingClip || !editingClip.file_path"
            class="w-full px-4 py-2.5 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
          />
          <p class="text-xs text-neutral-500 mt-1">Accepted: MP3, MP4, M4A, OGG, WAV (max 10MB)</p>
        </div>

        <div v-if="form.source_type === 'url'">
          <label class="block text-sm font-medium text-neutral-700 mb-2">External URL *</label>
          <input
            v-model="form.external_url"
            type="url"
            :required="form.source_type === 'url'"
            class="w-full px-4 py-2.5 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Duration (seconds)</label>
          <input
            v-model.number="form.duration_seconds"
            type="number"
            min="1"
            max="3600"
            class="w-full px-4 py-2.5 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
          />
        </div>

        <div class="flex items-center gap-2">
          <input
            v-model="form.is_active"
            type="checkbox"
            id="is_active"
            class="w-4 h-4 text-primary-600 border-neutral-300 rounded focus:ring-primary-500"
          />
          <label for="is_active" class="text-sm font-medium text-neutral-700">Active</label>
        </div>

        <div class="flex justify-end gap-3">
          <button
            type="button"
            @click="closeModal"
            class="px-4 py-2 text-neutral-700 bg-neutral-100 rounded-lg hover:bg-neutral-200 transition-colors"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="px-4 py-2 bg-primary-900 text-white rounded-lg hover:bg-primary-800 transition-colors disabled:opacity-50"
          >
            {{ form.processing ? 'Saving...' : (editingClip ? 'Update' : 'Create') }}
          </button>
        </div>
      </form>
    </Modal>
  </AppShell>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppShell from '@/Layouts/AppShell.vue'
import Modal from '@/Components/Modal.vue'
import { Plus, Pencil, Trash2 } from 'lucide-vue-next'

const props = defineProps({
  clips: Array,
})

const showCreateModal = ref(false)
const editingClip = ref(null)

const form = useForm({
  title: '',
  description: '',
  source_type: 'upload',
  audio_file: null,
  external_url: '',
  duration_seconds: null,
  is_active: true,
})

const formatDuration = (seconds) => {
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

const handleFileChange = (event) => {
  form.audio_file = event.target.files[0]
}

const editClip = (clip) => {
  editingClip.value = clip
  form.title = clip.title
  form.description = clip.description || ''
  form.source_type = clip.source_type
  form.external_url = clip.external_url || ''
  form.duration_seconds = clip.duration_seconds
  form.is_active = clip.is_active
  showCreateModal.value = true
}

const deleteClip = (clip) => {
  if (confirm(`Are you sure you want to delete "${clip.title}"?`)) {
    router.delete(`/admin/micro-nudges/clips/${clip.id}`, {
      preserveScroll: true,
    })
  }
}

const saveClip = () => {
  const url = editingClip.value
    ? `/admin/micro-nudges/clips/${editingClip.value.id}`
    : '/admin/micro-nudges/clips'

  const method = editingClip.value ? 'patch' : 'post'

  form[method](url, {
    preserveScroll: true,
    onSuccess: () => {
      closeModal()
    },
  })
}

const closeModal = () => {
  showCreateModal.value = false
  editingClip.value = null
  form.reset()
  form.source_type = 'upload'
  form.is_active = true
}
</script>

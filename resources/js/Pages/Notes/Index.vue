<template>
  <AppShell>
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="font-serif text-3xl font-bold text-neutral-900">My Notes</h1>
        <p class="text-neutral-600 mt-1">Capture your learnings and reflections.</p>
      </div>
      <Button @click="showCreateModal = true" class="flex items-center gap-2">
        <Plus class="w-4 h-4" />
        New Note
      </Button>
    </div>

    <!-- Filters -->
    <div class="flex gap-2 mb-6">
      <button
        v-for="scopeOption in scopeOptions"
        :key="scopeOption.value"
        @click="filterScope = scopeOption.value"
        :class="[
          'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
          filterScope === scopeOption.value
            ? 'bg-primary-600 text-white'
            : 'bg-white border border-neutral-200 text-neutral-700 hover:bg-neutral-50'
        ]"
      >
        {{ scopeOption.label }}
      </button>
    </div>

    <!-- Notes List -->
    <div v-if="filteredNotes.length === 0" class="bg-white rounded-xl border border-neutral-200 p-12 text-center">
      <FileText class="w-12 h-12 text-neutral-300 mx-auto mb-4" />
      <p class="text-neutral-500 mb-2">No notes yet</p>
      <p class="text-sm text-neutral-400">Create your first note to get started!</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="note in filteredNotes"
        :key="note.id"
        class="bg-white rounded-xl border border-neutral-200 p-6 hover:shadow-lg transition-shadow cursor-pointer"
        @click="openEditModal(note)"
      >
        <div class="flex items-start justify-between mb-3">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-1">
              <h3 class="font-bold text-neutral-900 line-clamp-1">{{ note.title }}</h3>
              <Pin v-if="note.pinned" class="w-4 h-4 text-primary-600 flex-shrink-0" />
            </div>
            <div class="flex items-center gap-2 text-xs text-neutral-500">
              <Tag class="w-3 h-3" />
              <span>{{ note.type }}</span>
              <span v-if="note.related">• {{ note.related }}</span>
            </div>
          </div>
        </div>
        
        <p class="text-neutral-600 text-sm mb-4 line-clamp-3">{{ note.preview }}</p>
        
        <div class="flex items-center justify-between text-xs text-neutral-400">
          <span>{{ note.updated_at }}</span>
          <div class="flex gap-2">
            <button
              @click.stop="deleteNote(note.id)"
              class="text-red-500 hover:text-red-700 transition-colors"
              title="Delete note"
            >
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Modal :show="showCreateModal || editingNote" @close="closeModal" max-width="2xl">
      <div class="p-6">
        <h2 class="font-serif text-2xl font-bold text-neutral-900 mb-6">
          {{ editingNote ? 'Edit Note' : 'Create New Note' }}
        </h2>

        <form @submit.prevent="saveNote">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-2">
                Title <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.title"
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
                v-model="form.content"
                rows="8"
                required
                maxlength="10000"
                class="w-full rounded-xl border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                placeholder="Write your note here..."
              ></textarea>
              <p class="text-xs text-neutral-500 mt-1">
                {{ form.content.length }} / 10,000 characters
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-2">
                Type
              </label>
              <select
                v-model="form.scope"
                class="w-full rounded-xl border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              >
                <option value="personal">Personal Note</option>
                <option value="lesson">Lesson Note</option>
                <option value="course">Course Note</option>
              </select>
            </div>

            <div class="flex items-center gap-2">
              <input
                v-model="form.pinned"
                type="checkbox"
                id="pinned"
                class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
              />
              <label for="pinned" class="text-sm text-neutral-700 cursor-pointer">
                Pin this note
              </label>
            </div>
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <Button type="button" variant="secondary" @click="closeModal">
              Cancel
            </Button>
            <Button type="submit" :loading="form.processing">
              {{ editingNote ? 'Update' : 'Create' }} Note
            </Button>
          </div>
        </form>
      </div>
    </Modal>
  </AppShell>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Button from '@/Components/Common/Button.vue';
import Modal from '@/Components/Modal.vue';
import { FileText, Plus, Tag, Pin, Trash2 } from 'lucide-vue-next';

const props = defineProps({
  notes: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  }
});

const filterScope = ref(props.filters.scope || 'all');
const showCreateModal = ref(false);
const editingNote = ref(null);

const scopeOptions = [
  { value: 'all', label: 'All Notes' },
  { value: 'personal', label: 'Personal' },
  { value: 'lesson', label: 'Lesson Notes' },
  { value: 'course', label: 'Course Notes' },
];

const form = useForm({
  title: '',
  content: '',
  scope: 'personal',
  pinned: false,
  noteable_type: null,
  noteable_id: null,
});

const filteredNotes = computed(() => {
  if (filterScope.value === 'all') {
    return props.notes;
  }
  return props.notes.filter(note => note.scope === filterScope.value);
});

function openEditModal(note) {
  editingNote.value = note;
  form.title = note.title;
  form.content = note.content;
  form.scope = note.scope;
  form.pinned = note.pinned;
  showCreateModal.value = true;
}

function closeModal() {
  showCreateModal.value = false;
  editingNote.value = null;
  form.reset();
}

function saveNote() {
  if (editingNote.value) {
    form.put(route('notes.update', editingNote.value.id), {
      preserveScroll: true,
      onSuccess: () => {
        closeModal();
      }
    });
  } else {
    form.post(route('notes.store'), {
      preserveScroll: true,
      onSuccess: () => {
        closeModal();
      }
    });
  }
}

function deleteNote(noteId) {
  if (confirm('Are you sure you want to delete this note?')) {
    router.delete(route('notes.destroy', noteId), {
      preserveScroll: true
    });
  }
}

// Update filter when route changes
onMounted(() => {
  if (props.filters.scope) {
    filterScope.value = props.filters.scope;
  }
});
</script>

<template>
  <Transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="translate-x-full opacity-0"
    enter-to-class="translate-x-0 opacity-100"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="translate-x-0 opacity-100"
    leave-to-class="translate-x-full opacity-0"
  >
    <div
      v-if="isOpen"
      class="fixed right-0 top-[4rem] h-[calc(100vh-4rem)] w-full md:w-96 bg-white border-l border-neutral-200 shadow-xl z-50 flex flex-col"
    >
    <!-- Header -->
    <div class="flex items-center justify-between p-4 border-b border-neutral-200 bg-gradient-to-r from-[#8B0000] to-[#D4AF37]">
      <div class="flex items-center gap-2">
        <FileText class="w-5 h-5 text-white" />
        <h3 class="font-semibold text-white">My Notes</h3>
      </div>
      <div class="flex items-center gap-2">
        <span
          v-if="saving"
          class="text-xs text-white/80 flex items-center gap-1 bg-white/20 px-2 py-1 rounded"
        >
          <Loader2 class="w-3 h-3 animate-spin" />
          Saving...
        </span>
        <span
          v-else-if="lastSaved"
          class="text-xs text-white/80 bg-white/20 px-2 py-1 rounded flex items-center gap-1"
        >
          <CheckCircle2 class="w-3 h-3" />
          Saved {{ formatLastSaved() }}
        </span>
        <button
          @click="$emit('close')"
          class="text-white hover:text-gray-200 transition-colors"
        >
          <X class="w-5 h-5" />
        </button>
      </div>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto p-4">
      <textarea
        v-model="noteContent"
        @input="onInput"
        @blur="saveImmediately"
        placeholder="Start typing your notes here...&#10;&#10;Your notes will be automatically saved every 30 seconds."
        class="w-full h-full min-h-[400px] p-4 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none text-sm leading-relaxed"
        :disabled="loading"
      ></textarea>
    </div>

    <!-- Footer -->
    <div class="p-4 border-t border-neutral-200 bg-neutral-50">
      <div class="flex items-center justify-between text-xs text-neutral-500">
        <span>{{ characterCount }} characters</span>
        <span v-if="autoSaveEnabled" class="flex items-center gap-1">
          <Clock class="w-3 h-3" />
          Auto-save enabled
        </span>
      </div>
    </div>
    </div>
  </Transition>

  <!-- Toggle Button (when closed) -->
  <button
    v-if="!isOpen"
    @click="$emit('open')"
    class="fixed right-4 bottom-4 md:right-6 md:bottom-6 bg-gradient-to-r from-[#8B0000] to-[#D4AF37] text-white p-4 rounded-full shadow-lg hover:shadow-xl transition-all hover:scale-105 z-40 flex items-center gap-2 group"
    title="Open Notes (Ctrl+N)"
  >
    <FileText class="w-5 h-5 group-hover:scale-110 transition-transform" />
    <span class="hidden md:inline font-medium">My Notes</span>
    <span class="hidden lg:inline text-xs opacity-75 ml-1">(Ctrl+N)</span>
  </button>

  <!-- Backdrop (when open on mobile) -->
  <Transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="isOpen"
      @click="$emit('close')"
      class="fixed inset-0 bg-black/50 z-40 md:hidden"
    ></div>
  </Transition>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { FileText, X, Loader2, Clock, CheckCircle2 } from 'lucide-vue-next';
import axios from 'axios';
import { route } from 'ziggy-js';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  lessonId: {
    type: Number,
    required: true,
  },
  initialContent: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['open', 'close']);

const noteContent = ref(props.initialContent || '');
const saving = ref(false);
const loading = ref(false);
const lastSaved = ref(null);
const autoSaveEnabled = ref(true);
let autoSaveTimer = null;
let saveTimeout = null;
const AUTO_SAVE_INTERVAL = 30000; // 30 seconds

const characterCount = computed(() => {
  return noteContent.value.length;
});

const formatLastSaved = () => {
  if (!lastSaved.value) return null;
  const now = new Date();
  const saved = new Date(lastSaved.value);
  const diffSeconds = Math.floor((now - saved) / 1000);

  if (diffSeconds < 10) {
    return 'just now';
  } else if (diffSeconds < 60) {
    return `${diffSeconds}s ago`;
  } else if (diffSeconds < 3600) {
    const minutes = Math.floor(diffSeconds / 60);
    return `${minutes}m ago`;
  } else {
    const hours = Math.floor(diffSeconds / 3600);
    return `${hours}h ago`;
  }
};

const saveNote = async (immediate = false) => {
  if (saving.value) {
    return; // Already saving
  }

  // Allow saving empty notes (user might want to clear their note)

  saving.value = true;

  try {
    const url = route('student-notes.store', { lesson: props.lessonId });
    const response = await axios.post(url, {
      content: noteContent.value,
    });

    lastSaved.value = new Date().toISOString();
    
    if (immediate) {
      // Clear auto-save timer and restart it
      if (autoSaveTimer) {
        clearTimeout(autoSaveTimer);
      }
      startAutoSave();
    }
  } catch (error) {
    console.error('Failed to save note:', error);
    // Show error message to user
    if (window.showError) {
      window.showError('Failed to save note. Please try again.');
    } else {
      alert('Failed to save note. Please try again.');
    }
  } finally {
    saving.value = false;
  }
};

const onInput = () => {
  // Clear existing timeout
  if (saveTimeout) {
    clearTimeout(saveTimeout);
  }

  // Debounce: save after 2 seconds of no typing
  saveTimeout = setTimeout(() => {
    if (autoSaveEnabled.value) {
      saveNote();
    }
  }, 2000);
};

const saveImmediately = () => {
  // Save immediately when user clicks away
  if (saveTimeout) {
    clearTimeout(saveTimeout);
  }
  saveNote(true);
};

const startAutoSave = () => {
  if (!autoSaveEnabled.value) return;

  // Clear existing timer
  if (autoSaveTimer) {
    clearTimeout(autoSaveTimer);
  }

  // Set new timer
  autoSaveTimer = setTimeout(() => {
    if (noteContent.value.trim()) {
      saveNote();
    }
    startAutoSave(); // Restart timer
  }, AUTO_SAVE_INTERVAL);
};

const stopAutoSave = () => {
  if (autoSaveTimer) {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = null;
  }
  if (saveTimeout) {
    clearTimeout(saveTimeout);
    saveTimeout = null;
  }
};

// Watch for initial content changes
watch(() => props.initialContent, (newContent) => {
  if (newContent !== noteContent.value) {
    noteContent.value = newContent || '';
  }
});

// Watch for panel open/close
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    // Load existing note when panel opens
    loadNote();
    startAutoSave();
  } else {
    // Save before closing
    saveImmediately();
    stopAutoSave();
  }
});

const loadNote = async () => {
  loading.value = true;
  try {
    const response = await axios.get(route('student-notes.show', { lesson: props.lessonId }));
    if (response.data?.content) {
      noteContent.value = response.data.content;
      lastSaved.value = response.data.updated_at;
    }
  } catch (error) {
    // Note doesn't exist yet, that's fine
    if (error.response?.status !== 404) {
      console.error('Failed to load note:', error);
    }
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  if (props.isOpen) {
    loadNote();
    startAutoSave();
  }
});

onBeforeUnmount(() => {
  stopAutoSave();
  saveImmediately(); // Final save before component unmounts
});
</script>

<style scoped>
/* Custom scrollbar for textarea */
textarea::-webkit-scrollbar {
  width: 8px;
}

textarea::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

textarea::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

textarea::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>

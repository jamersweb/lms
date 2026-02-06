<template>
  <Modal :show="show" @close="close" max-width="2xl">
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div v-if="show" class="bg-white rounded-xl overflow-hidden">
      <!-- Header -->
      <div class="bg-gradient-to-r from-[#8B0000] to-[#D4AF37] px-6 py-4">
        <div class="flex items-center justify-between">
          <h2 class="text-2xl font-serif font-bold text-white">Post-Lesson Summary</h2>
          <button
            @click="close"
            class="text-white hover:text-gray-200 transition-colors"
          >
            <X class="w-6 h-6" />
          </button>
        </div>
        <p class="text-white/90 mt-1">{{ lessonTitle }}</p>
      </div>

      <!-- Content -->
      <div class="p-6 max-h-[70vh] overflow-y-auto">
        <!-- Sunnah Pointers -->
        <div v-if="resources?.sunnah_pointers" class="mb-6">
          <h3 class="text-xl font-semibold text-[#8B0000] mb-3 flex items-center gap-2">
            <BookOpen class="w-5 h-5" />
            Sunnah Pointers
          </h3>
          <div class="bg-red-50 border-r-4 border-[#8B0000] rounded-lg p-4">
            <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">
              {{ resources.sunnah_pointers }}
            </p>
          </div>
        </div>

        <!-- Duas -->
        <div v-if="resources?.duas_text" class="mb-6">
          <h3 class="text-xl font-semibold text-[#8B0000] mb-3 flex items-center gap-2">
            <Heart class="w-5 h-5" />
            Duas
          </h3>
          <div class="bg-amber-50 border-r-4 border-[#D4AF37] rounded-lg p-4">
            <p class="text-gray-700 whitespace-pre-wrap leading-relaxed text-right" dir="rtl">
              {{ resources.duas_text }}
            </p>
          </div>
        </div>

        <!-- Audio Player -->
        <div v-if="resources?.audio_path" class="mb-6">
          <h3 class="text-xl font-semibold text-[#8B0000] mb-3 flex items-center gap-2">
            <Volume2 class="w-5 h-5" />
            Dua Pronunciation
          </h3>
          <audio controls class="w-full">
            <source :src="resources.audio_path" type="audio/mpeg">
            Your browser does not support the audio element.
          </audio>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-8">
          <Loader2 class="w-8 h-8 text-gray-400 mx-auto mb-4 animate-spin" />
          <p class="text-gray-500">Loading resources...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="!resources || (!resources.sunnah_pointers && !resources.duas_text)" class="text-center py-8">
          <FileText class="w-16 h-16 text-gray-300 mx-auto mb-4" />
          <p class="text-gray-500">No resources available for this lesson.</p>
        </div>

        <!-- Access Denied -->
        <div v-else-if="resources && resources.can_view === false" class="text-center py-8">
          <p class="text-amber-600 font-medium">Complete the lesson to view resources.</p>
        </div>
      </div>

      <!-- Footer Actions -->
      <div class="border-t border-gray-200 px-6 py-4 bg-gray-50 flex items-center justify-between">
        <button
          @click="close"
          class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors"
        >
          Close
        </button>
        <button
          v-if="resources && resources.can_view && (resources.sunnah_pointers || resources.duas_text)"
          @click="downloadPdf"
          :disabled="downloading"
          class="px-6 py-2 bg-gradient-to-r from-[#8B0000] to-[#D4AF37] text-white rounded-lg font-medium hover:opacity-90 transition-opacity disabled:opacity-50 flex items-center gap-2"
        >
          <Download v-if="!downloading" class="w-4 h-4" />
          <Loader2 v-else class="w-4 h-4 animate-spin" />
          {{ downloading ? 'Downloading...' : 'Download PDF' }}
        </button>
      </div>
      </div>
    </Transition>
  </Modal>
</template>

<script setup>
import Modal from '@/Components/Modal.vue';
import { X, BookOpen, Heart, Volume2, FileText, Download, Loader2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import axios from 'axios';
import { route } from 'ziggy-js';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  lessonId: {
    type: Number,
    required: true,
  },
  lessonTitle: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['close']);

const resources = ref(null);
const downloading = ref(false);
const loading = ref(false);

const close = () => {
  emit('close');
};

const fetchResources = async () => {
  if (!props.lessonId) return;
  
  loading.value = true;
  try {
    const response = await axios.get(route('lessons.resources.show', { lesson: props.lessonId }));
    resources.value = response.data;
  } catch (error) {
    console.error('Failed to fetch lesson resources:', error);
    resources.value = null;
  } finally {
    loading.value = false;
  }
};

const downloadPdf = async () => {
  downloading.value = true;
  try {
    const url = route('lessons.resources.pdf', { lesson: props.lessonId });
    const response = await axios.get(url, {
      responseType: 'blob',
    });
    
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = `lesson-resources-${props.lessonTitle.replace(/\s+/g, '-')}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (error) {
    console.error('Failed to download PDF:', error);
    if (window.showError) {
      window.showError('Failed to download PDF. Please try again.');
    } else {
      alert('Failed to download PDF. Please try again.');
    }
  } finally {
    downloading.value = false;
  }
};

watch(() => props.show, (newVal) => {
  if (newVal) {
    fetchResources();
  }
});
</script>

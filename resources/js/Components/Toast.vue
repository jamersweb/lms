<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0 translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        :class="[
          'fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 min-w-[300px] max-w-md',
          type === 'success' ? 'bg-green-600 text-white' : '',
          type === 'error' ? 'bg-red-600 text-white' : '',
          type === 'warning' ? 'bg-amber-600 text-white' : '',
          type === 'info' ? 'bg-blue-600 text-white' : '',
        ]"
      >
        <component :is="iconComponent" class="w-5 h-5 shrink-0" />
        <p class="flex-1 text-sm font-medium">{{ message }}</p>
        <button
          @click="close"
          class="text-white/80 hover:text-white transition-colors shrink-0"
        >
          <X class="w-4 h-4" />
        </button>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { CheckCircle2, XCircle, AlertTriangle, Info, X } from 'lucide-vue-next';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  message: {
    type: String,
    required: true,
  },
  type: {
    type: String,
    default: 'info',
    validator: (value) => ['success', 'error', 'warning', 'info'].includes(value),
  },
  duration: {
    type: Number,
    default: 5000, // 5 seconds
  },
});

const emit = defineEmits(['close']);

const iconComponent = computed(() => {
  switch (props.type) {
    case 'success':
      return CheckCircle2;
    case 'error':
      return XCircle;
    case 'warning':
      return AlertTriangle;
    default:
      return Info;
  }
});

let timeoutId = null;

const close = () => {
  emit('close');
};

watch(() => props.show, (newVal) => {
  if (newVal && props.duration > 0) {
    if (timeoutId) {
      clearTimeout(timeoutId);
    }
    timeoutId = setTimeout(() => {
      close();
    }, props.duration);
  }
});

onBeforeUnmount(() => {
  if (timeoutId) {
    clearTimeout(timeoutId);
  }
});
</script>

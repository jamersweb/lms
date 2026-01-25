<template>
  <component
    :is="isLink ? 'Link' : 'button'"
    :href="href"
    :type="!isLink ? type : undefined"
    :class="[
      'inline-flex items-center justify-center font-medium transition-all duration-200 focus:outline-none focus:ring-4 disabled:opacity-50 disabled:cursor-not-allowed',
      // Sizes
      size === 'sm' ? 'px-3 py-1.5 text-xs rounded-md' : '',
      size === 'md' ? 'px-5 py-2.5 text-sm rounded-lg' : '',
      size === 'lg' ? 'px-6 py-3 text-base rounded-lg' : '',
      // Variants
      variant === 'primary' ? 'bg-primary-900 text-white hover:bg-primary-800 active:bg-primary-950 shadow-sm hover:shadow focus:ring-primary-200' : '',
      variant === 'secondary' ? 'bg-white text-neutral-700 border border-neutral-300 hover:bg-neutral-50 hover:text-neutral-900 focus:ring-neutral-100' : '',
      variant === 'ghost' ? 'text-neutral-600 hover:text-primary-900 hover:bg-primary-50 focus:ring-primary-50' : '',
      variant === 'destructive' ? 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-100' : '',
      variant === 'gold' ? 'bg-secondary-500 text-white hover:bg-secondary-600 active:bg-secondary-700 shadow-sm hover:shadow focus:ring-secondary-200' : '',
      // Full Width
      fullWidth ? 'w-full' : ''
    ]"
    :disabled="disabled || loading"
  >
    <svg 
      v-if="loading" 
      class="animate-spin -ml-1 mr-2 h-4 w-4" 
      xmlns="http://www.w3.org/2000/svg" 
      fill="none" 
      viewBox="0 0 24 24"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    
    <slot />
  </component>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'ghost', 'destructive', 'gold'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
  type: {
    type: String,
    default: 'button',
  },
  href: {
    type: String,
    default: null,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  fullWidth: {
    type: Boolean,
    default: false,
  },
});

const isLink = computed(() => !!props.href);
</script>

<template>
  <span 
    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
    :class="[
      variant === 'success' ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : '',
      variant === 'warning' ? 'bg-amber-50 text-amber-700 ring-amber-600/20' : '',
      variant === 'error' ? 'bg-red-50 text-red-700 ring-red-600/20' : '',
      variant === 'info' ? 'bg-blue-50 text-blue-700 ring-blue-700/10' : '',
      variant === 'neutral' ? 'bg-neutral-50 text-neutral-600 ring-neutral-500/10' : '',
      variant === 'primary' ? 'bg-primary-50 text-primary-700 ring-primary-600/20' : '',
    ]"
  >
    <slot />
  </span>
</template>

<script setup>
defineProps({
  variant: {
    type: String,
    default: 'neutral',
    validator: (value) => ['success', 'warning', 'error', 'info', 'neutral', 'primary'].includes(value),
  }
});
</script>

<template>
  <div 
    class="bg-white rounded-xl border border-neutral-100 shadow-sm transition-shadow duration-300"
    :class="[
      hoverable ? 'hover:shadow-md hover:-translate-y-[2px]' : '',
      noPadding ? '' : 'p-6'
    ]"
  >
    <div v-if="$slots.header" class="px-6 py-4 border-b border-neutral-100">
        <slot name="header" />
    </div>
    
    <div :class="noPadding ? 'p-0' : ''">
        <slot />
    </div>

    <div v-if="$slots.footer" class="px-6 py-4 bg-neutral-50 border-t border-neutral-100 rounded-b-xl">
        <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  hoverable: {
    type: Boolean,
    default: false
  },
  noPadding: {
    type: Boolean,
    default: false
  }
});
</script>

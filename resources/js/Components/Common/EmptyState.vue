<template>
  <div class="flex flex-col items-center justify-center py-12 px-4 text-center rounded-xl border-2 border-dashed border-neutral-200 bg-neutral-50/50">
    <div class="h-16 w-16 bg-white rounded-full flex items-center justify-center shadow-sm mb-4">
      <component :is="icon" class="h-8 w-8 text-neutral-400" />
    </div>
    <h3 class="text-lg font-bold text-neutral-900 mb-1">{{ title }}</h3>
    <p class="text-neutral-500 max-w-sm mb-6">{{ description }}</p>
    <slot name="action" />
  </div>
</template>

<script setup>
defineProps({
  icon: Object,
  title: String,
  description: String
});
</script>

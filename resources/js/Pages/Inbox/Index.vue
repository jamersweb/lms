<template>
  <AppShell>
    <Head title="Inbox" />

    <div class="max-w-4xl mx-auto space-y-6">
      <div>
        <h1 class="text-2xl font-serif font-bold text-primary-900">Inbox</h1>
        <p class="text-neutral-600 mt-1">Your announcements and messages</p>
      </div>

      <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <div v-if="broadcasts.length === 0" class="px-6 py-12 text-center text-neutral-500">
          No messages yet.
        </div>

        <div v-else class="divide-y divide-neutral-100">
          <Link
            v-for="broadcast in broadcasts"
            :key="broadcast.id"
            :href="`/inbox/${broadcast.id}`"
            class="block px-6 py-4 hover:bg-neutral-50 transition-colors"
            :class="{ 'bg-primary-50': !broadcast.is_read }"
          >
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <h3 class="font-semibold text-neutral-900">{{ broadcast.title }}</h3>
                  <span v-if="!broadcast.is_read" class="w-2 h-2 bg-primary-600 rounded-full"></span>
                </div>
                <p class="text-sm text-neutral-600 mt-1 line-clamp-2">{{ broadcast.body }}</p>
                <p class="text-xs text-neutral-500 mt-2">{{ formatDate(broadcast.created_at) }}</p>
              </div>
            </div>
          </Link>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppShell from '@/Layouts/AppShell.vue'

const props = defineProps({
  broadcasts: Array,
})

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}
</script>

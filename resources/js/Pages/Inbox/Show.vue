<template>
  <AppShell>
    <Head :title="broadcast.title" />

    <div class="max-w-4xl mx-auto space-y-6">
      <div>
        <Link href="/inbox" class="inline-flex items-center gap-2 text-neutral-600 hover:text-primary-600 mb-4">
          <ArrowLeft class="w-4 h-4" />
          Back to Inbox
        </Link>
        <h1 class="text-2xl font-serif font-bold text-primary-900">{{ broadcast.title }}</h1>
        <p class="text-neutral-600 mt-1">{{ formatDate(broadcast.created_at) }}</p>
      </div>

      <div class="bg-white rounded-xl border border-neutral-200 p-6">
        <div class="prose max-w-none">
          <p class="whitespace-pre-wrap">{{ broadcast.body }}</p>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppShell from '@/Layouts/AppShell.vue'
import { ArrowLeft } from 'lucide-vue-next'

const props = defineProps({
  broadcast: Object,
})

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}
</script>

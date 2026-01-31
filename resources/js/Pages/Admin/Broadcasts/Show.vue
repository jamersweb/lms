<template>
  <AppShell>
    <Head :title="broadcast.title" />

    <div class="max-w-4xl mx-auto space-y-6">
      <div>
        <Link href="/admin/broadcasts" class="inline-flex items-center gap-2 text-neutral-600 hover:text-primary-600 mb-4">
          <ArrowLeft class="w-4 h-4" />
          Back to Broadcasts
        </Link>
        <h1 class="text-2xl font-serif font-bold text-primary-900">{{ broadcast.title }}</h1>
        <p class="text-neutral-600 mt-1">Status: {{ broadcast.status }}</p>
      </div>

      <div class="bg-white rounded-xl border border-neutral-200 p-6 space-y-6">
        <!-- Content -->
        <div>
          <h3 class="text-lg font-semibold text-neutral-900 mb-2">Message</h3>
          <div class="prose max-w-none">
            <p class="whitespace-pre-wrap">{{ broadcast.body }}</p>
          </div>
        </div>

        <!-- Channels -->
        <div>
          <h3 class="text-lg font-semibold text-neutral-900 mb-2">Channels</h3>
          <div class="flex flex-wrap gap-2">
            <span v-for="channel in broadcast.channels" :key="channel"
              class="inline-flex items-center px-3 py-1 rounded-md text-sm font-medium bg-primary-100 text-primary-700">
              {{ channel }}
            </span>
          </div>
        </div>

        <!-- Delivery Stats -->
        <div v-if="broadcast.delivery_stats" class="border-t border-neutral-200 pt-6">
          <h3 class="text-lg font-semibold text-neutral-900 mb-4">Delivery Statistics</h3>
          <div class="space-y-3">
            <div v-for="(stats, channel) in broadcast.delivery_stats" :key="channel" class="bg-neutral-50 rounded-lg p-4">
              <div class="font-medium text-neutral-900 mb-2">{{ channel }}</div>
              <div class="text-sm text-neutral-600 space-y-1">
                <div v-for="(count, status) in stats" :key="status">
                  {{ status }}: {{ count }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div v-if="broadcast.status === 'draft'" class="flex justify-end gap-3 pt-6 border-t border-neutral-200">
          <button
            @click="sendBroadcast"
            class="px-4 py-2 bg-primary-900 text-white rounded-lg hover:bg-primary-800 transition-colors"
          >
            Send Broadcast
          </button>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppShell from '@/Layouts/AppShell.vue'
import { ArrowLeft } from 'lucide-vue-next'

const props = defineProps({
  broadcast: Object,
})

const sendBroadcast = () => {
  if (confirm('Are you sure you want to send this broadcast? This action cannot be undone.')) {
    router.post(`/admin/broadcasts/${props.broadcast.id}/send`, {}, {
      preserveScroll: true,
    })
  }
}
</script>

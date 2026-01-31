<template>
  <AppShell>
    <Head title="Admin - Broadcasts" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-serif font-bold text-primary-900">Broadcasts</h1>
          <p class="text-neutral-600 mt-1">Send announcements to selected groups</p>
        </div>
        <Link
          href="/admin/broadcasts/create"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-900 text-white rounded-lg font-medium hover:bg-primary-800 transition-colors"
        >
          <Plus class="w-5 h-5" />
          Create Broadcast
        </Link>
      </div>

      <!-- Broadcasts Table -->
      <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <table class="w-full">
          <thead class="bg-neutral-50 border-b border-neutral-200">
            <tr>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Title</th>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Channels</th>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Status</th>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Deliveries</th>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Created</th>
              <th class="text-right px-6 py-4 text-sm font-semibold text-neutral-700">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-neutral-100">
            <tr v-for="broadcast in broadcasts" :key="broadcast.id" class="hover:bg-neutral-50 transition-colors">
              <td class="px-6 py-4">
                <div class="font-medium text-neutral-900">{{ broadcast.title }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-wrap gap-1.5">
                  <span v-for="channel in broadcast.channels" :key="channel"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium"
                    :class="getChannelClass(channel)">
                    {{ channel }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium"
                  :class="getStatusClass(broadcast.status)">
                  {{ broadcast.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-neutral-600">
                {{ broadcast.deliveries_count || 0 }}
              </td>
              <td class="px-6 py-4 text-sm text-neutral-600">
                {{ formatDate(broadcast.created_at) }}
              </td>
              <td class="px-6 py-4">
                <Link
                  :href="`/admin/broadcasts/${broadcast.id}`"
                  class="inline-flex items-center gap-1 px-3 py-1.5 text-sm text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                >
                  View
                </Link>
              </td>
            </tr>
            <tr v-if="broadcasts.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-neutral-500">
                No broadcasts yet. Click "Create Broadcast" to get started.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppShell from '@/Layouts/AppShell.vue'
import { Plus } from 'lucide-vue-next'

const props = defineProps({
  broadcasts: Array,
})

const getChannelClass = (channel) => {
  const classes = {
    email: 'bg-blue-100 text-blue-700',
    whatsapp: 'bg-green-100 text-green-700',
    in_app: 'bg-purple-100 text-purple-700',
  }
  return classes[channel] || 'bg-neutral-100 text-neutral-600'
}

const getStatusClass = (status) => {
  const classes = {
    draft: 'bg-neutral-100 text-neutral-600',
    scheduled: 'bg-yellow-100 text-yellow-700',
    sending: 'bg-blue-100 text-blue-700',
    sent: 'bg-green-100 text-green-700',
  }
  return classes[status] || 'bg-neutral-100 text-neutral-600'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}
</script>

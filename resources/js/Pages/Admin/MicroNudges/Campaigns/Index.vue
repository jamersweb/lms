<template>
  <AppShell>
    <Head title="Admin - Micro Nudge Campaigns" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-serif font-bold text-primary-900">Micro Nudge Campaigns</h1>
          <p class="text-neutral-600 mt-1">Schedule "Sunnah of the Hour" audio nudges</p>
        </div>
        <Link
          href="/admin/micro-nudges/campaigns/create"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-900 text-white rounded-lg font-medium hover:bg-primary-800 transition-colors"
        >
          <Plus class="w-5 h-5" />
          Create Campaign
        </Link>
      </div>

      <!-- Campaigns Table -->
      <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <table class="w-full">
          <thead class="bg-neutral-50 border-b border-neutral-200">
            <tr>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Campaign</th>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Schedule</th>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Rotation</th>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Deliveries</th>
              <th class="text-left px-6 py-4 text-sm font-semibold text-neutral-700">Status</th>
              <th class="text-right px-6 py-4 text-sm font-semibold text-neutral-700">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-neutral-100">
            <tr v-for="campaign in campaigns" :key="campaign.id" class="hover:bg-neutral-50 transition-colors">
              <td class="px-6 py-4">
                <div class="font-medium text-neutral-900">{{ campaign.name }}</div>
              </td>
              <td class="px-6 py-4 text-sm text-neutral-600">
                <div>{{ campaign.schedule_type === 'hourly' ? 'Hourly' : 'Daily' }}</div>
                <div v-if="campaign.schedule_type === 'daily'" class="text-xs text-neutral-500">
                  {{ formatTime(campaign.send_hour, campaign.send_minute) }}
                </div>
                <div v-else class="text-xs text-neutral-500">
                  :{{ campaign.send_minute.toString().padStart(2, '0') }} every hour
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700">
                  {{ campaign.rotation === 'random' ? 'Random' : 'Sequence' }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-neutral-600">
                {{ campaign.deliveries_count || 0 }}
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium"
                  :class="campaign.is_enabled ? 'bg-green-100 text-green-700' : 'bg-neutral-100 text-neutral-600'">
                  {{ campaign.is_enabled ? 'Enabled' : 'Disabled' }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-2">
                  <Link
                    :href="`/admin/micro-nudges/campaigns/${campaign.id}/edit`"
                    class="p-2 text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                    title="Edit"
                  >
                    <Pencil class="w-4 h-4" />
                  </Link>
                  <button
                    @click="deleteCampaign(campaign)"
                    class="p-2 text-neutral-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                    title="Delete"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="campaigns.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-neutral-500">
                No campaigns yet. Click "Create Campaign" to get started.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppShell from '@/Layouts/AppShell.vue'
import { Plus, Pencil, Trash2 } from 'lucide-vue-next'

const props = defineProps({
  campaigns: Array,
})

const formatTime = (hour, minute) => {
  const h = hour || 0
  const m = minute || 0
  return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`
}

const deleteCampaign = (campaign) => {
  if (confirm(`Are you sure you want to delete "${campaign.name}"?`)) {
    router.delete(`/admin/micro-nudges/campaigns/${campaign.id}`, {
      preserveScroll: true,
    })
  }
}
</script>

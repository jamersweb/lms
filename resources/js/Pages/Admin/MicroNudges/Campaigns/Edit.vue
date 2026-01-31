<template>
  <AppShell>
    <Head :title="campaign ? 'Edit Campaign' : 'Create Campaign'" />

    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-2xl font-serif font-bold text-primary-900">
          {{ campaign ? 'Edit Campaign' : 'Create Campaign' }}
        </h1>
        <p class="text-neutral-600 mt-1">Configure micro-nudge campaign settings</p>
      </div>

      <form @submit.prevent="saveCampaign" class="bg-white rounded-xl border border-neutral-200 p-6 space-y-6">
        <!-- Basic Info -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Campaign Name *</label>
          <input
            v-model="form.name"
            type="text"
            required
            class="w-full px-4 py-2.5 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
          />
        </div>

        <div class="flex items-center gap-2">
          <input
            v-model="form.is_enabled"
            type="checkbox"
            id="is_enabled"
            class="w-4 h-4 text-primary-600 border-neutral-300 rounded focus:ring-primary-500"
          />
          <label for="is_enabled" class="text-sm font-medium text-neutral-700">Enabled</label>
        </div>

        <!-- Schedule -->
        <div class="border-t border-neutral-200 pt-6">
          <h3 class="text-lg font-semibold text-neutral-900 mb-4">Schedule</h3>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-2">Schedule Type *</label>
              <select
                v-model="form.schedule_type"
                required
                class="w-full px-4 py-2.5 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              >
                <option value="hourly">Hourly</option>
                <option value="daily">Daily</option>
              </select>
            </div>

            <div v-if="form.schedule_type === 'daily'" class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-neutral-700 mb-2">Send Hour (0-23) *</label>
                <input
                  v-model.number="form.send_hour"
                  type="number"
                  min="0"
                  max="23"
                  required
                  class="w-full px-4 py-2.5 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-neutral-700 mb-2">Send Minute (0-59) *</label>
                <input
                  v-model.number="form.send_minute"
                  type="number"
                  min="0"
                  max="59"
                  required
                  class="w-full px-4 py-2.5 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
                />
              </div>
            </div>

            <div v-else>
              <label class="block text-sm font-medium text-neutral-700 mb-2">Send Minute (0-59) *</label>
              <input
                v-model.number="form.send_minute"
                type="number"
                min="0"
                max="59"
                required
                class="w-full px-4 py-2.5 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-2">Timezone</label>
              <input
                v-model="form.timezone"
                type="text"
                placeholder="e.g., America/New_York (optional)"
                class="w-full px-4 py-2.5 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              />
            </div>
          </div>
        </div>

        <!-- Rotation -->
        <div class="border-t border-neutral-200 pt-6">
          <h3 class="text-lg font-semibold text-neutral-900 mb-4">Rotation</h3>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Rotation Strategy *</label>
            <select
              v-model="form.rotation"
              required
              class="w-full px-4 py-2.5 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
            >
              <option value="random">Random</option>
              <option value="sequence">Sequence</option>
            </select>
          </div>
        </div>

        <!-- Audience Filters -->
        <div class="border-t border-neutral-200 pt-6">
          <h3 class="text-lg font-semibold text-neutral-900 mb-4">Audience Filters</h3>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-2">Minimum Level</label>
              <select
                v-model="form.audience_filters.min_level"
                class="w-full px-4 py-2.5 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              >
                <option :value="null">No minimum</option>
                <option value="beginner">Beginner</option>
                <option value="intermediate">Intermediate</option>
                <option value="expert">Expert</option>
              </select>
            </div>

            <div class="flex items-center gap-2">
              <input
                v-model="form.audience_filters.requires_bayah"
                type="checkbox"
                id="requires_bayah"
                class="w-4 h-4 text-primary-600 border-neutral-300 rounded focus:ring-primary-500"
              />
              <label for="requires_bayah" class="text-sm font-medium text-neutral-700">Requires Bay'ah</label>
            </div>

            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-2">Gender</label>
              <select
                v-model="form.audience_filters.gender"
                class="w-full px-4 py-2.5 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              >
                <option :value="null">All genders</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Audio Clips -->
        <div class="border-t border-neutral-200 pt-6">
          <h3 class="text-lg font-semibold text-neutral-900 mb-4">Audio Clips</h3>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Select Clips (leave empty to use all active clips)</label>
            <div class="space-y-2 max-h-64 overflow-y-auto border border-neutral-200 rounded-lg p-4">
              <label
                v-for="clip in audioClips"
                :key="clip.id"
                class="flex items-center gap-2 p-2 hover:bg-neutral-50 rounded cursor-pointer"
              >
                <input
                  v-model="form.clip_ids"
                  type="checkbox"
                  :value="clip.id"
                  class="w-4 h-4 text-primary-600 border-neutral-300 rounded focus:ring-primary-500"
                />
                <span class="text-sm text-neutral-700">{{ clip.title }}</span>
                <span v-if="clip.duration_seconds" class="text-xs text-neutral-500 ml-auto">
                  {{ formatDuration(clip.duration_seconds) }}
                </span>
              </label>
              <p v-if="audioClips.length === 0" class="text-sm text-neutral-500">No active audio clips available.</p>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-6 border-t border-neutral-200">
          <Link
            href="/admin/micro-nudges/campaigns"
            class="px-4 py-2 text-neutral-700 bg-neutral-100 rounded-lg hover:bg-neutral-200 transition-colors"
          >
            Cancel
          </Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="px-4 py-2 bg-primary-900 text-white rounded-lg hover:bg-primary-800 transition-colors disabled:opacity-50"
          >
            {{ form.processing ? 'Saving...' : (campaign ? 'Update' : 'Create') }}
          </button>
        </div>
      </form>
    </div>
  </AppShell>
</template>

<script setup>
import { reactive } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppShell from '@/Layouts/AppShell.vue'

const props = defineProps({
  campaign: Object,
  audioClips: Array,
})

const form = useForm({
  name: props.campaign?.name || '',
  is_enabled: props.campaign?.is_enabled ?? true,
  schedule_type: props.campaign?.schedule_type || 'hourly',
  send_hour: props.campaign?.send_hour || null,
  send_minute: props.campaign?.send_minute || 0,
  timezone: props.campaign?.timezone || null,
  rotation: props.campaign?.rotation || 'random',
  audience_filters: {
    min_level: props.campaign?.audience_filters?.min_level || null,
    requires_bayah: props.campaign?.audience_filters?.requires_bayah || false,
    gender: props.campaign?.audience_filters?.gender || null,
  },
  clip_ids: props.campaign?.clip_ids || [],
})

const formatDuration = (seconds) => {
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

const saveCampaign = () => {
  const url = props.campaign
    ? `/admin/micro-nudges/campaigns/${props.campaign.id}`
    : '/admin/micro-nudges/campaigns'

  const method = props.campaign ? 'patch' : 'post'

  form[method](url, {
    preserveScroll: true,
    onSuccess: () => {
      // Redirect handled by Inertia
    },
  })
}
</script>

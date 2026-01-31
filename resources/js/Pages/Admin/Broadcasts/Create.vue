<template>
  <AppShell>
    <Head title="Create Broadcast" />

    <div class="max-w-4xl mx-auto space-y-6">
      <div>
        <h1 class="text-2xl font-serif font-bold text-primary-900">Create Broadcast</h1>
        <p class="text-neutral-600 mt-1">Send announcements to selected groups</p>
      </div>

      <form @submit.prevent="saveBroadcast" class="bg-white rounded-xl border border-neutral-200 p-6 space-y-6">
        <!-- Title & Body -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Title *</label>
          <input
            v-model="form.title"
            type="text"
            required
            class="w-full px-4 py-2.5 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-2">Message Body *</label>
          <textarea
            v-model="form.body"
            rows="8"
            required
            class="w-full px-4 py-2.5 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
          ></textarea>
        </div>

        <!-- Channels -->
        <div class="border-t border-neutral-200 pt-6">
          <h3 class="text-lg font-semibold text-neutral-900 mb-4">Delivery Channels *</h3>
          <div class="space-y-3">
            <label class="flex items-center gap-3 cursor-pointer">
              <input
                v-model="form.channels"
                type="checkbox"
                value="email"
                class="h-5 w-5 rounded border-neutral-300 text-primary-600"
              />
              <span class="text-sm font-medium text-neutral-700">Email</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer">
              <input
                v-model="form.channels"
                type="checkbox"
                value="whatsapp"
                class="h-5 w-5 rounded border-neutral-300 text-primary-600"
              />
              <span class="text-sm font-medium text-neutral-700">WhatsApp</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer">
              <input
                v-model="form.channels"
                type="checkbox"
                value="in_app"
                class="h-5 w-5 rounded border-neutral-300 text-primary-600"
              />
              <span class="text-sm font-medium text-neutral-700">In-App Notification</span>
            </label>
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

            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-2">Enrolled in Course</label>
              <select
                v-model="form.audience_filters.course_id"
                class="w-full px-4 py-2.5 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              >
                <option :value="null">All courses</option>
                <option v-for="course in courses" :key="course.id" :value="course.id">
                  {{ course.title }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <!-- Audience Preview -->
        <div v-if="previewData" class="border-t border-neutral-200 pt-6">
          <h3 class="text-lg font-semibold text-neutral-900 mb-4">Audience Preview</h3>
          <div class="bg-neutral-50 rounded-lg p-4 space-y-2">
            <div class="text-sm text-neutral-600">
              Total matching users: <span class="font-semibold text-neutral-900">{{ previewData.total_count }}</span>
            </div>
            <div class="text-sm text-neutral-600 space-y-1">
              <div v-for="(count, channel) in previewData.channel_counts" :key="channel">
                {{ channel }}: <span class="font-semibold text-neutral-900">{{ count }}</span> users
              </div>
            </div>
          </div>
          <button
            type="button"
            @click="previewAudience"
            class="mt-4 px-4 py-2 text-sm text-primary-600 bg-primary-50 rounded-lg hover:bg-primary-100 transition-colors"
          >
            Refresh Preview
          </button>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-6 border-t border-neutral-200">
          <Link
            href="/admin/broadcasts"
            class="px-4 py-2 text-neutral-700 bg-neutral-100 rounded-lg hover:bg-neutral-200 transition-colors"
          >
            Cancel
          </Link>
          <button
            type="button"
            @click="previewAudience"
            class="px-4 py-2 text-neutral-700 bg-neutral-100 rounded-lg hover:bg-neutral-200 transition-colors"
          >
            Preview Audience
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="px-4 py-2 bg-primary-900 text-white rounded-lg hover:bg-primary-800 transition-colors disabled:opacity-50"
          >
            {{ form.processing ? 'Saving...' : 'Save Draft' }}
          </button>
        </div>
      </form>
    </div>
  </AppShell>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppShell from '@/Layouts/AppShell.vue'
import axios from 'axios'

const props = defineProps({
  courses: Array,
})

const previewData = ref(null)

const form = useForm({
  title: '',
  body: '',
  channels: [],
  audience_filters: {
    min_level: null,
    requires_bayah: false,
    gender: null,
    course_id: null,
  },
})

const previewAudience = async () => {
  try {
    const response = await axios.post('/admin/broadcasts/preview', {
      channels: form.channels,
      audience_filters: form.audience_filters,
    })
    previewData.value = response.data
  } catch (error) {
    console.error('Preview failed:', error)
  }
}

const saveBroadcast = () => {
  form.post('/admin/broadcasts', {
    preserveScroll: true,
    onSuccess: () => {
      // Redirect handled by Inertia
    },
  })
}
</script>

<template>
  <AppShell>
    <Head title="Admin - Notification Settings" />

    <div class="max-w-4xl">
      <!-- Header -->
      <div class="mb-8">
        <Link href="/admin" class="inline-flex items-center gap-2 text-neutral-600 hover:text-primary-600 mb-4">
          <ArrowLeft class="w-4 h-4" />
          Back to Admin
        </Link>
        <h1 class="text-2xl font-serif font-bold text-primary-900">Notification Settings</h1>
        <p class="text-neutral-600 mt-1">Configure reminder notifications for students</p>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="bg-white rounded-xl border border-neutral-200 p-6 space-y-6">
        <!-- Global Toggle -->
        <div class="flex items-center justify-between p-4 bg-neutral-50 rounded-lg border border-neutral-200">
          <div>
            <h3 class="font-semibold text-neutral-900">Enable Notifications</h3>
            <p class="text-sm text-neutral-600 mt-1">Master switch for all notification types</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" v-model="form.enabled" class="sr-only peer" />
            <div class="w-11 h-6 bg-neutral-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
          </label>
        </div>

        <!-- Channels -->
        <div class="border-t border-neutral-200 pt-6">
          <h3 class="text-lg font-semibold text-neutral-900 mb-4">Notification Channels</h3>
          <div class="space-y-3">
            <label class="flex items-center gap-3 cursor-pointer">
              <input type="checkbox" v-model="form.channels.email" class="h-5 w-5 rounded border-neutral-300 text-primary-600" />
              <span class="text-sm font-medium text-neutral-700">Email</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer">
              <input type="checkbox" v-model="form.channels.whatsapp" class="h-5 w-5 rounded border-neutral-300 text-primary-600" />
              <span class="text-sm font-medium text-neutral-700">WhatsApp</span>
            </label>
          </div>
        </div>

        <!-- Drip Reminders -->
        <div class="border-t border-neutral-200 pt-6">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-lg font-semibold text-neutral-900">Drip Reminders</h3>
              <p class="text-sm text-neutral-600 mt-1">Notify students when their next lesson becomes available</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="form.drip.enabled" class="sr-only peer" />
              <div class="w-11 h-6 bg-neutral-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
            </label>
          </div>
          <div v-if="form.drip.enabled" class="ml-8 space-y-3">
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-2">Send Hour (0-23)</label>
              <input
                v-model.number="form.drip.send_hour"
                type="number"
                min="0"
                max="23"
                class="w-32 px-4 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              />
              <p class="mt-1 text-xs text-neutral-500">Hour of day to send reminders (local timezone)</p>
            </div>
          </div>
        </div>

        <!-- Task Reminders -->
        <div class="border-t border-neutral-200 pt-6">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-lg font-semibold text-neutral-900">Task Reminders</h3>
              <p class="text-sm text-neutral-600 mt-1">Remind students to check in for daily practice tasks</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="form.task.enabled" class="sr-only peer" />
              <div class="w-11 h-6 bg-neutral-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
            </label>
          </div>
          <div v-if="form.task.enabled" class="ml-8 space-y-3">
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-2">Send Hour (0-23)</label>
              <input
                v-model.number="form.task.send_hour"
                type="number"
                min="0"
                max="23"
                class="w-32 px-4 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              />
            </div>
          </div>
        </div>

        <!-- Stagnation Reminders -->
        <div class="border-t border-neutral-200 pt-6">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-lg font-semibold text-neutral-900">Stagnation Reminders</h3>
              <p class="text-sm text-neutral-600 mt-1">Remind inactive students to return to their learning</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="form.stagnation.enabled" class="sr-only peer" />
              <div class="w-11 h-6 bg-neutral-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
            </label>
          </div>
          <div v-if="form.stagnation.enabled" class="ml-8 space-y-3">
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-2">Inactive Days Threshold</label>
              <input
                v-model.number="form.stagnation.inactive_days"
                type="number"
                min="1"
                max="30"
                class="w-32 px-4 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              />
              <p class="mt-1 text-xs text-neutral-500">Send reminder if student inactive for this many days</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-2">Send Hour (0-23)</label>
              <input
                v-model.number="form.stagnation.send_hour"
                type="number"
                min="0"
                max="23"
                class="w-32 px-4 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
              />
            </div>
          </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center gap-4 pt-4 border-t border-neutral-100">
          <button
            type="submit"
            :disabled="form.processing"
            class="px-6 py-2.5 bg-primary-900 text-white rounded-lg font-medium hover:bg-primary-800 transition-colors disabled:opacity-50 flex items-center gap-2"
          >
            <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
            {{ form.processing ? 'Saving...' : 'Save Settings' }}
          </button>
        </div>
      </form>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Loader2 } from 'lucide-vue-next';

const props = defineProps({
  settings: Object,
});

const form = useForm({
  enabled: props.settings?.enabled ?? true,
  channels: {
    email: props.settings?.channels?.email ?? true,
    whatsapp: props.settings?.channels?.whatsapp ?? true,
  },
  drip: {
    enabled: props.settings?.drip?.enabled ?? true,
    send_hour: props.settings?.drip?.send_hour ?? 9,
  },
  task: {
    enabled: props.settings?.task?.enabled ?? true,
    send_hour: props.settings?.task?.send_hour ?? 19,
  },
  stagnation: {
    enabled: props.settings?.stagnation?.enabled ?? true,
    inactive_days: props.settings?.stagnation?.inactive_days ?? 3,
    send_hour: props.settings?.stagnation?.send_hour ?? 10,
  },
});

function submit() {
  form.patch(route('admin.notifications.settings.update'), {
    preserveScroll: true,
  });
}
</script>

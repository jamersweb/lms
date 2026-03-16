<template>
  <AppShell>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="font-serif text-2xl font-bold text-neutral-900">Trigger Queue</h1>
          <p class="text-sm text-neutral-600">Oldest pending triggers are shown first so the browser bot can process them in order.</p>
        </div>
        <Link
          :href="route('admin.triggers.next')"
          data-bot="bot-view-link"
          class="inline-flex items-center rounded-lg bg-primary-900 px-4 py-2 text-sm font-medium text-white hover:bg-primary-800"
        >
          Bot View
        </Link>
      </div>

      <div v-if="$page.props.flash?.success" class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash?.error" class="rounded-lg bg-red-50 p-4 text-sm text-red-800">
        {{ $page.props.flash.error }}
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
          <p class="text-xs uppercase tracking-wide text-amber-700">Pending</p>
          <p class="mt-1 text-2xl font-bold text-amber-900">{{ stats.pending }}</p>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
          <p class="text-xs uppercase tracking-wide text-blue-700">Processing</p>
          <p class="mt-1 text-2xl font-bold text-blue-900">{{ stats.processing }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
          <p class="text-xs uppercase tracking-wide text-emerald-700">Sent Today</p>
          <p class="mt-1 text-2xl font-bold text-emerald-900">{{ stats.sent_today }}</p>
        </div>
        <div class="rounded-xl border border-red-200 bg-red-50 p-4">
          <p class="text-xs uppercase tracking-wide text-red-700">Failed</p>
          <p class="mt-1 text-2xl font-bold text-red-900">{{ stats.failed }}</p>
        </div>
      </div>

      <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-neutral-50 text-left text-xs uppercase tracking-wide text-neutral-500">
              <tr>
                <th class="px-4 py-3">Time</th>
                <th class="px-4 py-3">Event</th>
                <th class="px-4 py-3">User</th>
                <th class="px-4 py-3">Phone</th>
                <th class="px-4 py-3">Message</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in events.data" :key="item.id" class="border-t border-neutral-100 align-top">
                <td class="px-4 py-3 whitespace-nowrap text-neutral-600">{{ item.created_at }}</td>
                <td class="px-4 py-3">
                  <div class="font-semibold text-neutral-900">{{ item.event_key }}</div>
                  <div class="text-xs text-neutral-500">{{ item.template_name }}</div>
                </td>
                <td class="px-4 py-3">
                  <div class="font-medium text-neutral-900">{{ item.user_name || 'Unknown' }}</div>
                  <div class="text-xs text-neutral-500">{{ item.user_email || '-' }}</div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-neutral-800">{{ item.phone }}</td>
                <td class="px-4 py-3 max-w-md">
                  <p class="line-clamp-3 text-neutral-700">{{ item.message }}</p>
                  <p v-if="item.error_message" class="mt-2 text-xs text-red-600">Error: {{ item.error_message }}</p>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span
                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                    :class="statusClass(item.status)"
                  >
                    {{ item.status }}
                  </span>
                  <div class="mt-1 text-xs text-neutral-500">Attempts: {{ item.attempts }}</div>
                  <div v-if="item.claimed_by" class="text-xs text-neutral-500">By: {{ item.claimed_by }}</div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <div class="flex flex-col gap-2">
                    <button
                      v-if="item.can_claim"
                      type="button"
                      :data-bot="`claim-${item.id}`"
                      class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-500"
                      @click="claimTrigger(item.id)"
                    >
                      Claim
                    </button>
                    <a
                      v-if="item.can_complete"
                      :href="item.whatsapp_url"
                      :data-bot="`open-whatsapp-${item.id}`"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="rounded-lg bg-neutral-900 px-3 py-1.5 text-center text-xs font-medium text-white hover:bg-neutral-800"
                    >
                      Open WhatsApp
                    </a>
                    <button
                      v-if="item.can_complete"
                      type="button"
                      :data-bot="`mark-sent-${item.id}`"
                      class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-500"
                      @click="markSent(item.id)"
                    >
                      Mark Sent
                    </button>
                    <button
                      v-if="item.can_complete"
                      type="button"
                      :data-bot="`mark-failed-${item.id}`"
                      class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-500"
                      @click="markFailed(item.id)"
                    >
                      Mark Failed
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="events.data.length === 0">
                <td colspan="7" class="px-4 py-8 text-center text-neutral-500">No trigger events yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Link, router } from '@inertiajs/vue3';

defineProps({
  events: {
    type: Object,
    required: true,
  },
  stats: {
    type: Object,
    required: true,
  },
});

function statusClass(status) {
  if (status === 'pending') return 'bg-amber-100 text-amber-800';
  if (status === 'processing') return 'bg-blue-100 text-blue-800';
  if (status === 'sent') return 'bg-emerald-100 text-emerald-800';
  if (status === 'failed') return 'bg-red-100 text-red-800';
  return 'bg-neutral-100 text-neutral-700';
}

function claimTrigger(id) {
  router.post(route('admin.triggers.claim', id), {}, { preserveScroll: true });
}

function markSent(id) {
  router.post(route('admin.triggers.mark-sent', id), {}, { preserveScroll: true });
}

function markFailed(id) {
  router.post(
    route('admin.triggers.mark-failed', id),
    { error_message: 'Marked failed from admin trigger queue.' },
    { preserveScroll: true }
  );
}
</script>

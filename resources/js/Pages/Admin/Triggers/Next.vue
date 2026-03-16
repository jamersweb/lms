<template>
  <AppShell>
    <div class="mx-auto max-w-4xl space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="font-serif text-2xl font-bold text-neutral-900">Next Trigger</h1>
          <p class="text-sm text-neutral-600">Minimal queue view for browser automation. Process one trigger at a time.</p>
        </div>
        <Link
          :href="route('admin.triggers.index')"
          data-bot="full-queue-link"
          class="inline-flex items-center rounded-lg border border-neutral-300 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50"
        >
          Full Queue
        </Link>
      </div>

      <div v-if="$page.props.flash?.success" class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash?.error" class="rounded-lg bg-red-50 p-4 text-sm text-red-800">
        {{ $page.props.flash.error }}
      </div>

      <div v-if="event" class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm">
        <div class="mb-6 flex items-start justify-between gap-4">
          <div>
            <p class="text-xs uppercase tracking-wide text-neutral-500">Trigger #{{ event.id }}</p>
            <h2 class="mt-1 text-xl font-bold text-neutral-900">{{ event.event_key }}</h2>
            <p class="text-sm text-neutral-500">{{ event.template_name }}</p>
          </div>
          <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="statusClass(event.status)">
            {{ event.status }}
          </span>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="rounded-xl bg-neutral-50 p-4">
            <p class="text-xs uppercase tracking-wide text-neutral-500">User</p>
            <p class="mt-1 text-lg font-semibold text-neutral-900">{{ event.user_name || 'Unknown' }}</p>
            <p class="text-sm text-neutral-600">{{ event.user_email || '-' }}</p>
          </div>
          <div class="rounded-xl bg-neutral-50 p-4">
            <p class="text-xs uppercase tracking-wide text-neutral-500">Phone</p>
            <p class="mt-1 text-lg font-semibold text-neutral-900">{{ event.phone }}</p>
            <p class="text-sm text-neutral-600">Created: {{ event.created_at }}</p>
          </div>
        </div>

        <div class="mt-4 rounded-xl bg-neutral-50 p-4">
          <p class="text-xs uppercase tracking-wide text-neutral-500">Message</p>
          <p class="mt-2 whitespace-pre-wrap text-sm leading-6 text-neutral-800">{{ event.message }}</p>
        </div>

        <div v-if="event.error_message" class="mt-4 rounded-xl bg-red-50 p-4">
          <p class="text-xs uppercase tracking-wide text-red-600">Last Error</p>
          <p class="mt-2 text-sm text-red-700">{{ event.error_message }}</p>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
          <button
            v-if="event.can_complete"
            type="button"
            data-bot="claim-open"
            class="rounded-lg bg-primary-900 px-4 py-2 text-sm font-medium text-white hover:bg-primary-800"
            @click="claimAndOpen(event)"
          >
            Claim and Open WhatsApp
          </button>
          <button
            v-if="event.can_claim"
            type="button"
            data-bot="claim-trigger"
            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500"
            @click="claimTrigger(event.id)"
          >
            Claim Trigger
          </button>
          <a
            v-if="event.can_complete"
            :href="event.whatsapp_url"
            data-bot="open-whatsapp"
            target="_blank"
            rel="noopener noreferrer"
            class="rounded-lg bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800"
          >
            Open in WhatsApp Web
          </a>
          <button
            v-if="event.can_complete"
            type="button"
            data-bot="mark-sent"
            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500"
            @click="markSent(event.id)"
          >
            Mark Sent
          </button>
          <button
            v-if="event.can_complete"
            type="button"
            data-bot="mark-failed"
            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500"
            @click="markFailed(event.id)"
          >
            Mark Failed
          </button>
          <button
            type="button"
            data-bot="refresh-trigger"
            class="rounded-lg border border-neutral-300 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50"
            @click="refreshPage"
          >
            Refresh
          </button>
        </div>
      </div>

      <div v-else class="rounded-2xl border border-neutral-200 bg-white p-10 text-center">
        <h2 class="text-xl font-semibold text-neutral-900">No pending triggers</h2>
        <p class="mt-2 text-sm text-neutral-600">The queue is empty. Refresh this page after new LMS events are recorded.</p>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Link, router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';

defineProps({
  event: {
    type: Object,
    default: null,
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

function claimAndOpen(event) {
  window.open(event.whatsapp_url, '_blank', 'noopener,noreferrer');

  if (event.can_claim) {
    router.post(route('admin.triggers.claim', event.id), {}, { preserveScroll: true });
  }
}

function markSent(id) {
  router.post(route('admin.triggers.mark-sent', id), {}, {
    preserveScroll: true,
    onSuccess: () => router.visit(route('admin.triggers.next')),
  });
}

function markFailed(id) {
  router.post(
    route('admin.triggers.mark-failed', id),
    { error_message: 'Marked failed from bot view.' },
    {
      preserveScroll: true,
      onSuccess: () => router.visit(route('admin.triggers.next')),
    }
  );
}

function refreshPage() {
  router.reload({ preserveScroll: true });
}

let refreshTimer = null;

onMounted(() => {
  refreshTimer = window.setInterval(() => {
    router.reload({ preserveScroll: true, preserveState: true });
  }, 10000);
});

onBeforeUnmount(() => {
  if (refreshTimer) {
    window.clearInterval(refreshTimer);
  }
});
</script>

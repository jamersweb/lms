<template>
  <AppShell>
    <div class="max-w-5xl mx-auto">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="font-serif text-2xl font-bold text-neutral-900">Ask Portal (All Threads)</h1>
      </div>

      <div v-if="threads.data.length === 0" class="bg-white border border-dashed border-neutral-200 rounded-xl p-8 text-center text-neutral-500">
        No questions have been submitted yet.
      </div>

      <div v-else class="bg-white border border-neutral-200 rounded-xl divide-y divide-neutral-100">
        <div
          v-for="thread in threads.data"
          :key="thread.id"
          class="px-4 py-3 flex items-center justify-between gap-4 hover:bg-neutral-50"
        >
          <div class="flex-1 min-w-0">
            <div class="text-sm font-medium text-neutral-900 truncate">
              <Link :href="route('admin.ask.show', thread.id)">
                {{ thread.subject }}
              </Link>
            </div>
            <div class="text-xs text-neutral-500">
              {{ thread.user.name }} • {{ thread.created_at }}
            </div>
          </div>
          <span
            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium"
            :class="thread.status === 'open' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-neutral-100 text-neutral-600 border border-neutral-200'"
          >
            {{ thread.status === 'open' ? 'Open' : 'Closed' }}
          </span>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
  threads: Object,
});
</script>


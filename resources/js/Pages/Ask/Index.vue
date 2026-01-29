<template>
  <AppShell>
    <div class="max-w-3xl mx-auto">
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="font-serif text-2xl font-bold text-neutral-900">Ask Portal</h1>
          <p class="text-sm text-neutral-600">
            Ask private questions to your mentors.
          </p>
        </div>
        <Link :href="route('ask.create')" class="btn-primary">
          New Question
        </Link>
      </div>

      <div v-if="threads.data.length === 0" class="bg-white border border-dashed border-neutral-200 rounded-xl p-8 text-center text-neutral-500">
        You have not asked any questions yet.
      </div>

      <div v-else class="space-y-3">
        <Link
          v-for="thread in threads.data"
          :key="thread.id"
          :href="route('ask.show', thread.id)"
          class="block bg-white border border-neutral-200 rounded-xl px-4 py-3 hover:border-primary-300 hover:shadow-sm transition"
        >
          <div class="flex items-center justify-between gap-3">
            <div>
              <div class="text-sm font-medium text-neutral-900">
                {{ thread.subject }}
              </div>
              <div class="text-xs text-neutral-500">
                Opened {{ thread.created_at }}
              </div>
            </div>
            <span
              class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium"
              :class="thread.status === 'open' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-neutral-100 text-neutral-600 border border-neutral-200'"
            >
              {{ thread.status === 'open' ? 'Open' : 'Closed' }}
            </span>
          </div>
        </Link>
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


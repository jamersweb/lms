<template>
  <AppShell>
    <div class="max-w-5xl mx-auto">
      <div class="mb-6 flex items-center justify-between">
        <div>
          <button
            type="button"
            class="text-xs text-neutral-500 hover:text-neutral-800 mb-1"
            @click="$inertia.visit(route('admin.analytics.index'))"
          >
            ← Back to analytics
          </button>
          <h1 class="font-serif text-2xl font-bold text-neutral-900">
            Activity for {{ user.name }}
          </h1>
          <p class="text-xs text-neutral-500">
            {{ user.email }}
          </p>
        </div>
      </div>

      <div v-if="sessions.length === 0" class="bg-white border border-dashed border-neutral-200 rounded-xl p-8 text-center text-neutral-500">
        No watch sessions recorded for this user yet.
      </div>

      <div v-else class="bg-white border border-neutral-200 rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
          <thead class="bg-neutral-50 text-xs uppercase text-neutral-500">
            <tr>
              <th class="px-3 py-2 text-left">When</th>
              <th class="px-3 py-2 text-left">Course</th>
              <th class="px-3 py-2 text-left">Lesson</th>
              <th class="px-3 py-2 text-left">Watch time</th>
              <th class="px-3 py-2 text-left">Seeks</th>
              <th class="px-3 py-2 text-left">Max rate</th>
              <th class="px-3 py-2 text-left">Valid</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in sessions" :key="s.id" class="border-t border-neutral-100">
              <td class="px-3 py-2">
                <div>{{ s.started_at || 'Unknown' }}</div>
                <div v-if="s.ended_at" class="text-xs text-neutral-500">→ {{ s.ended_at }}</div>
              </td>
              <td class="px-3 py-2">{{ s.course_title || '—' }}</td>
              <td class="px-3 py-2">{{ s.lesson_title }}</td>
              <td class="px-3 py-2">
                {{ Math.round(s.watch_time_seconds) }}s
              </td>
              <td class="px-3 py-2">
                {{ s.seek_events_count }}
              </td>
              <td class="px-3 py-2">
                {{ s.max_playback_rate?.toFixed(2) ?? '—' }}x
              </td>
              <td class="px-3 py-2">
                <span
                  class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                  :class="s.is_valid ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-rose-50 text-rose-700 border border-rose-100'"
                >
                  {{ s.is_valid ? 'Valid' : 'Invalid' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';

defineProps({
  user: Object,
  sessions: Array,
});
</script>


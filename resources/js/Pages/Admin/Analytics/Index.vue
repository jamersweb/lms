<template>
  <AppShell>
    <div class="max-w-6xl mx-auto">
      <div class="mb-6">
        <h1 class="font-serif text-2xl font-bold text-neutral-900">Analytics</h1>
        <p class="text-sm text-neutral-600">
          Monitor engagement, stagnation, and potential speeding behaviour.
        </p>
      </div>

      <div class="border-b border-neutral-200 mb-4">
        <nav class="-mb-px flex gap-6 text-sm">
          <button
            type="button"
            class="pb-2 border-b-2"
            :class="tab === 'active' ? 'border-primary-600 text-primary-700 font-medium' : 'border-transparent text-neutral-500 hover:text-neutral-700'"
            @click="tab = 'active'"
          >
            Active ({{ activeUsers.length }})
          </button>
          <button
            type="button"
            class="pb-2 border-b-2"
            :class="tab === 'stalled' ? 'border-primary-600 text-primary-700 font-medium' : 'border-transparent text-neutral-500 hover:text-neutral-700'"
            @click="tab = 'stalled'"
          >
            Stalled ({{ stalledUsers.length }})
          </button>
          <button
            type="button"
            class="pb-2 border-b-2"
            :class="tab === 'speeding' ? 'border-primary-600 text-primary-700 font-medium' : 'border-transparent text-neutral-500 hover:text-neutral-700'"
            @click="tab = 'speeding'"
          >
            Speeding ({{ speeding.length }})
          </button>
          <button
            type="button"
            class="pb-2 border-b-2"
            :class="tab === 'task-mastery' ? 'border-primary-600 text-primary-700 font-medium' : 'border-transparent text-neutral-500 hover:text-neutral-700'"
            @click="tab = 'task-mastery'"
          >
            Task Mastery ({{ taskMastery.length }})
          </button>
        </nav>
      </div>

      <div v-if="tab === 'active'">
        <h2 class="text-sm font-semibold text-neutral-900 mb-2">Active in last 10 minutes</h2>
        <div v-if="activeUsers.length === 0" class="text-sm text-neutral-500">
          No active users right now.
        </div>
        <table v-else class="min-w-full text-sm border border-neutral-200 rounded-lg overflow-hidden bg-white">
          <thead class="bg-neutral-50 text-xs uppercase text-neutral-500">
            <tr>
              <th class="px-3 py-2 text-left">User</th>
              <th class="px-3 py-2 text-left">Email</th>
              <th class="px-3 py-2 text-left">Active lessons</th>
              <th class="px-3 py-2 text-left">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in activeUsers" :key="user.id" class="border-t border-neutral-100">
              <td class="px-3 py-2">{{ user.name }}</td>
              <td class="px-3 py-2 text-neutral-500">{{ user.email }}</td>
              <td class="px-3 py-2">{{ user.active_lessons_count }}</td>
              <td class="px-3 py-2">
                <Link :href="route('admin.users.activity', user.id)" class="text-primary-600 hover:text-primary-800">
                  Activity log
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else-if="tab === 'stalled'">
        <h2 class="text-sm font-semibold text-neutral-900 mb-2">
          Stalled &ndash; no activity in {{ stalledDays }}+ days
        </h2>
        <div v-if="stalledUsers.length === 0" class="text-sm text-neutral-500">
          No stalled users detected for this window.
        </div>
        <table v-else class="min-w-full text-sm border border-neutral-200 rounded-lg overflow-hidden bg-white">
          <thead class="bg-neutral-50 text-xs uppercase text-neutral-500">
            <tr>
              <th class="px-3 py-2 text-left">User</th>
              <th class="px-3 py-2 text-left">Email</th>
              <th class="px-3 py-2 text-left">Last activity</th>
              <th class="px-3 py-2 text-left">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in stalledUsers" :key="user.id" class="border-t border-neutral-100">
              <td class="px-3 py-2">{{ user.name }}</td>
              <td class="px-3 py-2 text-neutral-500">{{ user.email }}</td>
              <td class="px-3 py-2">{{ user.last_activity || 'Unknown' }}</td>
              <td class="px-3 py-2">
                <Link :href="route('admin.users.activity', user.id)" class="text-primary-600 hover:text-primary-800">
                  Activity log
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else-if="tab === 'speeding'">
        <h2 class="text-sm font-semibold text-neutral-900 mb-2">Speeding / anomaly flags</h2>
        <div v-if="speeding.length === 0" class="text-sm text-neutral-500">
          No speeding behaviour detected.
        </div>
        <table v-else class="min-w-full text-sm border border-neutral-200 rounded-lg overflow-hidden bg-white">
          <thead class="bg-neutral-50 text-xs uppercase text-neutral-500">
            <tr>
              <th class="px-3 py-2 text-left">User</th>
              <th class="px-3 py-2 text-left">Lesson</th>
              <th class="px-3 py-2 text-left">Course</th>
              <th class="px-3 py-2 text-left">Flags</th>
              <th class="px-3 py-2 text-left">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in speeding" :key="`${row.user.id}-${row.lesson.id}`" class="border-t border-neutral-100">
              <td class="px-3 py-2">
                {{ row.user.name }}
                <div class="text-xs text-neutral-500">{{ row.user.email }}</div>
              </td>
              <td class="px-3 py-2">{{ row.lesson.title }}</td>
              <td class="px-3 py-2">{{ row.lesson.course_title || '—' }}</td>
              <td class="px-3 py-2">
                <div class="space-y-1 text-xs">
                  <div v-if="row.flags.seek_detected" class="text-amber-700">Seek detected</div>
                  <div v-if="row.flags.max_playback_rate_seen > 1.5" class="text-amber-700">
                    Max rate: {{ row.flags.max_playback_rate_seen.toFixed(2) }}x
                  </div>
                  <div v-if="row.flags.verified_completion && ratio(row) < 0.6" class="text-rose-700">
                    Verified with low watch time ({{ Math.round(ratio(row) * 100) }}%)
                  </div>
                </div>
              </td>
              <td class="px-3 py-2">
                <Link :href="route('admin.users.activity', row.user.id)" class="text-primary-600 hover:text-primary-800">
                  Activity log
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else-if="tab === 'task-mastery'">
        <h2 class="text-sm font-semibold text-neutral-900 mb-2">
          Task Mastery &ndash; Which Sunnah/tasks are students struggling with most
        </h2>
        <div v-if="taskMastery.length === 0" class="text-sm text-neutral-500">
          No task data available yet.
        </div>
        <table v-else class="min-w-full text-sm border border-neutral-200 rounded-lg overflow-hidden bg-white">
          <thead class="bg-neutral-50 text-xs uppercase text-neutral-500">
            <tr>
              <th class="px-3 py-2 text-left">Lesson/Task</th>
              <th class="px-3 py-2 text-left">Course</th>
              <th class="px-3 py-2 text-left">Total Attempts</th>
              <th class="px-3 py-2 text-left">Approved</th>
              <th class="px-3 py-2 text-left">Needs Clarification</th>
              <th class="px-3 py-2 text-left">Pending</th>
              <th class="px-3 py-2 text-left">Struggle Rate</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="task in taskMastery" :key="task.lesson_id" class="border-t border-neutral-100">
              <td class="px-3 py-2 font-medium">{{ task.lesson_title }}</td>
              <td class="px-3 py-2 text-neutral-500">{{ task.course_title || '—' }}</td>
              <td class="px-3 py-2">{{ task.total_attempts }}</td>
              <td class="px-3 py-2 text-green-700">{{ task.approved }}</td>
              <td class="px-3 py-2 text-amber-700">{{ task.needs_clarification }}</td>
              <td class="px-3 py-2 text-blue-700">{{ task.pending }}</td>
              <td class="px-3 py-2">
                <span :class="task.struggle_rate > 50 ? 'text-red-700 font-bold' : task.struggle_rate > 30 ? 'text-amber-700' : 'text-green-700'">
                  {{ task.struggle_rate }}%
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
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  activeUsers: Array,
  stalledUsers: Array,
  speeding: Array,
  stalledDays: Number,
  taskMastery: Array,
});

const tab = ref('active');

const ratio = (row) => {
  const w = row.flags.time_watched_seconds || 0;
  const d = row.flags.video_duration_seconds || 0;
  if (!d) return 0;
  return w / d;
};
</script>


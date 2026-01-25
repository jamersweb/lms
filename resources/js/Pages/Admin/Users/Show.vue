<template>
  <AppShell>
    <div class="space-y-6">
      <!-- Back Button -->
      <Link href="/admin/users" class="inline-flex items-center gap-2 text-neutral-600 hover:text-neutral-900">
        <ArrowLeft class="w-4 h-4" />
        Back to Users
      </Link>

      <!-- User Header -->
      <div class="bg-white rounded-xl border border-neutral-200 p-6">
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-4">
            <div class="h-16 w-16 rounded-full bg-primary-100 flex items-center justify-center text-2xl font-bold text-primary-900">
              {{ getInitials(user.name) }}
            </div>
            <div>
              <h1 class="font-serif text-2xl font-bold text-neutral-900 flex items-center gap-3">
                {{ user.name }}
                <span v-if="user.is_admin" class="text-sm bg-primary-100 text-primary-700 px-3 py-1 rounded-full font-medium">
                  Admin
                </span>
              </h1>
              <p class="text-neutral-600">{{ user.email }}</p>
              <p class="text-sm text-neutral-400 mt-1">
                Joined {{ user.created_at }}
                <span v-if="user.email_verified_at"> • Email verified {{ user.email_verified_at }}</span>
              </p>
            </div>
          </div>
          <div class="flex gap-2">
            <Link :href="`/admin/users/${user.id}/edit`" class="px-4 py-2 bg-white border border-neutral-200 text-neutral-700 rounded-lg text-sm font-medium hover:bg-neutral-50 transition-colors">
              Edit User
            </Link>
            <Link :href="`/admin/habits/create?user_id=${user.id}`" class="px-4 py-2 bg-primary-900 text-white rounded-lg text-sm font-medium hover:bg-primary-800 transition-colors">
              Create Habit
            </Link>
          </div>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-neutral-200 p-5">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-lg bg-primary-50 flex items-center justify-center">
              <BookOpen class="w-5 h-5 text-primary-600" />
            </div>
            <div>
              <div class="text-xl font-bold text-neutral-900">{{ stats.totalCoursesEnrolled }}</div>
              <div class="text-sm text-neutral-500">Courses Enrolled</div>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-neutral-200 p-5">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-lg bg-emerald-50 flex items-center justify-center">
              <CheckCircle class="w-5 h-5 text-emerald-600" />
            </div>
            <div>
              <div class="text-xl font-bold text-neutral-900">{{ stats.totalLessonsCompleted }}</div>
              <div class="text-sm text-neutral-500">Lessons Completed</div>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-neutral-200 p-5">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-lg bg-blue-50 flex items-center justify-center">
              <Target class="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <div class="text-xl font-bold text-neutral-900">{{ stats.totalHabitsCreated }}</div>
              <div class="text-sm text-neutral-500">Active Habits</div>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-neutral-200 p-5">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-lg bg-secondary-50 flex items-center justify-center">
              <Calendar class="w-5 h-5 text-secondary-600" />
            </div>
            <div>
              <div class="text-xl font-bold text-neutral-900">{{ stats.totalHabitLogs }}</div>
              <div class="text-sm text-neutral-500">Habit Logs</div>
            </div>
          </div>
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-6">
        <!-- Enrollments -->
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
          <div class="px-6 py-4 border-b border-neutral-100">
            <h2 class="font-semibold text-neutral-900">Course Enrollments</h2>
          </div>
          <div class="divide-y divide-neutral-100">
            <div v-for="enrollment in enrollments" :key="enrollment.id" class="px-6 py-4">
              <div class="flex items-center justify-between mb-2">
                <Link :href="`/admin/courses/${enrollment.course_id}/edit`" class="font-medium text-neutral-900 hover:text-primary-600">
                  {{ enrollment.course_title }}
                </Link>
                <span class="text-sm text-neutral-500">{{ enrollment.enrolled_at }}</span>
              </div>
              <div class="flex items-center gap-3">
                <div class="flex-1 bg-neutral-100 rounded-full h-2">
                  <div class="bg-primary-600 h-2 rounded-full transition-all" :style="{ width: enrollment.progress + '%' }"></div>
                </div>
                <span class="text-sm font-medium text-neutral-600">{{ enrollment.progress }}%</span>
              </div>
              <div class="text-xs text-neutral-400 mt-1">
                {{ enrollment.completed_lessons }} of {{ enrollment.total_lessons }} lessons completed
              </div>
            </div>
            <div v-if="!enrollments.length" class="px-6 py-8 text-center text-neutral-400">
              No course enrollments yet
            </div>
          </div>
        </div>

        <!-- Habits -->
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
          <div class="px-6 py-4 border-b border-neutral-100 flex items-center justify-between">
            <h2 class="font-semibold text-neutral-900">User Habits</h2>
            <Link :href="`/admin/habits/create?user_id=${user.id}`" class="text-sm text-primary-600 hover:text-primary-700">
              + Add Habit
            </Link>
          </div>
          <div class="divide-y divide-neutral-100">
            <div v-for="habit in habits" :key="habit.id" class="px-6 py-4">
              <div class="flex items-center justify-between mb-2">
                <div class="font-medium text-neutral-900">{{ habit.name }}</div>
                <Link :href="`/admin/habits/${habit.id}/edit`" class="text-neutral-400 hover:text-primary-600">
                  <Pencil class="w-4 h-4" />
                </Link>
              </div>
              <div class="flex items-center gap-4 text-sm">
                <span :class="[
                  'px-2 py-0.5 rounded text-xs font-medium',
                  habit.frequency === 'daily' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'
                ]">
                  {{ habit.frequency }}
                </span>
                <span class="text-neutral-500">
                  <Flame class="w-4 h-4 inline text-orange-500" /> {{ habit.current_streak }} day streak
                </span>
                <span class="text-neutral-400">
                  Best: {{ habit.best_streak }} days
                </span>
              </div>
            </div>
            <div v-if="!habits.length" class="px-6 py-8 text-center text-neutral-400">
              No habits created yet
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, BookOpen, CheckCircle, Target, Calendar, Pencil, Flame } from 'lucide-vue-next';

defineProps({
  user: Object,
  stats: Object,
  enrollments: Array,
  habits: Array,
});

const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};
</script>

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

      <!-- User Segmentation -->
      <div class="bg-white rounded-xl border border-neutral-200 p-6">
        <h2 class="font-semibold text-neutral-900 mb-4">User Segmentation</h2>
        <form @submit.prevent="updateSegmentation" class="space-y-4">
          <div class="grid md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-2">Bay'ah Status</label>
              <label class="inline-flex items-center gap-2">
                <input
                  v-model="segmentationForm.has_bayah"
                  type="checkbox"
                  class="h-4 w-4 text-primary-600 border-neutral-300 rounded focus:ring-primary-500"
                />
                <span class="text-sm text-neutral-700">User has bay'ah</span>
              </label>
              <p v-if="segmentationForm.errors.has_bayah" class="mt-1 text-sm text-red-600">{{ segmentationForm.errors.has_bayah }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-2">Level</label>
              <select
                v-model="segmentationForm.level"
                class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              >
                <option value="beginner">Beginner</option>
                <option value="intermediate">Intermediate</option>
                <option value="expert">Expert</option>
              </select>
              <p v-if="segmentationForm.errors.level" class="mt-1 text-sm text-red-600">{{ segmentationForm.errors.level }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-2">Gender (Optional)</label>
              <select
                v-model="segmentationForm.gender"
                class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              >
                <option :value="null">Not specified</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
              <p v-if="segmentationForm.errors.gender" class="mt-1 text-sm text-red-600">{{ segmentationForm.errors.gender }}</p>
            </div>
          </div>

          <div class="pt-4 border-t border-neutral-100 flex items-center gap-4">
            <button
              type="submit"
              :disabled="segmentationForm.processing"
              class="px-4 py-2 bg-primary-900 text-white rounded-lg text-sm font-medium hover:bg-primary-800 transition-colors disabled:opacity-50"
            >
              {{ segmentationForm.processing ? 'Saving...' : 'Update Segmentation' }}
            </button>
            <p v-if="segmentationForm.recentlySuccessful" class="text-sm text-green-600">
              Segmentation updated successfully.
            </p>
          </div>
        </form>

        <!-- Display current segmentation info -->
        <div class="mt-6 pt-6 border-t border-neutral-100">
          <div class="grid md:grid-cols-3 gap-4 text-sm">
            <div>
              <span class="text-neutral-500">Gender:</span>
              <span class="ml-2 font-medium text-neutral-900">{{ user.gender || 'Not set' }}</span>
            </div>
            <div>
              <span class="text-neutral-500">Bay'ah:</span>
              <span class="ml-2 font-medium text-neutral-900">{{ user.has_bayah ? 'Yes' : 'No' }}</span>
            </div>
            <div>
              <span class="text-neutral-500">Level:</span>
              <span class="ml-2 font-medium text-neutral-900 capitalize">{{ user.level || 'Not set' }}</span>
            </div>
            <div v-if="user.whatsapp_number">
              <span class="text-neutral-500">WhatsApp:</span>
              <span class="ml-2 font-medium text-neutral-900">{{ user.whatsapp_number }}</span>
              <span v-if="user.whatsapp_opt_in" class="ml-2 text-xs text-green-600">(Opted in)</span>
            </div>
            <div v-if="user.last_active_at">
              <span class="text-neutral-500">Last Active:</span>
              <span class="ml-2 font-medium text-neutral-900">{{ user.last_active_at }}</span>
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
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, BookOpen, CheckCircle, Target, Calendar, Pencil, Flame } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps({
  user: Object,
  stats: Object,
  enrollments: Array,
  habits: Array,
});

const segmentationForm = useForm({
  has_bayah: props.user.has_bayah ?? false,
  level: props.user.level || 'beginner',
  gender: props.user.gender || null,
});

const updateSegmentation = () => {
  segmentationForm.patch(route('admin.users.segmentation.update', props.user.id), {
    preserveScroll: true,
  });
};

const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};
</script>

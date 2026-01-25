<template>
  <AppShell>
    <div class="space-y-8">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="font-serif text-3xl font-bold text-neutral-900">Admin Dashboard</h1>
          <p class="text-neutral-600 mt-1">Overview of your learning platform</p>
        </div>
        <div class="flex gap-3">
          <Link href="/admin/courses" class="px-4 py-2 bg-primary-900 text-white rounded-lg text-sm font-medium hover:bg-primary-800 transition-colors">
            Manage Courses
          </Link>
          <Link href="/admin/users" class="px-4 py-2 bg-white border border-neutral-200 text-neutral-700 rounded-lg text-sm font-medium hover:bg-neutral-50 transition-colors">
            Manage Users
          </Link>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-neutral-200 p-6">
          <div class="flex items-center gap-3">
            <div class="h-12 w-12 rounded-xl bg-primary-50 flex items-center justify-center">
              <Users class="w-6 h-6 text-primary-900" />
            </div>
            <div>
              <div class="text-2xl font-bold text-neutral-900">{{ stats.totalUsers }}</div>
              <div class="text-sm text-neutral-500">Total Users</div>
            </div>
          </div>
          <div class="mt-3 text-xs text-emerald-600 font-medium">
            +{{ stats.newUsersThisMonth }} this month
          </div>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-6">
          <div class="flex items-center gap-3">
            <div class="h-12 w-12 rounded-xl bg-secondary-50 flex items-center justify-center">
              <BookOpen class="w-6 h-6 text-secondary-700" />
            </div>
            <div>
              <div class="text-2xl font-bold text-neutral-900">{{ stats.totalCourses }}</div>
              <div class="text-sm text-neutral-500">Courses</div>
            </div>
          </div>
          <div class="mt-3 text-xs text-neutral-500">
            {{ stats.totalLessons }} lessons total
          </div>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-6">
          <div class="flex items-center gap-3">
            <div class="h-12 w-12 rounded-xl bg-emerald-50 flex items-center justify-center">
              <GraduationCap class="w-6 h-6 text-emerald-600" />
            </div>
            <div>
              <div class="text-2xl font-bold text-neutral-900">{{ stats.totalEnrollments }}</div>
              <div class="text-sm text-neutral-500">Enrollments</div>
            </div>
          </div>
          <div class="mt-3 text-xs text-emerald-600 font-medium">
            +{{ stats.newEnrollmentsThisMonth }} this month
          </div>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-6">
          <div class="flex items-center gap-3">
            <div class="h-12 w-12 rounded-xl bg-blue-50 flex items-center justify-center">
              <Target class="w-6 h-6 text-blue-600" />
            </div>
            <div>
              <div class="text-2xl font-bold text-neutral-900">{{ stats.totalHabits }}</div>
              <div class="text-sm text-neutral-500">Active Habits</div>
            </div>
          </div>
          <div class="mt-3 text-xs text-neutral-500">
            {{ stats.totalDiscussions }} discussions
          </div>
        </div>
      </div>

      <!-- Content Grid -->
      <div class="grid md:grid-cols-2 gap-6">
        <!-- Popular Courses -->
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
          <div class="px-6 py-4 border-b border-neutral-100 flex items-center justify-between">
            <h2 class="font-semibold text-neutral-900">Popular Courses</h2>
            <Link href="/admin/courses" class="text-sm text-primary-600 hover:text-primary-700">View All</Link>
          </div>
          <div class="divide-y divide-neutral-100">
            <div v-for="course in popularCourses" :key="course.id" class="px-6 py-4 flex items-center justify-between">
              <div>
                <div class="font-medium text-neutral-900">{{ course.title }}</div>
              </div>
              <div class="text-sm text-neutral-500">
                {{ course.enrollments_count }} enrolled
              </div>
            </div>
            <div v-if="!popularCourses.length" class="px-6 py-8 text-center text-neutral-400">
              No courses yet
            </div>
          </div>
        </div>

        <!-- Recent Users -->
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
          <div class="px-6 py-4 border-b border-neutral-100 flex items-center justify-between">
            <h2 class="font-semibold text-neutral-900">Recent Users</h2>
            <Link href="/admin/users" class="text-sm text-primary-600 hover:text-primary-700">View All</Link>
          </div>
          <div class="divide-y divide-neutral-100">
            <div v-for="user in recentUsers" :key="user.id" class="px-6 py-4 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-full bg-primary-100 flex items-center justify-center text-sm font-bold text-primary-900">
                  {{ getInitials(user.name) }}
                </div>
                <div>
                  <div class="font-medium text-neutral-900 flex items-center gap-2">
                    {{ user.name }}
                    <span v-if="user.is_admin" class="text-xs bg-primary-100 text-primary-700 px-2 py-0.5 rounded-full">Admin</span>
                  </div>
                  <div class="text-sm text-neutral-500">{{ user.email }}</div>
                </div>
              </div>
              <div class="text-xs text-neutral-400">{{ user.created_at }}</div>
            </div>
            <div v-if="!recentUsers.length" class="px-6 py-8 text-center text-neutral-400">
              No users yet
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Enrollments -->
      <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-neutral-100">
          <h2 class="font-semibold text-neutral-900">Recent Enrollments</h2>
        </div>
        <div class="divide-y divide-neutral-100">
          <div v-for="enrollment in recentEnrollments" :key="enrollment.id" class="px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
              <div class="h-10 w-10 rounded-full bg-emerald-50 flex items-center justify-center">
                <GraduationCap class="w-5 h-5 text-emerald-600" />
              </div>
              <div>
                <div class="font-medium text-neutral-900">{{ enrollment.user_name }}</div>
                <div class="text-sm text-neutral-500">enrolled in <span class="text-primary-600">{{ enrollment.course_title }}</span></div>
              </div>
            </div>
            <div class="text-sm text-neutral-400">{{ enrollment.enrolled_at }}</div>
          </div>
          <div v-if="!recentEnrollments.length" class="px-6 py-8 text-center text-neutral-400">
            No enrollments yet
          </div>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <Link href="/admin/courses/create" class="bg-white rounded-xl border border-neutral-200 p-6 hover:border-primary-200 hover:bg-primary-50/50 transition-colors group">
          <Plus class="w-8 h-8 text-primary-600 mb-3" />
          <div class="font-medium text-neutral-900 group-hover:text-primary-900">Create Course</div>
          <div class="text-sm text-neutral-500">Add new course</div>
        </Link>
        <Link href="/admin/users/create" class="bg-white rounded-xl border border-neutral-200 p-6 hover:border-primary-200 hover:bg-primary-50/50 transition-colors group">
          <UserPlus class="w-8 h-8 text-primary-600 mb-3" />
          <div class="font-medium text-neutral-900 group-hover:text-primary-900">Add User</div>
          <div class="text-sm text-neutral-500">Create new user</div>
        </Link>
        <Link href="/admin/habits/create" class="bg-white rounded-xl border border-neutral-200 p-6 hover:border-primary-200 hover:bg-primary-50/50 transition-colors group">
          <Target class="w-8 h-8 text-primary-600 mb-3" />
          <div class="font-medium text-neutral-900 group-hover:text-primary-900">Create Habit</div>
          <div class="text-sm text-neutral-500">For any user</div>
        </Link>
        <Link href="/admin/moderation" class="bg-white rounded-xl border border-neutral-200 p-6 hover:border-primary-200 hover:bg-primary-50/50 transition-colors group">
          <Shield class="w-8 h-8 text-primary-600 mb-3" />
          <div class="font-medium text-neutral-900 group-hover:text-primary-900">Moderation</div>
          <div class="text-sm text-neutral-500">Manage discussions</div>
        </Link>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Link } from '@inertiajs/vue3';
import { Users, BookOpen, GraduationCap, Target, Plus, UserPlus, Shield } from 'lucide-vue-next';

defineProps({
  stats: Object,
  popularCourses: Array,
  recentUsers: Array,
  recentEnrollments: Array,
});

const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};
</script>

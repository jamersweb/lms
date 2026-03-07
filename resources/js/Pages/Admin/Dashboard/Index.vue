<template>
  <AppShell>
    <!-- Welcome Hero -->
    <div class="mb-8 relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900 p-8 text-white shadow-lg">
      <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PHBhdGggZD0iTTM2IDM0YzAtMi4yMDktMS43OTEtNC00LTRzLTQgMS43OTEtNCA0IDEuNzkxIDQgNCA0IDQtMS43OTEgNC00eiIvPjwvZz48L2c+PC9zdmc+')] opacity-20"></div>
      <div class="relative z-10 flex items-center justify-between">
        <div>
          <h1 class="font-serif text-4xl font-bold mb-2 drop-shadow-sm">Admin Dashboard</h1>
          <p class="text-white/90 text-lg">Overview of your learning platform</p>
        </div>
        <div class="flex gap-3">
          <Link href="/admin/courses" class="px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-lg text-sm font-medium hover:bg-white/30 transition-colors border border-white/30">
            Manage Courses
          </Link>
          <Link href="/admin/users" class="px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-lg text-sm font-medium hover:bg-white/30 transition-colors border border-white/30">
            Manage Users
          </Link>
        </div>
      </div>
    </div>

    <!-- Top KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <!-- Total Users -->
      <Card class="relative overflow-hidden bg-gradient-to-br from-primary-500 via-primary-600 to-primary-700 border-2 border-primary-400 hover:border-primary-300 hover:shadow-xl hover:scale-105 transition-all duration-300 group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
        <div class="relative z-10 flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-white/90 uppercase tracking-wide mb-1">Total Users</p>
            <p class="text-4xl font-serif font-bold text-white drop-shadow-sm">{{ stats.totalUsers }}</p>
            <p class="text-xs text-white/80 mt-1">+{{ stats.newUsersThisMonth }} this month</p>
          </div>
          <div class="h-14 w-14 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center group-hover:bg-white/30 transition-colors shadow-lg">
            <Users class="w-7 h-7 text-white" />
          </div>
        </div>
      </Card>

      <!-- Total Courses -->
      <Card class="relative overflow-hidden bg-gradient-to-br from-neutral-100 via-white to-primary-50 border-2 border-primary-200 hover:border-primary-400 hover:shadow-xl hover:scale-105 transition-all duration-300 group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-primary-100/50 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
        <div class="relative z-10 flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-primary-700 uppercase tracking-wide mb-1">Courses</p>
            <p class="text-4xl font-serif font-bold text-primary-900">{{ stats.totalCourses }}</p>
            <p class="text-xs text-primary-600 mt-1">{{ stats.totalLessons }} lessons total</p>
          </div>
          <div class="h-14 w-14 rounded-full bg-gradient-to-br from-primary-100 to-primary-200 flex items-center justify-center group-hover:from-primary-200 group-hover:to-primary-300 transition-all shadow-md">
            <BookOpen class="w-7 h-7 text-primary-700" />
          </div>
        </div>
      </Card>

      <!-- Total Enrollments -->
      <Card class="relative overflow-hidden bg-gradient-to-br from-secondary-400 via-secondary-500 to-secondary-600 border-2 border-secondary-300 hover:border-secondary-200 hover:shadow-xl hover:scale-105 transition-all duration-300 group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/20 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
        <div class="relative z-10 flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-white/90 uppercase tracking-wide mb-1">Enrollments</p>
            <p class="text-4xl font-serif font-bold text-white drop-shadow-sm">{{ stats.totalEnrollments }}</p>
            <p class="text-xs text-white/80 mt-1">+{{ stats.newEnrollmentsThisMonth }} this month</p>
          </div>
          <div class="h-14 w-14 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center group-hover:bg-white/30 transition-colors shadow-lg">
            <GraduationCap class="w-7 h-7 text-white" />
          </div>
        </div>
      </Card>

      <!-- Active Habits -->
      <Card class="relative overflow-hidden bg-gradient-to-br from-primary-700 via-primary-800 to-primary-900 text-white border-2 border-primary-500 hover:border-primary-400 hover:shadow-2xl hover:scale-105 transition-all duration-300 group">
        <div class="absolute inset-0 bg-gradient-to-br from-secondary-500/20 to-transparent"></div>
        <div class="absolute top-0 right-0 w-32 h-32 bg-secondary-400/30 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
        <div class="relative z-10 flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-white/90 uppercase tracking-wide mb-1">Active Habits</p>
            <p class="text-4xl font-serif font-bold text-white drop-shadow-sm">{{ stats.totalHabits }}</p>
            <p class="text-xs text-white/80 mt-1">{{ stats.totalDiscussions }} discussions</p>
          </div>
          <div class="h-14 w-14 rounded-full bg-gradient-to-br from-secondary-400 to-secondary-600 flex items-center justify-center group-hover:from-secondary-300 group-hover:to-secondary-500 transition-all shadow-lg">
            <Target class="w-7 h-7 text-white" />
          </div>
        </div>
      </Card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Left Column: Main Content -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Popular Courses -->
        <Card class="shadow-lg border-t-4 border-t-primary-600 bg-gradient-to-br from-white to-primary-50/20">
          <template #header>
            <div class="flex items-center justify-between pb-2 border-b border-primary-100">
              <h2 class="text-lg font-bold text-primary-900 flex items-center gap-2">
                <div class="h-1 w-1 rounded-full bg-primary-600 animate-pulse"></div>
                Popular Courses
              </h2>
              <Link href="/admin/courses" class="text-sm font-medium text-primary-600 hover:text-primary-800 transition-colors font-semibold">View All →</Link>
            </div>
          </template>
          <div class="divide-y divide-neutral-100">
            <div v-for="course in popularCourses" :key="course.id" class="px-4 py-3 flex items-center justify-between hover:bg-gradient-to-r hover:from-primary-50 hover:to-white transition-all duration-200 rounded-lg group">
              <div>
                <div class="font-medium text-neutral-900 group-hover:text-primary-900">{{ course.title }}</div>
              </div>
              <div class="text-sm text-neutral-500 bg-primary-50 px-3 py-1 rounded-full font-medium">
                {{ course.enrollments_count }} enrolled
              </div>
            </div>
            <div v-if="!popularCourses.length" class="px-6 py-8 text-center text-neutral-400">
              <BookOpen class="w-12 h-12 mx-auto mb-3 opacity-50" />
              <p>No courses yet</p>
            </div>
          </div>
        </Card>

        <!-- Recent Users -->
        <Card class="shadow-lg border-t-4 border-t-secondary-500 bg-gradient-to-br from-white to-secondary-50/20">
          <template #header>
            <div class="flex items-center justify-between pb-2 border-b border-secondary-100">
              <h2 class="text-lg font-bold text-secondary-900 flex items-center gap-2">
                <div class="h-1 w-1 rounded-full bg-secondary-500 animate-pulse"></div>
                Recent Users
              </h2>
              <Link href="/admin/users" class="text-sm font-medium text-secondary-700 hover:text-secondary-900 transition-colors font-semibold">View All →</Link>
            </div>
          </template>
          <div class="divide-y divide-neutral-100">
            <div v-for="user in recentUsers" :key="user.id" class="px-4 py-3 flex items-center justify-between hover:bg-gradient-to-r hover:from-secondary-50 hover:to-white transition-all duration-200 rounded-lg group">
              <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-sm font-bold text-white shadow-md">
                  {{ getInitials(user.name) }}
                </div>
                <div>
                  <div class="font-medium text-neutral-900 flex items-center gap-2 group-hover:text-secondary-900">
                    {{ user.name }}
                    <span v-if="user.is_admin" class="text-xs bg-primary-100 text-primary-700 px-2 py-0.5 rounded-full font-semibold">Admin</span>
                  </div>
                  <div class="text-sm text-neutral-500">{{ user.email }}</div>
                </div>
              </div>
              <div class="text-xs text-neutral-400">{{ user.created_at }}</div>
            </div>
            <div v-if="!recentUsers.length" class="px-6 py-8 text-center text-neutral-400">
              <Users class="w-12 h-12 mx-auto mb-3 opacity-50" />
              <p>No users yet</p>
            </div>
          </div>
        </Card>

        <!-- Recent Enrollments -->
        <Card class="shadow-lg border-t-4 border-t-emerald-500 bg-gradient-to-br from-white to-emerald-50/20">
          <template #header>
            <div class="flex items-center justify-between pb-2 border-b border-emerald-100">
              <h2 class="text-lg font-bold text-emerald-900 flex items-center gap-2">
                <div class="h-1 w-1 rounded-full bg-emerald-500 animate-pulse"></div>
                Recent Enrollments
              </h2>
            </div>
          </template>
          <div class="divide-y divide-neutral-100">
            <div v-for="enrollment in recentEnrollments" :key="enrollment.id" class="px-4 py-3 flex items-center justify-between hover:bg-gradient-to-r hover:from-emerald-50 hover:to-white transition-all duration-200 rounded-lg group">
              <div class="flex items-center gap-4">
                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-md">
                  <GraduationCap class="w-5 h-5 text-white" />
                </div>
                <div>
                  <div class="font-medium text-neutral-900 group-hover:text-emerald-900">{{ enrollment.user_name }}</div>
                  <div class="text-sm text-neutral-500">enrolled in <span class="text-primary-600 font-semibold">{{ enrollment.course_title }}</span></div>
                </div>
              </div>
              <div class="text-sm text-neutral-400">{{ enrollment.enrolled_at }}</div>
            </div>
            <div v-if="!recentEnrollments.length" class="px-6 py-8 text-center text-neutral-400">
              <GraduationCap class="w-12 h-12 mx-auto mb-3 opacity-50" />
              <p>No enrollments yet</p>
            </div>
          </div>
        </Card>
      </div>

      <!-- Right Column: Quick Actions -->
      <div class="space-y-6">
        <!-- Quick Links -->
        <Card class="shadow-lg bg-gradient-to-br from-primary-100 via-white to-secondary-50 border-2 border-primary-200">
          <template #header>
            <div class="flex items-center justify-between pb-2 border-b border-primary-200">
              <h3 class="font-bold text-primary-900 flex items-center gap-2">
                <div class="h-1 w-1 rounded-full bg-primary-600 animate-pulse"></div>
                Quick Actions
              </h3>
            </div>
          </template>
          <div class="grid grid-cols-1 gap-3">
            <Link href="/admin/courses/create" class="p-4 rounded-xl border-2 border-primary-200 hover:border-primary-400 hover:bg-gradient-to-r hover:from-primary-50 hover:to-white transition-all group shadow-sm hover:shadow-md">
              <div class="flex items-center gap-3">
                <div class="h-12 w-12 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center group-hover:from-primary-300 group-hover:to-primary-500 transition-all shadow-md">
                  <Plus class="w-6 h-6 text-white" />
                </div>
                <div>
                  <div class="font-medium text-neutral-900 group-hover:text-primary-900">Create Course</div>
                  <div class="text-xs text-neutral-500">Add new course</div>
                </div>
              </div>
            </Link>
            <Link href="/admin/users/create" class="p-4 rounded-xl border-2 border-primary-200 hover:border-primary-400 hover:bg-gradient-to-r hover:from-primary-50 hover:to-white transition-all group shadow-sm hover:shadow-md">
              <div class="flex items-center gap-3">
                <div class="h-12 w-12 rounded-full bg-gradient-to-br from-secondary-400 to-secondary-600 flex items-center justify-center group-hover:from-secondary-300 group-hover:to-secondary-500 transition-all shadow-md">
                  <UserPlus class="w-6 h-6 text-white" />
                </div>
                <div>
                  <div class="font-medium text-neutral-900 group-hover:text-primary-900">Add User</div>
                  <div class="text-xs text-neutral-500">Create new user</div>
                </div>
              </div>
            </Link>
            <Link href="/admin/habits/create" class="p-4 rounded-xl border-2 border-primary-200 hover:border-primary-400 hover:bg-gradient-to-r hover:from-primary-50 hover:to-white transition-all group shadow-sm hover:shadow-md">
              <div class="flex items-center gap-3">
                <div class="h-12 w-12 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center group-hover:from-emerald-300 group-hover:to-emerald-500 transition-all shadow-md">
                  <Target class="w-6 h-6 text-white" />
                </div>
                <div>
                  <div class="font-medium text-neutral-900 group-hover:text-primary-900">Create Habit</div>
                  <div class="text-xs text-neutral-500">Lesson-based (appears after lesson complete)</div>
                </div>
              </div>
            </Link>
            <Link href="/admin/moderation" class="p-4 rounded-xl border-2 border-primary-200 hover:border-primary-400 hover:bg-gradient-to-r hover:from-primary-50 hover:to-white transition-all group shadow-sm hover:shadow-md">
              <div class="flex items-center gap-3">
                <div class="h-12 w-12 rounded-full bg-gradient-to-br from-primary-700 to-primary-900 flex items-center justify-center group-hover:from-primary-600 group-hover:to-primary-800 transition-all shadow-md">
                  <Shield class="w-6 h-6 text-white" />
                </div>
                <div>
                  <div class="font-medium text-neutral-900 group-hover:text-primary-900">Moderation</div>
                  <div class="text-xs text-neutral-500">Manage discussions</div>
                </div>
              </div>
            </Link>
          </div>
        </Card>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/Common/Card.vue';
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

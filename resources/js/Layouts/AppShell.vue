<template>
  <div class="flex h-screen bg-neutral-50 font-sans text-neutral-900">
    <!-- Sidebar (Desktop) -->
    <aside class="hidden w-72 flex-col border-r border-neutral-200 bg-white shadow-[2px_0_24px_rgba(0,0,0,0.02)] md:flex z-10">
      <div class="flex h-20 items-center px-6 border-b border-neutral-100">
        <!-- Logo Area -->
        <Link href="/dashboard" class="flex items-center gap-3 group">
          <img src="/images/logo.png" alt="Tazkiyah Tarbiyah" class="h-12 w-auto" />
        </Link>
      </div>

      <nav class="flex-1 space-y-1 px-4 py-8 overflow-y-auto">
        <Link v-for="item in navigation" :key="item.name" :href="item.href"
          :class="[
            route().current(item.route) 
              ? 'bg-primary-50 text-primary-900 border-l-4 border-primary-900' 
              : 'text-neutral-600 hover:bg-neutral-50 hover:text-primary-900 border-l-4 border-transparent',
            'group flex items-center px-4 py-3 text-sm font-medium transition-all duration-200 ease-in-out rounded-r-lg mb-1'
          ]">
          <component :is="item.icon" 
            :class="[
              route().current(item.route) ? 'text-primary-900' : 'text-neutral-400 group-hover:text-primary-700',
              'mr-3 h-5 w-5 flex-shrink-0 transition-colors'
            ]" 
          />
          {{ item.name }}
          
          <span v-if="item.badge" class="ml-auto bg-secondary-100 text-secondary-700 py-0.5 px-2 rounded-full text-xs font-semibold">
            {{ item.badge }}
          </span>
        </Link>

        <!-- Admin Section -->
        <template v-if="isAdmin">
          <div class="pt-6 mt-4 border-t border-neutral-200">
            <div class="px-4 mb-2 text-xs font-semibold text-neutral-400 uppercase tracking-wider">Admin Panel</div>
            <Link v-for="item in adminNavigation" :key="item.name" :href="item.href"
              :class="[
                page.url.startsWith(item.activePrefix)
                  ? 'bg-primary-50 text-primary-900 border-l-4 border-primary-900' 
                  : 'text-neutral-600 hover:bg-neutral-50 hover:text-primary-900 border-l-4 border-transparent',
                'group flex items-center px-4 py-3 text-sm font-medium transition-all duration-200 ease-in-out rounded-r-lg mb-1'
              ]">
              <component :is="item.icon" 
                :class="[
                  page.url.startsWith(item.activePrefix) ? 'text-primary-900' : 'text-neutral-400 group-hover:text-primary-700',
                  'mr-3 h-5 w-5 flex-shrink-0 transition-colors'
                ]" 
              />
              {{ item.name }}
            </Link>
          </div>
        </template>
      </nav>

      <div class="border-t border-neutral-200 p-6">
        <Link href="/logout" method="post" as="button" class="group flex w-full items-center px-4 py-3 text-sm font-medium text-neutral-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
          <LogOut class="mr-3 h-5 w-5 text-neutral-400 group-hover:text-red-500 transition-colors" />
          Sign Out
        </Link>
      </div>
    </aside>

    <!-- Mobile Sidebar (Slide-out) -->
    <Teleport to="body">
      <Transition name="mobile-menu">
        <div v-if="isMobileMenuOpen" class="fixed inset-0 z-50 md:hidden">
          <!-- Overlay -->
          <div 
            class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" 
            @click="closeMobileMenu"
          ></div>
          
          <!-- Slide-out Menu -->
          <aside class="fixed inset-y-0 left-0 w-80 max-w-[85vw] bg-white shadow-2xl flex flex-col transform transition-transform">
            <!-- Mobile Header -->
            <div class="flex h-20 items-center justify-between px-6 border-b border-neutral-100">
              <Link href="/dashboard" class="flex items-center gap-2">
                <img src="/images/logo.png" alt="Tazkiyah Tarbiyah" class="h-10 w-auto" />
              </Link>
              <button 
                @click="closeMobileMenu"
                class="p-2 -mr-2 text-neutral-500 hover:bg-neutral-100 rounded-lg transition-colors"
                aria-label="Close menu"
              >
                <X class="h-6 w-6" />
              </button>
            </div>

            <!-- Mobile Navigation -->
            <nav class="flex-1 space-y-1 px-4 py-6 overflow-y-auto">
              <Link 
                v-for="item in navigation" 
                :key="item.name" 
                :href="item.href"
                @click="closeMobileMenu"
                :class="[
                  route().current(item.route) 
                    ? 'bg-primary-50 text-primary-900 border-l-4 border-primary-900' 
                    : 'text-neutral-600 hover:bg-neutral-50 hover:text-primary-900 border-l-4 border-transparent',
                  'group flex items-center px-4 py-3.5 text-base font-medium transition-all duration-200 ease-in-out rounded-r-lg mb-1'
                ]">
                <component :is="item.icon" 
                  :class="[
                    route().current(item.route) ? 'text-primary-900' : 'text-neutral-400 group-hover:text-primary-700',
                    'mr-4 h-6 w-6 flex-shrink-0 transition-colors'
                  ]" 
                />
                {{ item.name }}
                
                <span v-if="item.badge" class="ml-auto bg-secondary-100 text-secondary-700 py-1 px-2.5 rounded-full text-xs font-semibold">
                  {{ item.badge }}
                </span>
              </Link>

              <!-- Admin Section (Mobile) -->
              <template v-if="isAdmin">
                <div class="pt-6 mt-4 border-t border-neutral-200">
                  <div class="px-4 mb-2 text-xs font-semibold text-neutral-400 uppercase tracking-wider">Admin Panel</div>
                  <Link 
                    v-for="item in adminNavigation" 
                    :key="item.name" 
                    :href="item.href"
                    @click="closeMobileMenu"
                    :class="[
                      page.url.startsWith(item.activePrefix)
                        ? 'bg-primary-50 text-primary-900 border-l-4 border-primary-900' 
                        : 'text-neutral-600 hover:bg-neutral-50 hover:text-primary-900 border-l-4 border-transparent',
                      'group flex items-center px-4 py-3.5 text-base font-medium transition-all duration-200 ease-in-out rounded-r-lg mb-1'
                    ]">
                    <component :is="item.icon" 
                      :class="[
                        page.url.startsWith(item.activePrefix) ? 'text-primary-900' : 'text-neutral-400 group-hover:text-primary-700',
                        'mr-4 h-6 w-6 flex-shrink-0 transition-colors'
                      ]" 
                    />
                    {{ item.name }}
                  </Link>
                </div>
              </template>
            </nav>

            <!-- Mobile Footer -->
            <div class="border-t border-neutral-200 p-6">
              <Link 
                href="/logout" 
                method="post" 
                as="button" 
                class="group flex w-full items-center px-4 py-3.5 text-base font-medium text-neutral-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
              >
                <LogOut class="mr-4 h-6 w-6 text-neutral-400 group-hover:text-red-500 transition-colors" />
                Sign Out
              </Link>
            </div>
          </aside>
        </div>
      </Transition>
    </Teleport>

    <!-- Mobile Header + Content -->
    <div class="flex flex-1 flex-col overflow-hidden relative">
        <!-- Top Header (Mobile & Desktop) -->
        <header class="flex h-20 items-center justify-between border-b border-neutral-200 bg-white px-4 md:px-8 z-20">
            <div class="flex items-center md:hidden">
                <!-- Mobile Menu Button -->
                <button 
                  @click="openMobileMenu"
                  class="p-2 -ml-2 text-neutral-600 hover:bg-neutral-50 rounded-lg transition-colors active:bg-neutral-100"
                  aria-label="Open menu"
                >
                    <Menu class="h-6 w-6" />
                </button>
                
                <!-- Mobile Logo -->
                <Link href="/dashboard" class="ml-3 flex items-center">
                  <img src="/images/logo.png" alt="Tazkiyah Tarbiyah" class="h-8 w-auto" />
                </Link>
            </div>
            
            <div class="hidden md:flex flex-1">
                <!-- Breadcrumbs or Search could go here -->
                <h2 class="font-serif text-xl text-primary-900 font-semibold" v-if="headerTitle">{{ headerTitle }}</h2>
            </div>

            <div class="flex flex-1 justify-end items-center gap-3 md:gap-6">
                <!-- Notifications / Actions -->
                <button class="p-2 text-neutral-400 hover:text-primary-900 transition-colors relative">
                    <Bell class="h-5 w-5" />
                    <span class="absolute top-1.5 right-1.5 h-2 w-2 bg-secondary-500 rounded-full border-2 border-white"></span>
                </button>

                <div class="h-6 w-px bg-neutral-200 hidden sm:block"></div>

                <div class="flex items-center space-x-2 md:space-x-3 cursor-pointer group">
                   <div class="text-right hidden md:block">
                     <div class="text-sm font-medium text-neutral-900 group-hover:text-primary-900 transition-colors">{{ $page.props.auth.user?.name || 'Guest' }}</div>
                     <div class="text-xs text-neutral-500">{{ $page.props.auth.user?.is_admin ? 'Admin' : 'Student' }}</div>
                   </div>
                   <!-- Avatar -->
                   <div class="h-9 w-9 md:h-10 md:w-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-900 font-bold text-sm ring-2 ring-transparent group-hover:ring-primary-200 transition-all shadow-sm">
                     {{ userInitials }}
                   </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto bg-neutral-50 scroll-smooth">
            <div class="max-w-7xl mx-auto px-4 py-6 md:py-8 md:px-8">
                <slot />
            </div>
        </main>
    </div>
  </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { Home, BookOpen, CheckSquare, MessageCircle, Award, Settings, LogOut, Menu, Bell, X, LayoutDashboard, Users, FolderOpen, Video, Target, Shield } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const page = usePage();

// Mobile menu state
const isMobileMenuOpen = ref(false);

const openMobileMenu = () => {
  isMobileMenuOpen.value = true;
  // Prevent body scroll when menu is open
  document.body.style.overflow = 'hidden';
};

const closeMobileMenu = () => {
  isMobileMenuOpen.value = false;
  // Restore body scroll
  document.body.style.overflow = '';
};

// Close menu on route change
watch(() => page.url, () => {
  if (isMobileMenuOpen.value) {
    closeMobileMenu();
  }
});

// Dynamic Header Title based on route or prop (simplified for now)
const headerTitle = computed(() => {
    // Basic mapping, could be more robust
    const url = page.url;
    if (url.includes('courses')) return 'My Courses';
    if (url.includes('habits')) return 'Habit Tracker';
    if (url.includes('leaderboard')) return 'Community & Ranking';
    return 'Dashboard';
});

// Compute user initials from authenticated user
const userInitials = computed(() => {
    const name = page.props.auth?.user?.name || 'Guest';
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
});

const navigation = [
  { name: 'Dashboard', href: '/dashboard', route: 'dashboard', icon: Home },
  { name: 'My Courses', href: '/courses', route: 'courses.index', icon: BookOpen, badge: '2' },
  { name: 'Habit Tracker', href: '/habits', route: 'habits.index', icon: CheckSquare },
  { name: 'Community', href: '/leaderboard', route: 'leaderboard.index', icon: MessageCircle },
  { name: 'Certificates', href: '/certificates', route: 'certificates.index', icon: Award },
];

// Admin navigation
const adminNavigation = [
  { name: 'Admin Dashboard', href: '/admin', activePrefix: '/admin', icon: LayoutDashboard },
  { name: 'Users', href: '/admin/users', activePrefix: '/admin/users', icon: Users },
  { name: 'Courses', href: '/admin/courses', activePrefix: '/admin/courses', icon: BookOpen },
  { name: 'Modules', href: '/admin/modules', activePrefix: '/admin/modules', icon: FolderOpen },
  { name: 'Lessons', href: '/admin/lessons', activePrefix: '/admin/lessons', icon: Video },
  { name: 'Habits', href: '/admin/habits', activePrefix: '/admin/habits', icon: Target },
  { name: 'Moderation', href: '/admin/moderation', activePrefix: '/admin/moderation', icon: Shield },
];

// Check if user is admin
const isAdmin = computed(() => page.props.auth?.user?.is_admin);
</script>

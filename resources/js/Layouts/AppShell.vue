<template>
  <div class="flex h-screen bg-neutral-50 font-sans text-neutral-900">
    <!-- Sidebar (Desktop) -->
    <aside v-if="!isLessonPage" class="hidden w-72 flex-col border-r border-primary-600 bg-white shadow-[2px_0_24px_rgba(0,0,0,0.02)] md:flex z-10">
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
            <div class="px-4 mb-2 text-xs font-semibold text-neutral-400 uppercase tracking-wider">{{ t('nav.admin_panel') }}</div>
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

      <div class="border-t border-neutral-200 p-6 space-y-1">
        <Link href="/profile" class="group flex w-full items-center px-4 py-3 text-sm font-medium text-neutral-600 hover:text-primary-900 hover:bg-primary-50 rounded-lg transition-colors">
          <Settings class="mr-3 h-5 w-5 text-neutral-400 group-hover:text-primary-600 transition-colors" />
          Profile
        </Link>
        <Link href="/logout" method="post" as="button" class="group flex w-full items-center px-4 py-3 text-sm font-medium text-neutral-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
          <LogOut class="mr-3 h-5 w-5 text-neutral-400 group-hover:text-red-500 transition-colors" />
          {{ t('nav.sign_out') }}
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
                  <div class="px-4 mb-2 text-xs font-semibold text-neutral-400 uppercase tracking-wider">{{ t('nav.admin_panel') }}</div>
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
            <div class="border-t border-neutral-200 p-6 space-y-4">
              <Link href="/profile" @click="closeMobileMenu" class="group flex w-full items-center px-4 py-3.5 text-base font-medium text-neutral-600 hover:text-primary-900 hover:bg-primary-50 rounded-lg transition-colors">
                <Settings class="mr-4 h-6 w-6 text-neutral-400 group-hover:text-primary-600 transition-colors" />
                Profile
              </Link>
              <!-- Language switch -->
              <div class="flex items-center justify-between">
                <span class="text-sm text-neutral-500">Language</span>
                <div class="inline-flex items-center rounded-lg border border-primary-200 bg-primary-50 p-0.5">
                  <form method="post" action="/locale" class="inline" @submit="closeMobileMenu">
                    <input type="hidden" name="_token" :value="csrfToken" />
                    <input type="hidden" name="locale" value="en" />
                    <button
                      type="submit"
                      :class="[
                        'px-3 py-1.5 text-xs font-medium rounded-md transition-all min-w-[3rem]',
                        ($page?.props?.locale === 'en' || $page?.props?.locale === 'en_roman')
                          ? 'bg-primary-600 text-white shadow-sm'
                          : 'text-primary-600 hover:bg-primary-100 hover:text-primary-900'
                      ]"
                    >
                      EN
                    </button>
                  </form>
                  <form method="post" action="/locale" class="inline" @submit="closeMobileMenu">
                    <input type="hidden" name="_token" :value="csrfToken" />
                    <input type="hidden" name="locale" value="ur" />
                    <button
                      type="submit"
                      :class="[
                        'px-3 py-1.5 text-xs font-medium rounded-md transition-all min-w-[3rem]',
                        $page?.props?.locale === 'ur'
                          ? 'bg-primary-600 text-white shadow-sm'
                          : 'text-primary-600 hover:bg-primary-100 hover:text-primary-900'
                      ]"
                    >
                      اردو
                    </button>
                  </form>
                </div>
              </div>
              <Link
                href="/logout"
                method="post"
                as="button"
                class="group flex w-full items-center px-4 py-3.5 text-base font-medium text-neutral-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
              >
                <LogOut class="mr-4 h-6 w-6 text-neutral-400 group-hover:text-red-500 transition-colors" />
                {{ t('nav.sign_out') }}
              </Link>
            </div>
          </aside>
        </div>
      </Transition>
    </Teleport>

    <!-- Mobile Header + Content -->
    <div class="flex flex-1 flex-col overflow-hidden relative">
        <!-- Top Header (Mobile & Desktop) -->
        <header class="flex h-20 items-center justify-between border-b border-primary-600 bg-white px-4 md:px-8 z-20">
            <div class="flex items-center">
                <!-- Hamburger Menu (Lesson Pages Only) -->
                <div v-if="isLessonPage" class="relative mr-3" ref="lessonMenuRef">
                    <button
                      @click="showLessonMenu = !showLessonMenu"
                      class="p-2 text-primary-600 hover:text-primary-700 hover:bg-primary-50 rounded-lg transition-colors active:bg-primary-100"
                      aria-label="Open menu"
                    >
                        <Menu class="h-6 w-6" />
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <Transition
                      enter-active-class="transition ease-out duration-100"
                      enter-from-class="transform opacity-0 scale-95"
                      enter-to-class="transform opacity-100 scale-100"
                      leave-active-class="transition ease-in duration-75"
                      leave-from-class="transform opacity-100 scale-100"
                      leave-to-class="transform opacity-0 scale-95"
                    >
                        <div
                          v-if="showLessonMenu"
                          class="absolute left-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-neutral-200 z-50 py-2"
                        >
                            <Link
                              v-for="item in navigation"
                              :key="item.name"
                              :href="item.href"
                              @click="showLessonMenu = false"
                              :class="[
                                'flex items-center px-4 py-3 text-sm font-medium transition-colors',
                                route().current(item.route)
                                  ? 'bg-primary-50 text-primary-900'
                                  : 'text-neutral-600 hover:bg-neutral-50 hover:text-primary-900'
                              ]"
                            >
                                <component :is="item.icon" class="mr-3 h-5 w-5 flex-shrink-0" />
                                {{ item.name }}
                                <span v-if="item.badge" class="ml-auto bg-secondary-100 text-secondary-700 py-0.5 px-2 rounded-full text-xs font-semibold">
                                  {{ item.badge }}
                                </span>
                            </Link>
                            
                            <!-- Admin Section -->
                            <template v-if="isAdmin">
                                <div class="border-t border-neutral-200 my-2"></div>
                                <div class="px-4 py-2 text-xs font-semibold text-neutral-400 uppercase tracking-wider">{{ t('nav.admin_panel') }}</div>
                                <Link
                                  v-for="item in adminNavigation"
                                  :key="item.name"
                                  :href="item.href"
                                  @click="showLessonMenu = false"
                                  :class="[
                                    'flex items-center px-4 py-3 text-sm font-medium transition-colors',
                                    page.url.startsWith(item.activePrefix)
                                      ? 'bg-primary-50 text-primary-900'
                                      : 'text-neutral-600 hover:bg-neutral-50 hover:text-primary-900'
                                  ]"
                                >
                                    <component :is="item.icon" class="mr-3 h-5 w-5 flex-shrink-0" />
                                    {{ item.name }}
                                </Link>
                            </template>
                            
                            <!-- Logout -->
                            <div class="border-t border-neutral-200 my-2"></div>
                            <Link
                              href="/logout"
                              method="post"
                              as="button"
                              @click="showLessonMenu = false"
                              class="w-full flex items-center px-4 py-3 text-sm font-medium text-neutral-500 hover:text-red-600 hover:bg-red-50 transition-colors"
                            >
                                <LogOut class="mr-3 h-5 w-5 text-neutral-400" />
                                {{ t('nav.sign_out') }}
                            </Link>
                        </div>
                    </Transition>
                </div>

                <!-- Mobile Menu Button (Non-Lesson Pages) -->
                <button
                  v-if="!isLessonPage"
                  @click="openMobileMenu"
                  class="p-2 -ml-2 text-neutral-600 hover:bg-neutral-50 rounded-lg transition-colors active:bg-neutral-100 md:hidden"
                  aria-label="Open menu"
                >
                    <Menu class="h-6 w-6" />
                </button>

                <!-- Mobile Logo (Only show on mobile or when sidebar is hidden) -->
                <Link v-if="isLessonPage" href="/dashboard" class="ml-3 flex items-center md:ml-0">
                  <img src="/images/logo.png" alt="Tazkiyah Tarbiyah" class="h-8 w-auto" />
                </Link>
            </div>

            <div class="hidden md:flex flex-1">
                <!-- Breadcrumbs or Search could go here -->
                <h2
                  class="font-serif text-xl text-primary-900 font-semibold"
                  v-if="headerTitle && !isLessonPage"
                  :class="locale === 'ur' ? 'text-right w-full' : ''"
                >
                  {{ headerTitle }}
                </h2>
            </div>

            <div class="flex flex-1 justify-end items-center gap-3 md:gap-6">
                <!-- Language switch: English | اردو -->
                <div class="inline-flex items-center rounded-lg border border-primary-200 bg-primary-50 p-0.5">
                  <form method="post" action="/locale" class="inline">
                    <input type="hidden" name="_token" :value="csrfToken" />
                    <input type="hidden" name="locale" value="en" />
                    <button
                      type="submit"
                      :class="[
                        'px-3 py-1.5 text-xs font-medium rounded-md transition-all min-w-[3rem]',
                        ($page?.props?.locale === 'en' || $page?.props?.locale === 'en_roman')
                          ? 'bg-primary-600 text-white shadow-sm'
                          : 'text-primary-600 hover:bg-primary-100 hover:text-primary-900'
                      ]"
                    >
                      EN
                    </button>
                  </form>
                  <form method="post" action="/locale" class="inline">
                    <input type="hidden" name="_token" :value="csrfToken" />
                    <input type="hidden" name="locale" value="ur" />
                    <button
                      type="submit"
                      :class="[
                        'px-3 py-1.5 text-xs font-medium rounded-md transition-all min-w-[3rem]',
                        $page?.props?.locale === 'ur'
                          ? 'bg-primary-600 text-white shadow-sm'
                          : 'text-primary-600 hover:bg-primary-100 hover:text-primary-900'
                      ]"
                    >
                      اردو
                    </button>
                  </form>
                </div>

                <!-- Notifications / Actions -->
                <NotificationDropdown />

                <div class="h-6 w-px bg-neutral-200 hidden sm:block"></div>

                <Link href="/profile" class="flex items-center space-x-2 md:space-x-3 cursor-pointer group">
                  <div :class="['hidden md:block', locale === 'ur' ? 'text-right' : 'text-right']">
                     <div class="text-sm font-medium text-neutral-900 group-hover:text-primary-900 transition-colors">{{ $page?.props?.auth?.user?.name || 'Guest' }}</div>
                     <div class="text-xs text-neutral-500">{{ $page?.props?.auth?.user?.is_admin ? 'Admin' : 'Student' }}</div>
                   </div>
                   <!-- Avatar -->
                   <div v-if="$page?.props?.auth?.user?.avatar_url" class="h-9 w-9 md:h-10 md:w-10 rounded-full overflow-hidden ring-2 ring-transparent group-hover:ring-primary-200 transition-all shadow-sm">
                     <img :src="$page.props.auth.user.avatar_url" :alt="$page.props.auth.user.name" class="h-full w-full object-cover" />
                   </div>
                   <div v-else class="h-9 w-9 md:h-10 md:w-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-900 font-bold text-sm ring-2 ring-transparent group-hover:ring-primary-200 transition-all shadow-sm">
                     {{ userInitials }}
                   </div>
                </Link>
            </div>
        </header>

        <!-- Main Content -->
        <main :class="isLessonPage ? 'flex-1 overflow-hidden bg-neutral-50' : 'flex-1 overflow-y-auto bg-neutral-50 scroll-smooth'">
            <div v-if="isLessonPage" class="h-full">
                <slot />
            </div>
            <div v-else class="max-w-7xl mx-auto px-4 py-6 md:py-8 md:px-8">
                <slot />
            </div>
        </main>
    </div>

    <!-- Global Toast Notification -->
    <Toast
      :show="toast.show"
      :message="toast.message"
      :type="toast.type"
      @close="hideToast"
    />
  </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { Home, BookOpen, CheckSquare, MessageCircle, Award, Settings, LogOut, Menu, Bell, X, LayoutDashboard, Users, FolderOpen, Video, Target, Shield } from 'lucide-vue-next';
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import NotificationDropdown from '@/Components/NotificationDropdown.vue';
import Toast from '@/Components/Toast.vue';
import { useToast } from '@/composables/useToast';
import { useI18n } from '@/i18n';

const page = usePage();
const { t, locale } = useI18n();

// CSRF token: from Inertia props or meta tag (avoid document in template – can be undefined during SSR/hydration)
const csrfToken = computed(() => {
  const fromProps = page.props?.csrf_token;
  if (fromProps) return fromProps;
  if (typeof document !== 'undefined') {
    return document.querySelector('meta[name=csrf-token]')?.getAttribute('content') ?? '';
  }
  return '';
});

// Mobile menu state
const isMobileMenuOpen = ref(false);
const showLessonMenu = ref(false);
const lessonMenuRef = ref(null);

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

// Close lesson menu when clicking outside
function handleLessonMenuClickOutside(event) {
  if (lessonMenuRef.value && !lessonMenuRef.value.contains(event.target)) {
    showLessonMenu.value = false;
  }
}

// Close menu on route change
watch(() => page.url, () => {
  if (isMobileMenuOpen.value) {
    closeMobileMenu();
  }
  if (showLessonMenu.value) {
    showLessonMenu.value = false;
  }
});

// Add click outside listener for lesson menu
onMounted(() => {
  document.addEventListener('click', handleLessonMenuClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleLessonMenuClickOutside);
});

// Check if we're on a lesson page
const isLessonPage = computed(() => {
    return page.url.includes('/lessons/') && page.url.match(/\/courses\/\d+\/lessons\/\d+/);
});

// Dynamic Header Title based on route or prop (simplified for now)
const headerTitle = computed(() => {
    const url = page.url;
    if (url.includes('/profile')) return 'Profile';
    if (url.includes('courses')) return t('nav.my_courses');
    if (url.includes('habits')) return t('nav.habit_tracker');
    if (url.includes('leaderboard')) return t('nav.community');
    if (url.includes('certificates')) return t('nav.certificates');
    return t('nav.dashboard');
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
  { name: t('nav.dashboard'), href: '/dashboard', route: 'dashboard', icon: Home },
  { name: t('nav.my_courses'), href: '/courses', route: 'courses.index', icon: BookOpen, badge: '2' },
  { name: t('nav.habit_tracker'), href: '/habits', route: 'habits.index', icon: CheckSquare },
  { name: t('nav.community'), href: '/leaderboard', route: 'leaderboard.index', icon: MessageCircle },
  { name: t('nav.certificates'), href: '/certificates', route: 'certificates.index', icon: Award },
];

// Admin navigation
const adminNavigation = [
  { name: t('nav.admin_dashboard'), href: '/admin', activePrefix: '/admin', icon: LayoutDashboard },
  { name: t('nav.admin_users'), href: '/admin/users', activePrefix: '/admin/users', icon: Users },
  { name: t('nav.admin_courses'), href: '/admin/courses', activePrefix: '/admin/courses', icon: BookOpen },
  { name: t('nav.admin_modules'), href: '/admin/modules', activePrefix: '/admin/modules', icon: FolderOpen },
  { name: t('nav.admin_lessons'), href: '/admin/lessons', activePrefix: '/admin/lessons', icon: Video },
  { name: t('nav.admin_habits'), href: '/admin/habits', activePrefix: '/admin/habits', icon: Target },
  { name: t('nav.admin_moderation'), href: '/admin/moderation', activePrefix: '/admin/moderation', icon: Shield },
];

// Check if user is admin
const isAdmin = computed(() => page.props.auth?.user?.is_admin);

// Toast functionality
const { toast, hideToast } = useToast();

// Make toast available globally
window.showToast = (message, type = 'info') => {
  toast.value = {
    show: true,
    message,
    type,
    duration: 5000,
  };
};

window.showSuccess = (message) => window.showToast(message, 'success');
window.showError = (message) => window.showToast(message, 'error');
window.showWarning = (message) => window.showToast(message, 'warning');
window.showInfo = (message) => window.showToast(message, 'info');
</script>

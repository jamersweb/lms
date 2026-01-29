<template>
  <div class="relative" ref="dropdownRef">
    <button
      @click="toggleDropdown"
      class="p-2 text-neutral-400 hover:text-primary-900 transition-colors relative"
      aria-label="Notifications"
    >
      <Bell class="h-5 w-5" />
      <span
        v-if="unreadCount > 0"
        class="absolute top-1.5 right-1.5 h-2 w-2 bg-secondary-500 rounded-full border-2 border-white"
      ></span>
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 h-5 w-5 bg-primary-600 text-white text-xs rounded-full flex items-center justify-center font-bold"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2 w-96 bg-white rounded-xl shadow-lg border border-neutral-200 z-50 max-h-[500px] flex flex-col"
      >
        <!-- Header -->
        <div class="p-4 border-b border-neutral-200 flex items-center justify-between">
          <h3 class="font-bold text-neutral-900">Notifications</h3>
          <div class="flex items-center gap-2">
            <button
              v-if="unreadCount > 0"
              @click="markAllAsRead"
              class="text-xs text-primary-600 hover:text-primary-700 font-medium"
              :disabled="markingAllAsRead"
            >
              Mark all as read
            </button>
            <button
              @click="isOpen = false"
              class="text-neutral-400 hover:text-neutral-600"
            >
              <X class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Notifications List -->
        <div class="overflow-y-auto flex-1">
          <div v-if="loading" class="p-8 text-center text-neutral-500">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
            <p class="mt-2 text-sm">Loading notifications...</p>
          </div>

          <div v-else-if="notifications.length === 0" class="p-8 text-center text-neutral-500">
            <Bell class="w-12 h-12 mx-auto mb-3 opacity-50" />
            <p class="text-sm">No notifications yet</p>
          </div>

          <div v-else class="divide-y divide-neutral-100">
            <div
              v-for="notification in notifications"
              :key="notification.id"
              @click="handleNotificationClick(notification)"
              :class="[
                'p-4 hover:bg-neutral-50 cursor-pointer transition-colors',
                !notification.read_at ? 'bg-primary-50/50' : ''
              ]"
            >
              <div class="flex items-start gap-3">
                <div :class="[
                  'h-10 w-10 rounded-full flex items-center justify-center shrink-0',
                  getNotificationIconClass(notification.type)
                ]">
                  <component :is="getNotificationIcon(notification.type)" class="w-5 h-5" />
                </div>
                <div class="flex-1 min-w-0">
                  <h4 class="text-sm font-medium text-neutral-900 mb-1">
                    {{ getNotificationTitle(notification) }}
                  </h4>
                  <p class="text-xs text-neutral-600 line-clamp-2">
                    {{ getNotificationBody(notification) }}
                  </p>
                  <p class="text-xs text-neutral-400 mt-1">
                    {{ notification.created_at }}
                  </p>
                </div>
                <div v-if="!notification.read_at" class="h-2 w-2 bg-primary-600 rounded-full shrink-0 mt-2"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div v-if="notifications.length > 0" class="p-3 border-t border-neutral-200">
          <Link
            href="#"
            class="block text-center text-sm text-primary-600 hover:text-primary-700 font-medium"
            @click="isOpen = false"
          >
            View all notifications
          </Link>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell, X, MessageCircle, Award, AlertCircle, CheckCircle, FileText } from 'lucide-vue-next';
import axios from 'axios';
import { route } from 'ziggy-js';

const dropdownRef = ref(null);
const isOpen = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
const loading = ref(false);
const markingAllAsRead = ref(false);

const page = usePage();

function toggleDropdown() {
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    loadNotifications();
  }
}

async function loadNotifications() {
  loading.value = true;
  try {
    const response = await axios.get(route('notifications.index'));
    notifications.value = response.data.notifications;
    unreadCount.value = response.data.unread_count;
  } catch (error) {
    console.error('Failed to load notifications:', error);
  } finally {
    loading.value = false;
  }
}

async function markAllAsRead() {
  markingAllAsRead.value = true;
  try {
    await axios.post(route('notifications.read-all'));
    notifications.value = notifications.value.map(n => ({ ...n, read_at: new Date().toISOString() }));
    unreadCount.value = 0;
  } catch (error) {
    console.error('Failed to mark all as read:', error);
  } finally {
    markingAllAsRead.value = false;
  }
}

async function markAsRead(notificationId) {
  try {
    await axios.post(route('notifications.read', notificationId));
    const notification = notifications.value.find(n => n.id === notificationId);
    if (notification) {
      notification.read_at = new Date().toISOString();
      unreadCount.value = Math.max(0, unreadCount.value - 1);
    }
  } catch (error) {
    console.error('Failed to mark as read:', error);
  }
}

function handleNotificationClick(notification) {
  if (!notification.read_at) {
    markAsRead(notification.id);
  }

  // Navigate based on notification type
  if (notification.type === 'App\\Notifications\\BroadcastNotification') {
    // Stay on current page or navigate to relevant content
    isOpen.value = false;
  } else if (notification.type === 'App\\Notifications\\StagnationReminderNotification') {
    router.visit(route('dashboard'));
    isOpen.value = false;
  }
}

function getNotificationIcon(type) {
  if (type.includes('Broadcast')) return MessageCircle;
  if (type.includes('Stagnation')) return AlertCircle;
  if (type.includes('Certificate')) return Award;
  return Bell;
}

function getNotificationIconClass(type) {
  if (type.includes('Broadcast')) return 'bg-blue-50 text-blue-600';
  if (type.includes('Stagnation')) return 'bg-amber-50 text-amber-600';
  if (type.includes('Certificate')) return 'bg-emerald-50 text-emerald-600';
  return 'bg-primary-50 text-primary-600';
}

function getNotificationTitle(notification) {
  if (notification.type === 'App\\Notifications\\BroadcastNotification') {
    return notification.data.subject || 'New Broadcast';
  }
  if (notification.type === 'App\\Notifications\\StagnationReminderNotification') {
    return 'Time to Continue Learning';
  }
  return 'Notification';
}

function getNotificationBody(notification) {
  if (notification.type === 'App\\Notifications\\BroadcastNotification') {
    return notification.data.body || '';
  }
  if (notification.type === 'App\\Notifications\\StagnationReminderNotification') {
    const days = notification.data.days || 0;
    return `We haven't seen any activity from you in the last ${days} days. Continue your learning journey!`;
  }
  return 'You have a new notification';
}

// Close dropdown when clicking outside
function handleClickOutside(event) {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isOpen.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  // Get initial unread count from Inertia props
  const page = usePage();
  if (page.props.unread_notifications_count !== undefined) {
    unreadCount.value = page.props.unread_notifications_count;
  }
  // Load notifications if dropdown is opened
  if (isOpen.value) {
    loadNotifications();
  }
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

// Refresh notifications periodically when dropdown is open
let refreshInterval = null;
watch(isOpen, (open) => {
  if (open) {
    refreshInterval = setInterval(() => {
      loadNotifications();
    }, 30000); // Refresh every 30 seconds
  } else {
    if (refreshInterval) {
      clearInterval(refreshInterval);
      refreshInterval = null;
    }
  }
});
</script>

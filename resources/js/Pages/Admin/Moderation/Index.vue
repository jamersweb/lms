<template>
  <AppShell>
    <Head title="Admin - Moderation" />
    
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-2xl font-serif font-bold text-primary-900">Moderation Panel</h1>
        <p class="text-neutral-600 mt-1">Manage community discussions and content</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Discussions -->
        <div class="lg:col-span-2 space-y-4">
          <h2 class="text-lg font-semibold text-neutral-800">Recent Discussions</h2>
          
          <div v-for="discussion in discussions.data" :key="discussion.id" class="bg-white rounded-xl border border-neutral-200 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <h3 class="font-medium text-neutral-900">{{ discussion.title }}</h3>
                  <span v-if="discussion.deleted_at" class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                    Deleted
                  </span>
                  <span v-else-if="discussion.status === 'closed'" class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                    Locked
                  </span>
                  <span v-else class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                    Open
                  </span>
                </div>
                <p class="text-sm text-neutral-500 mt-1">
                  By {{ discussion.user.name }} • {{ new Date(discussion.created_at).toLocaleDateString() }}
                </p>
              </div>
            </div>
            
            <div class="flex items-center gap-2 mt-4 pt-4 border-t border-neutral-100">
              <a 
                :href="`/discussions/${discussion.id}`" 
                target="_blank"
                class="px-3 py-1.5 text-sm text-primary-600 hover:bg-primary-50 rounded-lg transition-colors flex items-center gap-1"
              >
                <ExternalLink class="w-4 h-4" />
                View
              </a>
              
              <button 
                v-if="!discussion.deleted_at" 
                @click="handleAction('delete', discussion.id, 'discussion')"
                class="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors flex items-center gap-1"
              >
                <Trash2 class="w-4 h-4" />
                Delete
              </button>
              <button 
                v-else 
                @click="handleAction('restore', discussion.id, 'discussion')"
                class="px-3 py-1.5 text-sm text-green-600 hover:bg-green-50 rounded-lg transition-colors flex items-center gap-1"
              >
                <RotateCcw class="w-4 h-4" />
                Restore
              </button>
              
              <button 
                v-if="discussion.status === 'open'" 
                @click="handleAction('lock', discussion.id, 'discussion')"
                class="px-3 py-1.5 text-sm text-amber-600 hover:bg-amber-50 rounded-lg transition-colors flex items-center gap-1"
              >
                <Lock class="w-4 h-4" />
                Lock
              </button>
              <button 
                v-else 
                @click="handleAction('unlock', discussion.id, 'discussion')"
                class="px-3 py-1.5 text-sm text-blue-600 hover:bg-blue-50 rounded-lg transition-colors flex items-center gap-1"
              >
                <Unlock class="w-4 h-4" />
                Unlock
              </button>
            </div>
          </div>
          
          <div v-if="discussions.data.length === 0" class="bg-white rounded-xl border border-neutral-200 p-12 text-center">
            <MessageSquare class="w-12 h-12 mx-auto text-neutral-300 mb-3" />
            <p class="text-neutral-500">No discussions found</p>
          </div>

          <!-- Pagination -->
          <div v-if="discussions.links && discussions.links.length > 3" class="flex items-center justify-center gap-1">
            <Link 
              v-for="link in discussions.links" 
              :key="link.label" 
              :href="link.url"
              :class="[
                'px-3 py-1.5 rounded-lg text-sm',
                link.active ? 'bg-primary-900 text-white' : 'text-neutral-600 hover:bg-neutral-100',
                !link.url ? 'opacity-50 cursor-not-allowed' : ''
              ]"
              v-html="link.label"
            />
          </div>
        </div>

        <!-- Action Log -->
        <div class="space-y-4">
          <h2 class="text-lg font-semibold text-neutral-800">Recent Actions</h2>
          
          <div class="bg-white rounded-xl border border-neutral-200 p-4">
            <div v-if="actions.length === 0" class="text-center py-8 text-neutral-500">
              <Shield class="w-10 h-10 mx-auto text-neutral-300 mb-2" />
              <p class="text-sm">No moderation actions yet</p>
            </div>
            
            <div v-else class="divide-y divide-neutral-100">
              <div v-for="action in actions" :key="action.id" class="py-3 first:pt-0 last:pb-0">
                <div class="flex items-start gap-3">
                  <div class="w-8 h-8 rounded-full bg-neutral-100 flex items-center justify-center text-xs font-bold text-neutral-600">
                    {{ action.moderator.name?.charAt(0) }}
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm">
                      <span class="font-medium text-neutral-900">{{ action.moderator.name }}</span>
                      <span class="text-neutral-600"> {{ action.action }}d {{ action.target_type }} #{{ action.target_id }}</span>
                    </p>
                    <p class="text-xs text-neutral-400 mt-0.5">
                      {{ new Date(action.created_at).toLocaleString() }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ExternalLink, Trash2, RotateCcw, Lock, Unlock, MessageSquare, Shield } from 'lucide-vue-next';

defineProps({
  discussions: Object,
  actions: Array,
});

function handleAction(action, id, type) {
  if (!confirm(`Are you sure you want to ${action} this ${type}?`)) return;

  router.post('/admin/moderation/handle', {
    action: action,
    target_id: id,
    target_type: type,
    reason: 'Admin action via panel',
  });
}
</script>

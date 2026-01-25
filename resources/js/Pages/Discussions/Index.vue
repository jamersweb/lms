<template>
  <AppShell>
     <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
           <div>
              <div class="flex items-center gap-2 text-sm text-neutral-500 mb-1">
                 <Link :href="route('courses.show', course.id)" class="hover:text-primary-600 hover:underline">{{ course.title }}</Link>
                 <span>/</span>
                 <span>Discussions</span>
              </div>
              <h1 class="font-serif text-3xl font-bold text-neutral-900">Community Forum</h1>
           </div>
           <Button>New Discussion</Button>
        </div>

        <!-- Filter Tabs (Simplified) -->
        <div class="flex gap-4 border-b border-neutral-200 mb-6">
           <button class="px-4 py-2 border-b-2 border-primary-600 text-primary-700 font-medium text-sm">All Threads</button>
           <button class="px-4 py-2 border-b-2 border-transparent text-neutral-500 hover:text-neutral-700 font-medium text-sm">My Questions</button>
        </div>

        <!-- Discussions List -->
        <div class="space-y-4">
           <Link 
             v-for="discussion in discussions.data" 
             :key="discussion.id"
             :href="route('discussions.show', discussion.id)"
             class="block bg-white p-6 rounded-xl border border-neutral-200 shadow-sm hover:shadow-md transition-all group"
           >
              <div class="flex items-start justify-between">
                 <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                       <Badge v-if="discussion.is_pinned" variant="primary" class="flex items-center gap-1">
                          <Pin class="w-3 h-3" /> Pinned
                       </Badge>
                       <div class="flex items-center gap-2 text-xs text-neutral-500">
                          <img :src="discussion.user.avatar" class="w-5 h-5 rounded-full" />
                          <span class="font-medium text-neutral-700">{{ discussion.user.name }}</span>
                          <span>&bull;</span>
                          <span>{{ discussion.created_at }}</span>
                       </div>
                    </div>
                    
                    <h3 class="text-lg font-bold text-neutral-900 mb-2 group-hover:text-primary-700 transition-colors">
                       {{ discussion.title }}
                    </h3>
                    
                    <p class="text-neutral-600 text-sm line-clamp-2">
                       {{ discussion.body }}
                    </p>
                 </div>
                 
                 <div class="ml-6 flex flex-col items-center justify-center min-w-[60px]">
                    <div class="text-neutral-400 mb-1">
                       <MessageSquare class="w-5 h-5" />
                    </div>
                    <span class="text-sm font-medium text-neutral-600">{{ discussion.replies_count }}</span>
                 </div>
              </div>
           </Link>
        </div>
     </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import Button from '@/Components/Common/Button.vue';
import Badge from '@/Components/Common/Badge.vue';
import { MessageSquare, Pin } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

defineProps({
    course: Object,
    discussions: Object,
    filters: Object
});
</script>

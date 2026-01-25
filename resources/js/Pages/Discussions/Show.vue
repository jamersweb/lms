<template>
  <AppShell>
     <div class="max-w-4xl mx-auto">
        <!-- Breadcrumbs -->
        <div class="flex items-center gap-2 text-sm text-neutral-500 mb-6">
           <Link :href="route('courses.discussions.index', discussion.course_id)" class="hover:text-primary-600 hover:underline">Discussions</Link>
           <span>/</span>
           <span class="text-neutral-900 truncate max-w-xs">{{ discussion.title }}</span>
        </div>

        <!-- Main Post -->
        <div class="bg-white rounded-xl border border-neutral-200 shadow-sm p-8 mb-8">
           <div class="flex items-center gap-4 mb-6">
              <img :src="discussion.user.avatar" class="w-12 h-12 rounded-full border border-neutral-100" />
              <div>
                 <h1 class="font-serif text-2xl font-bold text-neutral-900">{{ discussion.title }}</h1>
                 <div class="text-sm text-neutral-500">
                    Posted by <span class="font-medium text-neutral-900">{{ discussion.user.name }}</span> &bull; {{ discussion.created_at }}
                 </div>
              </div>
           </div>
           
           <div class="prose prose-neutral max-w-none text-neutral-700 leading-relaxed">
              {{ discussion.body }}
           </div>
        </div>
        
        <!-- Replies Section -->
        <div class="space-y-6">
           <h3 class="font-bold text-neutral-900 text-lg">{{ replies.length }} Replies</h3>

           <div v-for="reply in replies" :key="reply.id" :class="[
              'p-6 rounded-xl border',
              reply.user.is_instructor 
                 ? 'bg-primary-50 border-primary-100' 
                 : 'bg-white border-neutral-200 shadow-sm'
           ]">
              <div class="flex items-center justify-between mb-4">
                 <div class="flex items-center gap-3">
                    <img :src="reply.user.avatar" class="w-8 h-8 rounded-full bg-white" />
                    <div>
                       <div class="font-medium text-sm text-neutral-900 flex items-center gap-2">
                          {{ reply.user.name }}
                          <Badge v-if="reply.user.is_instructor" variant="primary">Instructor</Badge>
                       </div>
                       <div class="text-xs text-neutral-500">{{ reply.created_at }}</div>
                    </div>
                 </div>
              </div>
              
              <div class="text-neutral-700 leading-relaxed text-sm">
                 {{ reply.body }}
              </div>
           </div>
        </div>

        <!-- Reply Editor -->
        <div class="mt-8 bg-white p-6 rounded-xl border border-neutral-200 shadow-sm">
           <h3 class="font-bold text-neutral-900 mb-4">Leave a Reply</h3>
           <textarea 
             rows="4" 
             class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 mb-4"
             placeholder="Type your response here..."
           ></textarea>
           <div class="flex justify-end">
              <Button>Post Reply</Button>
           </div>
        </div>
     </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import Button from '@/Components/Common/Button.vue';
import Badge from '@/Components/Common/Badge.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    discussion: Object,
    replies: Array
});
</script>

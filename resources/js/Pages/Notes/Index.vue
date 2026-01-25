<template>
  <AppShell>
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="font-serif text-3xl font-bold text-neutral-900">My Notes</h1>
        <p class="text-neutral-600 mt-1">Capture your learnings and reflections.</p>
      </div>
    </div>

    <!-- Coming Soon Banner -->
    <div class="bg-gradient-to-br from-primary-50 to-emerald-50 rounded-2xl border border-primary-100 p-8 md:p-12 text-center">
      <div class="max-w-md mx-auto">
        <div class="h-16 w-16 bg-white rounded-2xl shadow-sm flex items-center justify-center mx-auto mb-6">
          <FileText class="w-8 h-8 text-primary-600" />
        </div>
        
        <span class="inline-block bg-primary-100 text-primary-700 text-xs font-semibold px-3 py-1 rounded-full mb-4">
          Coming in Phase 2
        </span>
        
        <h2 class="font-serif text-2xl font-bold text-neutral-900 mb-3">
          Notes Feature Coming Soon
        </h2>
        
        <p class="text-neutral-600 mb-6">
          Take notes while watching lessons, organize your thoughts, and create a personal knowledge base. 
          This feature will be available in the next phase of development.
        </p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
          <div class="flex items-center gap-2 text-sm text-neutral-500">
            <BookOpen class="w-4 h-4" />
            <span>Lesson-linked notes</span>
          </div>
          <div class="flex items-center gap-2 text-sm text-neutral-500">
            <Tag class="w-4 h-4" />
            <span>Tags & organization</span>
          </div>
          <div class="flex items-center gap-2 text-sm text-neutral-500">
            <Search class="w-4 h-4" />
            <span>Full-text search</span>
          </div>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { FileText, BookOpen, Tag, Search } from 'lucide-vue-next';
</script>

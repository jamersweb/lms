<template>
  <AppShell>
    <div class="max-w-4xl mx-auto">
      <div class="mb-8">
        <h1 class="font-serif text-3xl font-bold text-neutral-900 mb-2">My Certificates</h1>
        <p class="text-sm text-neutral-600">
          View and download your earned certificates
        </p>
      </div>

      <div v-if="certificates.length === 0" class="bg-white border border-neutral-200 rounded-xl p-8 text-center">
        <p class="text-neutral-500">You haven't earned any certificates yet.</p>
        <p class="text-sm text-neutral-400 mt-2">Complete courses to earn certificates!</p>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="cert in certificates"
          :key="cert.id"
          class="bg-white border border-neutral-200 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-2">
                <div class="h-12 w-12 rounded-full bg-primary-100 flex items-center justify-center">
                  <svg class="w-6 h-6 text-primary-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                  </svg>
                </div>
                <div>
                  <h3 class="font-bold text-lg text-neutral-900">
                    {{ cert.type === 'course_completion' ? 'Course Completion' :
                       cert.type === 'level_up' ? 'Level Up' : 'Milestone Achievement' }}
                  </h3>
                  <p class="text-sm text-neutral-500">{{ cert.course_title }}</p>
                </div>
              </div>

              <div class="mt-4 space-y-1 text-sm">
                <div class="flex items-center gap-2 text-neutral-600">
                  <span class="font-medium">Certificate Number:</span>
                  <span class="font-mono text-xs">{{ cert.certificate_number }}</span>
                </div>
                <div class="flex items-center gap-2 text-neutral-600">
                  <span class="font-medium">Issued:</span>
                  <span>{{ cert.issued_at }}</span>
                </div>
                <div v-if="cert.level" class="flex items-center gap-2">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                    {{ cert.level }}
                  </span>
                </div>
              </div>
            </div>

            <div v-if="cert.can_download">
              <a
                :href="route('certificates.download', cert.id)"
                class="inline-flex items-center px-4 py-2 rounded-md bg-primary-900 text-white text-sm font-medium hover:bg-primary-800"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Download PDF
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { route } from 'ziggy-js';

defineProps({
  certificates: Array,
});
</script>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search } from 'lucide-vue-next';
import { ref } from 'vue';
import AppShell from '@/Layouts/AppShell.vue';
const props = defineProps({ questions: Object, admin: Boolean, filters: { type: Object, default: () => ({}) } });
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const base = props.admin ? '/admin/questions' : '/questions';
function filter() { router.get(base, { search: search.value, status: status.value }, { preserveState: true }); }
</script>

<template>
  <AppShell>
    <Head title="Questions" />
    <div class="max-w-4xl space-y-6">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">{{ admin ? 'Student Questions' : 'My Questions' }}</h1>
        <Link v-if="!admin" href="/questions/create" class="inline-flex items-center gap-2 rounded-lg bg-primary-900 px-4 py-2 text-white"><Plus class="h-4 w-4" /> Ask a question</Link>
      </div>
      <form v-if="admin" @submit.prevent="filter" class="flex flex-wrap gap-3">
        <input v-model="search" aria-label="Search questions" placeholder="Search questions" class="min-w-0 flex-1 rounded-lg border-neutral-300" />
        <select v-model="status" aria-label="Status" class="rounded-lg border-neutral-300"><option value="">All statuses</option><option>open</option><option>answered</option><option>resolved</option></select>
        <button aria-label="Search" title="Search" class="rounded-lg border px-3"><Search class="h-5 w-5" /></button>
      </form>
      <p v-if="!questions.data.length" class="py-8 text-neutral-500">No questions found.</p>
      <ul class="divide-y divide-neutral-200">
        <li v-for="question in questions.data" :key="question.id" class="py-4">
          <Link :href="`${base}/${question.id}`" class="block break-words font-semibold text-primary-900 hover:underline">{{ question.title }}</Link>
          <p class="mt-1 text-sm text-neutral-600">{{ question.user?.name }} {{ question.status }} &middot; {{ question.messages_count }} messages &middot; {{ question.created_at }}</p>
        </li>
      </ul>
      <nav aria-label="Pagination" class="flex flex-wrap gap-2">
        <template v-for="link in questions.links" :key="link.label">
          <Link v-if="link.url" :href="link.url" :aria-current="link.active ? 'page' : undefined" class="px-3 py-2 text-sm" :class="{ 'font-bold underline': link.active }">{{ link.label.replace(/&laquo;/g, '').replace(/&raquo;/g, '') }}</Link>
        </template>
      </nav>
    </div>
  </AppShell>
</template>

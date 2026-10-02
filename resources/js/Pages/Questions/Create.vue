<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Send } from 'lucide-vue-next';
import AppShell from '@/Layouts/AppShell.vue';
const props = defineProps({ courses: Array, context_type: String, context_id: [Number, String] });
const form = useForm({ title: '', body: '', context_type: props.context_type || null, context_id: props.context_id || null, priority: 'normal' });
</script>
<template>
  <AppShell>
    <Head title="Ask a Question" />
    <div class="max-w-2xl space-y-6">
      <Link href="/questions" class="text-primary-700">Back to questions</Link>
      <h1 class="text-2xl font-semibold">Ask a Question</h1>
      <form @submit.prevent="form.post('/questions')" class="space-y-5">
        <div><label for="question-title" class="block mb-2">Subject</label><input id="question-title" v-model="form.title" required maxlength="255" class="w-full rounded-lg border-neutral-300" /></div>
        <div><label for="question-body" class="block mb-2">Question</label><textarea id="question-body" v-model="form.body" required minlength="10" rows="7" class="w-full rounded-lg border-neutral-300" /></div>
        <div><label for="question-priority" class="block mb-2">Priority</label><select id="question-priority" v-model="form.priority" class="rounded-lg border-neutral-300"><option>low</option><option>normal</option><option>high</option></select></div>
        <p v-for="(error, field) in form.errors" :key="field" role="alert" class="text-sm text-red-700">{{ error }}</p>
        <button :disabled="form.processing" class="inline-flex items-center gap-2 rounded-lg bg-primary-900 px-4 py-2 text-white disabled:opacity-50"><Send class="h-4 w-4" /> Submit question</button>
      </form>
    </div>
  </AppShell>
</template>

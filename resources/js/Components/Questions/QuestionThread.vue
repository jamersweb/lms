<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Send, Check } from 'lucide-vue-next';
import AppShell from '@/Layouts/AppShell.vue';
const props = defineProps({ question: Object, messages: Array, admin: Boolean, admins: { type: Array, default: () => [] } });
const base = props.admin ? '/admin/questions' : '/questions';
const reply = useForm({ message: '', is_internal: false, audio_url: '', audio_file: null });
const settings = useForm({ status: props.question.status, priority: props.question.priority, assigned_to: props.question.assignee?.id || null });
const resolve = useForm({});
function send() { reply.post(`${base}/${props.question.id}/message`, { onSuccess: () => reply.reset(), preserveScroll: true }); }
</script>
<template>
  <AppShell>
    <Head :title="question.title" />
    <div class="max-w-3xl space-y-6">
      <Link :href="base" class="text-primary-700">Back to questions</Link>
      <h1 class="break-words text-2xl font-semibold">{{ question.title }}</h1>
      <p class="text-sm text-neutral-600"><template v-if="question.user?.name">{{ question.user.name }} &middot; </template>{{ question.status }} &middot; {{ question.priority }}</p>
      <form v-if="admin" @submit.prevent="settings.patch(`${base}/${question.id}`)" class="flex flex-wrap items-end gap-3 border-y py-4">
        <label class="text-sm">Status<select v-model="settings.status" class="block rounded-lg border-neutral-300"><option>open</option><option>answered</option><option>resolved</option></select></label>
        <label class="text-sm">Priority<select v-model="settings.priority" class="block rounded-lg border-neutral-300"><option>low</option><option>normal</option><option>high</option></select></label>
        <label class="text-sm">Assigned to<select v-model="settings.assigned_to" class="block max-w-full rounded-lg border-neutral-300"><option :value="null">Unassigned</option><option v-for="person in admins" :key="person.id" :value="person.id">{{ person.name }}</option></select></label>
        <button :disabled="settings.processing" class="rounded-lg border px-4 py-2">Save</button>
        <p v-for="error in settings.errors" :key="error" role="alert" class="w-full text-red-700">{{ error }}</p>
      </form>
      <div v-if="!messages.length" class="whitespace-pre-wrap break-words">{{ question.body }}</div>
      <ol class="divide-y divide-neutral-200">
        <li v-for="message in messages" :key="message.id" class="py-5">
          <p class="text-sm font-semibold">{{ message.sender.name }} <span class="font-normal text-neutral-500">&middot; {{ message.created_at }} {{ message.is_internal ? '(Internal note)' : '' }}</span></p>
          <p class="mt-2 whitespace-pre-wrap break-words">{{ message.message }}</p>
          <audio v-if="message.audio_playable_url" :src="message.audio_playable_url" controls class="mt-3 w-full max-w-md" />
        </li>
      </ol>
      <form v-if="admin || question.status !== 'resolved'" @submit.prevent="send" class="space-y-3">
        <label for="reply" class="block font-medium">Reply</label>
        <textarea id="reply" v-model="reply.message" required minlength="5" rows="4" class="w-full rounded-lg border-neutral-300" />
        <template v-if="admin">
          <label class="flex items-center gap-2"><input v-model="reply.is_internal" type="checkbox" /> Internal note</label>
          <label class="block text-sm">Audio attachment<input type="file" accept="audio/*" class="block max-w-full mt-1" @change="reply.audio_file = $event.target.files[0]" /></label>
          <label class="block text-sm">Audio URL<input v-model="reply.audio_url" type="url" class="mt-1 w-full rounded-lg border-neutral-300" /></label>
        </template>
        <p v-for="error in reply.errors" :key="error" role="alert" class="text-sm text-red-700">{{ error }}</p>
        <button :disabled="reply.processing" class="inline-flex items-center gap-2 rounded-lg bg-primary-900 px-4 py-2 text-white disabled:opacity-50"><Send class="h-4 w-4" /> Send reply</button>
      </form>
      <button v-if="!admin && question.status !== 'resolved'" :disabled="resolve.processing" @click="resolve.patch(`/questions/${question.id}/resolve`)" class="inline-flex items-center gap-2 rounded-lg border px-4 py-2"><Check class="h-4 w-4" /> Mark resolved</button>
    </div>
  </AppShell>
</template>

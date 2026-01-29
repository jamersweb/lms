<template>
  <AppShell>
    <div class="max-w-3xl mx-auto">
      <div class="mb-6 flex items-center justify-between">
        <div>
          <button
            type="button"
            class="text-xs text-neutral-500 hover:text-neutral-800 mb-1"
            @click="$inertia.visit(route('ask.index'))"
          >
            ← Back to my questions
          </button>
          <h1 class="font-serif text-2xl font-bold text-neutral-900">
            {{ thread.subject }}
          </h1>
          <p class="text-xs text-neutral-500">
            Opened {{ thread.created_at }}
          </p>
        </div>
        <span
          class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium"
          :class="thread.status === 'open' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-neutral-100 text-neutral-600 border border-neutral-200'"
        >
          {{ thread.status === 'open' ? 'Open' : 'Closed' }}
        </span>
      </div>

      <div class="space-y-4 mb-6">
        <div
          v-for="message in messages"
          :key="message.id"
          class="flex gap-3"
        >
          <div class="mt-1">
            <div
              class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold"
              :class="message.sender_type === 'mentor' ? 'bg-primary-900 text-white' : 'bg-neutral-200 text-neutral-700'"
            >
              {{ initials(message.user.name) }}
            </div>
          </div>
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-1">
              <span class="text-sm font-medium text-neutral-900">{{ message.user.name }}</span>
              <span
                v-if="message.sender_type === 'mentor'"
                class="text-[10px] uppercase tracking-wide bg-primary-50 text-primary-700 border border-primary-100 rounded-full px-2 py-0.5"
              >
                Mentor
              </span>
              <span class="text-xs text-neutral-500">
                {{ message.created_at }}
              </span>
            </div>
            <div class="rounded-lg bg-white border border-neutral-200 px-4 py-3 text-sm text-neutral-800">
              {{ message.body }}
            </div>
          </div>
        </div>
      </div>

      <div v-if="thread.status === 'open'" class="bg-white border border-neutral-200 rounded-xl p-4">
        <h2 class="text-sm font-medium text-neutral-900 mb-2">Reply</h2>
        <form @submit.prevent="submit">
          <textarea
            v-model="form.body"
            rows="4"
            class="w-full rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
          ></textarea>
          <p v-if="form.errors.body" class="mt-1 text-xs text-red-600">
            {{ form.errors.body }}
          </p>
          <div class="mt-3 flex justify-end">
            <button
              type="submit"
              class="inline-flex items-center px-4 py-2 rounded-md bg-primary-900 text-white text-sm font-medium hover:bg-primary-800 disabled:opacity-50"
              :disabled="form.processing"
            >
              Send reply
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
  thread: Object,
  messages: Array,
});

const form = useForm({
  body: '',
});

const submit = () => {
  form.post(route('ask.reply', props.thread.id), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('body');
    },
  });
};

const initials = (name) => {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((n) => n[0]?.toUpperCase())
    .join('');
};
</script>


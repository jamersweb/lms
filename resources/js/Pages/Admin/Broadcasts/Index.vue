<template>
  <AppShell>
    <div class="max-w-4xl mx-auto">
      <div class="mb-6">
        <h1 class="font-serif text-2xl font-bold text-neutral-900">Broadcasts</h1>
        <p class="text-sm text-neutral-600">
          Send segmented email messages to students based on gender, bayah status, and level.
        </p>
      </div>

      <form
        class="mb-8 bg-white border border-neutral-200 rounded-xl p-4 shadow-sm space-y-3"
        @submit.prevent="submit"
      >
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div>
            <label class="block text-xs font-semibold text-neutral-700 mb-1">Gender</label>
            <select
              v-model="form.gender"
              class="w-full rounded-md border border-neutral-300 text-sm"
            >
              <option :value="null">All</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-neutral-700 mb-1">Has Bayah</label>
            <select
              v-model="form.has_bayah"
              class="w-full rounded-md border border-neutral-300 text-sm"
            >
              <option :value="null">All</option>
              <option :value="true">Yes</option>
              <option :value="false">No</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-neutral-700 mb-1">Level</label>
            <select
              v-model="form.level"
              class="w-full rounded-md border border-neutral-300 text-sm"
            >
              <option :value="null">All</option>
              <option value="beginner">Beginner</option>
              <option value="intermediate">Intermediate</option>
              <option value="expert">Expert</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-neutral-700 mb-1">Subject</label>
          <input
            v-model="form.subject"
            type="text"
            class="w-full rounded-md border border-neutral-300 text-sm"
          />
          <p v-if="form.errors.subject" class="text-xs text-red-600 mt-1">
            {{ form.errors.subject }}
          </p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-neutral-700 mb-1">Body</label>
          <textarea
            v-model="form.body"
            rows="4"
            class="w-full rounded-md border border-neutral-300 text-sm"
          ></textarea>
          <p v-if="form.errors.body" class="text-xs text-red-600 mt-1">
            {{ form.errors.body }}
          </p>
        </div>

        <div class="flex justify-end">
          <button
            type="submit"
            class="inline-flex items-center px-4 py-2 rounded-md bg-primary-900 text-white text-sm font-medium hover:bg-primary-800 disabled:opacity-50"
            :disabled="form.processing"
          >
            Send broadcast
          </button>
        </div>
      </form>

      <div>
        <h2 class="text-sm font-semibold text-neutral-900 mb-2">Recent broadcasts</h2>
        <div v-if="broadcasts.length === 0" class="text-sm text-neutral-500">
          No broadcasts sent yet.
        </div>
        <div v-else class="space-y-3">
          <div
            v-for="b in broadcasts"
            :key="b.id"
            class="bg-white border border-neutral-200 rounded-lg p-3 text-sm"
          >
            <div class="flex items-center justify-between mb-1">
              <div class="font-semibold text-neutral-900">
                {{ b.subject }}
              </div>
              <div class="text-xs text-neutral-500">
                {{ b.sent_at || 'Pending' }}
              </div>
            </div>
            <div class="text-xs text-neutral-500 mb-1">
              Audience:
              <span v-if="b.audience.gender">gender={{ b.audience.gender }} </span>
              <span v-if="b.audience.has_bayah !== null">
                bayah={{ b.audience.has_bayah ? 'yes' : 'no' }}
              </span>
              <span v-if="b.audience.level"> level={{ b.audience.level }}</span>
            </div>
            <p class="text-neutral-700 whitespace-pre-line">
              {{ b.body }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  broadcasts: Array,
});

const form = useForm({
  gender: null,
  has_bayah: null,
  level: null,
  subject: '',
  body: '',
});

const submit = () => {
  form.post(route('admin.broadcasts.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('subject', 'body');
    },
  });
};
</script>


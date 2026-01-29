<template>
  <AppShell>
    <div class="max-w-3xl mx-auto">
      <div class="mb-8">
        <h1 class="font-serif text-3xl font-bold text-neutral-900 mb-2">Community Dua Wall</h1>
        <p class="text-sm text-neutral-600">
          Share a short dua request. Others can silently click “Prayed for you”.
        </p>
      </div>

      <!-- Create form -->
      <form
        class="mb-8 bg-white border border-neutral-200 rounded-xl p-4 shadow-sm space-y-3"
        @submit.prevent="submit"
      >
        <label class="block text-sm font-medium text-neutral-800 mb-1">
          Your request
        </label>
        <textarea
          v-model="form.request_text"
          rows="3"
          class="w-full rounded-lg border border-neutral-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
          placeholder="Please make dua for..."
        ></textarea>
        <p v-if="form.errors.request_text" class="text-xs text-red-600">
          {{ form.errors.request_text }}
        </p>

        <label class="inline-flex items-center gap-2 text-xs text-neutral-600">
          <input
            type="checkbox"
            v-model="form.is_anonymous"
            class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
          />
          Post anonymously
        </label>

        <div class="flex justify-end">
          <button
            type="submit"
            class="inline-flex items-center px-4 py-2 rounded-md bg-primary-900 text-white text-sm font-medium hover:bg-primary-800 disabled:opacity-50"
            :disabled="form.processing"
          >
            Post request
          </button>
        </div>
      </form>

      <!-- List -->
      <div class="space-y-4">
        <div
          v-for="dua in requests.data"
          :key="dua.id"
          class="bg-white border border-neutral-200 rounded-xl p-4 shadow-sm"
        >
          <div class="flex items-center justify-between mb-2">
            <div class="text-xs text-neutral-500">
              <span v-if="dua.author">
                From <span class="font-medium text-neutral-800">{{ dua.author.name }}</span>
              </span>
              <span v-else>Anonymous</span>
              <span v-if="dua.created_at"> • {{ dua.created_at }}</span>
            </div>
          </div>
          <p class="text-sm text-neutral-800 font-serif whitespace-pre-line mb-3">
            {{ dua.request_text }}
          </p>
          <div class="flex items-center justify-between text-xs text-neutral-500">
            <div>
              <span class="font-semibold text-emerald-700">{{ dua.prayers_count }}</span>
              <span class="ml-1">prayed</span>
            </div>
            <button
              type="button"
              class="inline-flex items-center px-3 py-1 rounded-full border text-xs font-medium transition-colors"
              :class="dua.has_prayed
                ? 'border-emerald-300 bg-emerald-50 text-emerald-700 cursor-default'
                : 'border-primary-200 bg-primary-50 text-primary-700 hover:bg-primary-100'"
              :disabled="dua.has_prayed || prayingId === dua.id"
              @click="pray(dua)"
            >
              <span v-if="dua.has_prayed">You prayed</span>
              <span v-else>Prayed for you</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { useForm, router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  requests: Object,
});

const form = useForm({
  request_text: '',
  is_anonymous: false,
});

const prayingId = ref(null);

const submit = () => {
  form.post(route('dua.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('request_text');
    },
  });
};

const pray = async (dua) => {
  prayingId.value = dua.id;
  try {
    await axios.post(route('dua.pray', { dua: dua.id }));
    router.reload({ only: ['requests'] });
  } catch {
    // ignore
  } finally {
    prayingId.value = null;
  }
};
</script>


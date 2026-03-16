<template>
  <AppShell>
    <div class="max-w-2xl mx-auto space-y-6">
      <h1 class="font-serif text-2xl font-bold text-neutral-900">Trigger Settings</h1>

      <div v-if="$page.props.flash?.success" class="p-4 bg-green-50 text-green-800 rounded-lg">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash?.error" class="p-4 bg-red-50 text-red-800 rounded-lg">
        {{ $page.props.flash.error }}
      </div>

      <div class="bg-white rounded-xl border border-neutral-200 p-6">
        <h2 class="text-lg font-semibold text-neutral-800 mb-2">How It Works</h2>
        <p class="text-sm text-neutral-600">LMS only records trigger events. It does not send WhatsApp itself. OpenClaw reads new records from the API, sends the message, then marks the record as sent or failed.</p>
      </div>

      <!-- Test Send Form -->
      <div class="bg-white rounded-xl border border-neutral-200 p-6">
        <h2 class="text-lg font-semibold text-neutral-800 mb-4">Queue Test Trigger</h2>
        <p class="text-sm text-neutral-600 mb-4">This creates a new trigger record in the outbox. It will appear on the trigger queue page and can be picked up by OpenClaw.</p>

        <form @submit.prevent="sendForm.post(route('admin.whatsapp-settings.send-test'))" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Template</label>
            <select
              v-model="sendForm.template_key"
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              required
            >
              <option value="">Select template</option>
              <option v-for="t in templates" :key="t.key" :value="t.key">
                {{ t.template_name }} ({{ t.key }})
              </option>
            </select>
            <p v-if="sendForm.template_key" class="mt-2 text-xs text-neutral-500">
              Default: {{ templates.find(x => x.key === sendForm.template_key)?.message?.slice(0, 80) }}...
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">User (WhatsApp)</label>
            <select
              v-model="sendForm.user_id"
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              required
            >
              <option value="">Select user</option>
              <option v-for="u in users" :key="u.id" :value="u.id" :disabled="!u.has_whatsapp">
                {{ u.name }} {{ u.whatsapp_number ? `(${u.whatsapp_number})` : '(no WhatsApp)' }}
              </option>
            </select>
            <p v-if="users.length === 0" class="mt-1 text-sm text-amber-600">No users with WhatsApp number. Add in Profile.</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-2">Message</label>
            <textarea
              v-model="sendForm.message"
              rows="4"
              class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              placeholder="Enter message to send..."
              required
            />
            <p v-if="sendForm.errors.message" class="mt-1 text-sm text-red-600">{{ sendForm.errors.message }}</p>
          </div>

          <button
            type="submit"
            :disabled="sendForm.processing || users.length === 0"
            class="px-6 py-2 bg-primary-900 text-white rounded-lg font-medium hover:bg-primary-800 disabled:opacity-50"
          >
            {{ sendForm.processing ? 'Queueing...' : 'Queue Test Trigger' }}
          </button>
        </form>
      </div>

      <!-- OpenClaw API -->
      <div class="bg-white rounded-xl border border-neutral-200 p-6">
        <h2 class="text-lg font-semibold text-neutral-800 mb-2">OpenClaw API</h2>
        <p class="text-sm text-neutral-600 mb-4">Use this API from your OpenClaw worker to claim new triggers and report results.</p>
        <div class="space-y-2 text-sm text-neutral-700">
          <p><span class="font-semibold">Claim:</span> <code class="bg-neutral-100 px-1.5 py-0.5 rounded">{{ openclawBaseUrl }}/triggers/claim</code></p>
          <p><span class="font-semibold">Pending:</span> <code class="bg-neutral-100 px-1.5 py-0.5 rounded">{{ openclawBaseUrl }}/triggers/pending</code></p>
          <p><span class="font-semibold">Ack:</span> <code class="bg-neutral-100 px-1.5 py-0.5 rounded">{{ openclawBaseUrl }}/triggers/{id}/ack</code></p>
          <p><span class="font-semibold">Fail:</span> <code class="bg-neutral-100 px-1.5 py-0.5 rounded">{{ openclawBaseUrl }}/triggers/{id}/fail</code></p>
          <p class="text-xs text-neutral-500 pt-2">Auth: <code class="bg-neutral-100 px-1.5 py-0.5 rounded">Authorization: Bearer OPENCLAW_TRIGGER_API_TOKEN</code></p>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { useForm } from '@inertiajs/vue3';

defineProps({
  templates: Array,
  users: Array,
  openclawBaseUrl: String,
});

const sendForm = useForm({
  template_key: '',
  user_id: '',
  message: '',
});
</script>

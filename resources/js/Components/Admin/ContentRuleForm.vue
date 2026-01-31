<template>
  <div class="bg-white rounded-xl border border-neutral-200 p-6 space-y-6">
    <div>
      <h3 class="text-lg font-semibold text-neutral-900 mb-1">Access Rule</h3>
      <p class="text-sm text-neutral-500">Control who can access this content based on level, gender, and bay'ah status.</p>
    </div>

    <form @submit.prevent="saveRule" class="space-y-4">
      <!-- Minimum Level -->
      <div>
        <label class="block text-sm font-medium text-neutral-700 mb-2">Minimum Level</label>
        <select
          v-model="form.min_level"
          class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
        >
          <option :value="null">None (No level requirement)</option>
          <option value="beginner">Beginner</option>
          <option value="intermediate">Intermediate</option>
          <option value="expert">Expert</option>
        </select>
        <p v-if="form.errors.min_level" class="mt-1 text-sm text-red-600">{{ form.errors.min_level }}</p>
      </div>

      <!-- Gender -->
      <div>
        <label class="block text-sm font-medium text-neutral-700 mb-2">Gender Restriction</label>
        <select
          v-model="form.gender"
          class="w-full px-4 py-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400"
        >
          <option :value="null">None (All genders)</option>
          <option value="male">Male only</option>
          <option value="female">Female only</option>
        </select>
        <p v-if="form.errors.gender" class="mt-1 text-sm text-red-600">{{ form.errors.gender }}</p>
      </div>

      <!-- Requires Bay'ah -->
      <div>
        <label class="inline-flex items-center gap-2 cursor-pointer">
          <input
            v-model="form.requires_bayah"
            type="checkbox"
            class="h-4 w-4 text-primary-600 border-neutral-300 rounded focus:ring-primary-500"
          />
          <span class="text-sm font-medium text-neutral-700">Requires Bay'ah</span>
        </label>
        <p v-if="form.errors.requires_bayah" class="mt-1 text-sm text-red-600">{{ form.errors.requires_bayah }}</p>
      </div>

      <!-- Preview -->
      <div v-if="previewMessage" class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
        <p class="text-sm text-blue-800">
          <span class="font-medium">Preview:</span> {{ previewMessage }}
        </p>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-3 pt-4 border-t border-neutral-100">
        <button
          type="submit"
          :disabled="form.processing"
          class="px-4 py-2 bg-primary-900 text-white rounded-lg text-sm font-medium hover:bg-primary-800 transition-colors disabled:opacity-50 flex items-center gap-2"
        >
          <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
          {{ form.processing ? 'Saving...' : 'Save Rule' }}
        </button>

        <button
          v-if="hasExistingRule"
          type="button"
          @click="removeRule"
          :disabled="deleteForm.processing"
          class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors disabled:opacity-50 flex items-center gap-2"
        >
          <Loader2 v-if="deleteForm.processing" class="w-4 h-4 animate-spin" />
          {{ deleteForm.processing ? 'Removing...' : 'Remove Rule' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps({
  type: {
    type: String,
    required: true,
    validator: (value) => ['courses', 'modules', 'lessons'].includes(value),
  },
  entityId: {
    type: Number,
    required: true,
  },
  initialRule: {
    type: Object,
    default: null,
  },
});

const hasExistingRule = computed(() => props.initialRule !== null);

const form = useForm({
  min_level: props.initialRule?.min_level || null,
  gender: props.initialRule?.gender || null,
  requires_bayah: props.initialRule?.requires_bayah || false,
});

const deleteForm = useForm({});

// Generate preview message
const previewMessage = computed(() => {
  const parts = [];

  if (form.requires_bayah) {
    parts.push("Bay'ah required");
  }

  if (form.min_level) {
    const levelLabel = form.min_level.charAt(0).toUpperCase() + form.min_level.slice(1);
    parts.push(`Requires ${levelLabel} level`);
  }

  if (form.gender === 'male') {
    parts.push('Brothers only');
  } else if (form.gender === 'female') {
    parts.push('Sisters only');
  }

  return parts.length > 0 ? parts.join(' • ') : null;
});

function saveRule() {
  form.put(route('admin.content-rules.upsert', { type: props.type, id: props.entityId }), {
    preserveScroll: true,
    onSuccess: () => {
      // Form will be reset by Inertia after successful save
    },
  });
}

function removeRule() {
  if (!confirm('Are you sure you want to remove this access rule?')) {
    return;
  }

  deleteForm.delete(route('admin.content-rules.destroy', { type: props.type, id: props.entityId }), {
    preserveScroll: true,
  });
}

// Watch for changes to initialRule prop (when page reloads after save)
watch(() => props.initialRule, (newRule) => {
  if (newRule) {
    form.min_level = newRule.min_level || null;
    form.gender = newRule.gender || null;
    form.requires_bayah = newRule.requires_bayah || false;
  } else {
    form.reset();
  }
}, { deep: true });
</script>

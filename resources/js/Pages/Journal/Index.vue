<template>
  <AppShell>
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="font-serif text-3xl font-bold text-neutral-900">Spiritual Journal</h1>
        <p class="text-neutral-600 mt-1">Reflect on your day, gratitude, and progress.</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
       <!-- Editor Column -->
       <div class="lg:col-span-2 space-y-8">
          <div class="bg-white p-6 rounded-xl border border-neutral-200 shadow-sm">
             <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-lg text-neutral-900">Today's Reflection</h3>
                <span class="text-sm text-neutral-500">{{ new Date().toLocaleDateString() }}</span>
             </div>

             <form @submit.prevent="saveEntry">
                <!-- Mood Selection -->
                <div class="mb-6">
                   <label class="block text-sm font-medium text-neutral-700 mb-2">How was your spiritual state today?</label>
                   <div class="flex gap-4">
                      <button 
                        v-for="mood in moods" 
                        :key="mood.value"
                        type="button"
                        @click="form.mood = mood.value"
                        :class="[
                           'flex flex-col items-center p-3 rounded-lg border transition-all w-20',
                           form.mood === mood.value 
                              ? 'border-primary-500 bg-primary-50 text-primary-700' 
                              : 'border-neutral-200 hover:bg-neutral-50 text-neutral-600'
                        ]"
                      >
                         <span class="text-2xl mb-1">{{ mood.emoji }}</span>
                         <span class="text-xs">{{ mood.label }}</span>
                      </button>
                   </div>
                </div>

                <!-- Content Area -->
                <div class="mb-6">
                   <label class="block text-sm font-medium text-neutral-700 mb-2">Write your thoughts...</label>
                   <textarea 
                      v-model="form.content"
                      rows="8" 
                      class="w-full rounded-xl border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 p-4"
                      placeholder="What are you grateful for today? What did you struggle with?..."
                   ></textarea>
                </div>

                <div class="flex justify-end">
                   <Button :loading="form.processing" type="submit">
                      Save Entry
                   </Button>
                </div>
             </form>
          </div>
       </div>

       <!-- History Column -->
       <div class="space-y-6">
          <h3 class="font-bold text-lg text-neutral-900">Past Entries</h3>
          
          <div v-if="entries.data.length === 0" class="text-neutral-500 text-sm">
             No past entries.
          </div>

          <div v-else class="space-y-4">
             <div 
               v-for="entry in entries.data" 
               :key="entry.id" 
               class="bg-white p-5 rounded-xl border border-neutral-100 shadow-sm hover:shadow-md transition-shadow"
             >
                <div class="flex items-center justify-between mb-2">
                   <span class="text-xs font-semibold text-neutral-500 uppercase">{{ entry.entry_date }}</span>
                   <span class="text-lg" :title="entry.mood">{{ getMoodEmoji(entry.mood) }}</span>
                </div>
                <p class="text-neutral-700 text-sm line-clamp-3">
                   {{ entry.content }}
                </p>
                <button class="text-xs text-primary-600 font-medium mt-3 hover:text-primary-700">Read More</button>
             </div>
          </div>
       </div>
    </div>
  </AppShell>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Button from '@/Components/Common/Button.vue';

const props = defineProps({
    entries: Object,
    todayEntry: Object
});

const form = useForm({
    date: new Date().toISOString().slice(0, 10),
    mood: props.todayEntry?.mood || 'good',
    content: props.todayEntry?.content || '',
});

const moods = [
   { value: 'great', label: 'Great', emoji: '🌟' },
   { value: 'good', label: 'Good', emoji: '🙂' },
   { value: 'neutral', label: 'Neutral', emoji: '😐' },
   { value: 'low', label: 'Low', emoji: '😔' },
];

const getMoodEmoji = (value) => {
   return moods.find(m => m.value === value)?.emoji || '😐';
};

const saveEntry = () => {
    form.post(route('journal.store'), {
        preserveScroll: true,
    });
};
</script>

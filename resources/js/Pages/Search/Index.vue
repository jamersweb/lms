<template>
  <AppShell>
    <div class="max-w-3xl mx-auto">
       <div class="text-center mb-8">
          <h1 class="font-serif text-3xl font-bold text-neutral-900 mb-2">Transcript Search</h1>
          <p class="text-neutral-500">Find any topic across all lessons and courses.</p>
       </div>

       <!-- Search Box -->
       <div class="relative mb-8">
          <Search class="absolute left-4 top-1/2 -translate-y-1/2 text-neutral-400 w-5 h-5" />
          <input 
            v-model="form.q"
            @keydown.enter="submit"
            type="text" 
            placeholder="Search keywords (e.g. 'intention', 'sabr', 'heart')..." 
            class="w-full pl-12 pr-4 py-4 rounded-xl border border-neutral-200 shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-lg transition-shadow duration-200 hover:shadow-md"
          />
       </div>

       <!-- Results -->
       <div v-if="results.length > 0" class="space-y-4">
          <div
            v-for="result in results"
            :key="result.lesson_id"
            class="bg-white p-6 rounded-xl border border-neutral-100 shadow-sm hover:shadow-md transition-all duration-200"
          >
            <div class="flex items-center justify-between mb-1">
              <div class="text-xs font-semibold text-primary-600 uppercase tracking-wide">
                {{ result.course_title }}
              </div>
              <ArrowRight class="w-4 h-4 text-neutral-300" />
            </div>

            <h3 class="text-lg font-bold text-neutral-900 mb-3">
              {{ result.lesson_title }}
            </h3>

            <div class="space-y-2">
              <div
                v-for="(match, idx) in result.matches"
                :key="`${result.lesson_id}-${idx}-${match.start_seconds}`"
                class="flex items-start gap-3 border border-neutral-100 rounded-lg px-3 py-2 hover:border-primary-200 hover:bg-primary-50/40 transition-colors"
              >
                <div class="mt-0.5">
                  <Link
                    :href="matchHref(result, match)"
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-primary-100 text-primary-800 hover:bg-primary-200"
                  >
                    {{ formatTimestamp(match.start_seconds) }}
                  </Link>
                </div>
                <div class="flex-1 text-sm text-neutral-700 font-serif leading-relaxed">
                  <Link :href="matchHref(result, match)">
                    {{ match.snippet_text }}
                  </Link>
                </div>
              </div>
            </div>
          </div>
       </div>
       
       <div v-else class="text-center py-12 text-neutral-500">
          <p v-if="query">No results found for "{{ query }}".</p>
          <p v-else>No results. Try searching for a topic above.</p>
       </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import { Search, ArrowRight } from 'lucide-vue-next';
import { useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    query: String,
    results: Array
});

const form = useForm({
    q: props.query || ''
});

const submit = () => {
    form.get(route('search'));
};

const formatTimestamp = (seconds) => {
  const s = Math.max(0, Number(seconds || 0));
  const mins = Math.floor(s / 60);
  const secs = Math.floor(s % 60);
  return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const matchHref = (result, match) => {
  const base = route('lessons.show', {
    course: result.course_id,
    lesson: result.lesson_id,
  });

  const params = new URLSearchParams();
  params.set('t', Math.max(0, Number(match.start_seconds || 0)));
  if (props.query) {
    params.set('q', props.query);
  }

  return `${base}?${params.toString()}`;
};
</script>

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
          <div v-for="result in results" :key="result.id" class="bg-white p-6 rounded-xl border border-neutral-100 shadow-sm hover:shadow-md transition-all duration-200 group">
             <Link :href="result.url" class="block">
                <div class="flex items-center justify-between mb-2">
                   <div class="text-xs font-semibold text-primary-600 uppercase tracking-wide">
                      {{ result.course }} &bull; {{ result.timestamp }}
                   </div>
                   <ArrowRight class="w-4 h-4 text-neutral-300 group-hover:text-primary-500 transition-colors" />
                </div>
                
                <h3 class="text-lg font-bold text-neutral-900 mb-2 group-hover:text-primary-700 transition-colors">
                   {{ result.title }}
                </h3>
                
                <p class="text-neutral-600 font-serif leading-relaxed" v-html="result.snippet"></p>
             </Link>
          </div>
       </div>
       
       <div v-else-if="query" class="text-center py-12 text-neutral-500">
          <p>No results found for "{{ query }}".</p>
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
</script>

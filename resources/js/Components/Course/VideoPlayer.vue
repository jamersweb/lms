<template>
  <div class="relative w-full aspect-video bg-gradient-to-br from-primary-800 to-primary-950 rounded-xl overflow-hidden shadow-lg group">
    <!-- YouTube Embed -->
    <iframe 
      v-if="isYouTube && src"
      :src="src" 
      class="w-full h-full" 
      frameborder="0" 
      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
      allowfullscreen
    ></iframe>

    <!-- External Video (Tazkiyah Tarbiyah) - Beautiful Play Card -->
    <a 
      v-else-if="isExternal && src"
      :href="src" 
      target="_blank" 
      rel="noopener noreferrer"
      class="absolute inset-0 flex flex-col items-center justify-center text-white cursor-pointer group/play"
    >
      <!-- Decorative Background Pattern -->
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-0 w-32 h-32 bg-white/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-0 w-40 h-40 bg-secondary-500/30 rounded-full blur-3xl"></div>
      </div>
      
      <!-- Play Button -->
      <div class="relative z-10 flex flex-col items-center">
        <div class="h-20 w-20 md:h-24 md:w-24 rounded-full bg-white/10 backdrop-blur-sm flex items-center justify-center mb-6 group-hover/play:bg-white/20 transition-all duration-300 group-hover/play:scale-110 ring-4 ring-white/20 group-hover/play:ring-white/40">
          <Play class="w-10 h-10 md:w-12 md:h-12 text-white ml-1" fill="currentColor" />
        </div>
        
        <span class="text-lg md:text-xl font-serif font-bold mb-2 text-center px-4">Click to Watch Video</span>
        <span class="text-sm text-white/70 flex items-center gap-2">
          <ExternalLink class="w-4 h-4" />
          Opens on Tazkiyah Tarbiyah
        </span>
      </div>
      
      <!-- Bottom Gradient -->
      <div class="absolute bottom-0 left-0 right-0 h-20 bg-gradient-to-t from-black/50 to-transparent"></div>
    </a>
    
    <!-- Empty State -->
    <div v-else class="absolute inset-0 flex flex-col items-center justify-center text-white/50">
      <VideoOff class="w-12 h-12 mb-3 opacity-50" />
      <span class="text-sm">Video Source Not Available</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Play, ExternalLink, VideoOff } from 'lucide-vue-next';

const props = defineProps({
  src: String,
  provider: {
    type: String,
    default: 'youtube'
  }
});

const isYouTube = computed(() => {
  return props.provider === 'youtube' || 
         (props.src && props.src.includes('youtube.com'));
});

const isExternal = computed(() => {
  return props.provider === 'external' || 
         (props.src && props.src.includes('tazkiyahtarbiyah.com'));
});
</script>

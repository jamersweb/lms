<template>
  <AppShell>
    <div class="max-w-3xl mx-auto py-10 space-y-6">
      <h1 class="text-2xl font-bold text-neutral-900 mb-4">YouTube Player Dev Test</h1>

      <div class="aspect-video rounded-xl overflow-hidden border border-neutral-200 bg-black">
        <YouTubePlayer
          video-id="dQw4w9WgXcQ"
          :start-seconds="start"
          @ready="onReady"
          @heartbeat="onHeartbeat"
          @ended="onEnded"
          @stateChange="onStateChange"
        />
      </div>

      <div class="text-sm text-neutral-700">
        <p>Open the console to see <code>ready</code>, <code>heartbeat</code>, <code>stateChange</code>, and <code>ended</code> events.</p>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import YouTubePlayer from '@/Components/YouTubePlayer.vue';
import { ref } from 'vue';

const start = ref(0);

const onReady = (payload) => {
  console.log('[Dev] ready', payload);
};

const onHeartbeat = (payload) => {
  console.log('[Dev] heartbeat', payload);
};

const onEnded = () => {
  console.log('[Dev] ended');
};

const onStateChange = (payload) => {
  console.log('[Dev] stateChange', payload);
};
</script>


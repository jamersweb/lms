<template>
  <div class="relative w-full h-full">
    <div ref="playerEl" class="w-full h-full"></div>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
  videoId: {
    type: String,
    required: true,
  },
  startSeconds: {
    type: Number,
    default: 0,
  },
});

const emit = defineEmits(['ready', 'heartbeat', 'ended', 'stateChange']);

const playerEl = ref(null);
let player = null;
let heartbeatTimer = null;
let antiCheatTimer = null;
let lastAllowedTime = 0;

let youTubeApiPromise = null;

function loadYouTubeApi() {
  if (window.YT && window.YT.Player) {
    return Promise.resolve();
  }

  if (youTubeApiPromise) {
    return youTubeApiPromise;
  }

  youTubeApiPromise = new Promise((resolve) => {
    const existingScript = document.querySelector('script[src="https://www.youtube.com/iframe_api"]');
    if (!existingScript) {
      const tag = document.createElement('script');
      tag.src = 'https://www.youtube.com/iframe_api';
      document.head.appendChild(tag);
    }

    const previous = window.onYouTubeIframeAPIReady;
    window.onYouTubeIframeAPIReady = () => {
      if (typeof previous === 'function') {
        previous();
      }
      resolve();
    };
  });

  return youTubeApiPromise;
}

function startHeartbeat() {
  stopHeartbeat();
  heartbeatTimer = window.setInterval(() => {
    if (!player) return;
    const currentTime = player.getCurrentTime ? player.getCurrentTime() : 0;
    const playbackRate = player.getPlaybackRate ? player.getPlaybackRate() : 1;
    emit('heartbeat', { currentTime, playbackRate });
  }, 5000);
}

function stopHeartbeat() {
  if (heartbeatTimer) {
    clearInterval(heartbeatTimer);
    heartbeatTimer = null;
  }
  if (antiCheatTimer) {
    clearInterval(antiCheatTimer);
    antiCheatTimer = null;
  }
}

function createPlayer() {
  if (!playerEl.value || !window.YT || !window.YT.Player) {
    return;
  }

  player = new window.YT.Player(playerEl.value, {
    videoId: props.videoId,
    playerVars: {
      controls: 0,
      disablekb: 1,
      rel: 0,
      playsinline: 1,
      modestbranding: 1,
      iv_load_policy: 3,
      hl: 'en',
      origin: window.location.origin,
      start: props.startSeconds || 0,
    },
    events: {
      onReady: (event) => {
        lastAllowedTime = props.startSeconds || 0;
        const duration = event.target.getDuration ? event.target.getDuration() : 0;
        emit('ready', { duration });
      },
      onStateChange: (event) => {
        const state = event.data;
        emit('stateChange', { state });

        if (state === window.YT.PlayerState.PLAYING) {
          startHeartbeat();

          // Start anti-seek timer
          if (!antiCheatTimer) {
            antiCheatTimer = window.setInterval(() => {
              if (!player || !player.getCurrentTime) return;
              const currentTime = player.getCurrentTime() || 0;

              // Allow backward seeks (reset baseline)
              if (currentTime < lastAllowedTime - 0.5) {
                lastAllowedTime = currentTime;
                return;
              }

              // Block forward jumps greater than threshold
              if (currentTime > lastAllowedTime + 1.25) {
                player.seekTo(lastAllowedTime, true);
              } else {
                lastAllowedTime = Math.max(lastAllowedTime, currentTime);
              }
            }, 500);
          }
        } else {
          stopHeartbeat();
        }

        if (state === window.YT.PlayerState.ENDED) {
          emit('ended');
        }
      },
      onPlaybackRateChange: (event) => {
        let playbackRate = event.data;

        // Cap rate at 1.5x
        if (playbackRate > 1.5 && player && player.setPlaybackRate) {
          player.setPlaybackRate(1.5);
          playbackRate = 1.5;
        }

        const currentTime = player && player.getCurrentTime ? player.getCurrentTime() : 0;
        // Report playbackRate changes via heartbeat payload
        emit('heartbeat', { currentTime, playbackRate });
      },
    },
  });
}

onMounted(async () => {
  await loadYouTubeApi();
  createPlayer();
});

onBeforeUnmount(() => {
  stopHeartbeat();
  if (player && player.destroy) {
    player.destroy();
    player = null;
  }
});

watch(
  () => props.startSeconds,
  (value) => {
    if (player && typeof value === 'number' && !Number.isNaN(value)) {
      player.seekTo(value, true);
    }
  }
);
</script>


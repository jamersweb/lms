<template>
  <div class="relative w-full h-full custom-youtube-player" ref="wrapperEl" @mouseenter="showControlsTemporary" @mouseleave="hideControls" @mousemove="handleMouseMove" @click="handleVideoClick">
    <!-- YouTube IFrame API Player (hidden, only for playback) -->
    <div ref="playerEl" class="youtube-api-container" style="pointer-events: none;"></div>

    <!-- Custom UI Layer (covers YouTube UI completely) -->
    <div class="custom-ui-layer" @mouseenter="showControlsTemporary" @mouseleave="hideControls" @mousemove="handleMouseMove" @click="handleVideoClick">
      <!-- Invisible blocker to prevent YouTube hover (always present) -->
      <div class="youtube-hover-blocker" @click="handleVideoClick"></div>

      <!-- Custom Controls Overlay -->
      <div class="custom-controls" v-if="showControls">
        <!-- Top Bar -->
        <div class="custom-controls-top">
          <div class="custom-title">{{ title || 'Video Player' }}</div>
        </div>

        <!-- Center Play Button -->
        <div class="custom-controls-center" v-if="!isPlaying" @click.stop>
          <button @click.stop="play" class="custom-play-button" aria-label="Play">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="white">
              <path d="M8 5v14l11-7z"/>
            </svg>
          </button>
        </div>

        <!-- Bottom Controls -->
        <div class="custom-controls-bottom">
          <div class="custom-progress-container-full" @click.stop>
            <div class="custom-progress-bar" @click.stop="seekTo">
              <div class="custom-progress-filled" :style="{ width: progressPercent + '%' }"></div>
              <div class="custom-progress-handle" :style="{ left: progressPercent + '%' }"></div>
            </div>
          </div>

          <div class="custom-controls-row" @click.stop>
            <button @click.stop="togglePlay" class="custom-control-btn" aria-label="Play/Pause">
              <svg v-if="!isPlaying" width="24" height="24" viewBox="0 0 24 24" fill="white">
                <path d="M8 5v14l11-7z"/>
              </svg>
              <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="white">
                <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
              </svg>
            </button>

            <div class="custom-time">
              {{ formatTime(currentTime) }} / {{ formatTime(duration) }}
            </div>

            <div class="flex-1"></div>

            <button @click="toggleMute" class="custom-control-btn" aria-label="Mute/Unmute">
              <svg v-if="!isMuted" width="24" height="24" viewBox="0 0 24 24" fill="white">
                <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
              </svg>
              <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="white">
                <path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/>
              </svg>
            </button>

            <div class="custom-volume-container">
              <input
                type="range"
                min="0"
                max="100"
                :value="volume"
                @input="setVolume"
                class="custom-volume-slider"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Thumbnail Overlay (shows until video starts playing) -->
      <div v-if="!hasStartedPlaying" class="custom-thumbnail-overlay">
        <img :src="thumbnailUrl" alt="Video thumbnail" class="custom-thumbnail-image" @error="handleThumbnailError" />
        <div class="custom-thumbnail-gradient"></div>
        <!-- Play button on thumbnail -->
        <div class="custom-thumbnail-play" @click.stop>
          <button @click.stop="play" class="custom-play-button-large" aria-label="Play">
            <svg width="100" height="100" viewBox="0 0 24 24" fill="white">
              <path d="M8 5v14l11-7z"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Loading Indicator -->
      <div v-if="isLoading" class="custom-loading">
        <div class="custom-spinner"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, watch, computed } from 'vue';

const props = defineProps({
  videoId: {
    type: String,
    required: true,
  },
  startSeconds: {
    type: Number,
    default: 0,
  },
  title: {
    type: String,
    default: '',
  },
});

// YouTube thumbnail URL
const thumbnailUrl = computed(() => {
  return `https://img.youtube.com/vi/${props.videoId}/maxresdefault.jpg`;
});

const emit = defineEmits(['ready', 'heartbeat', 'ended', 'stateChange']);

const playerEl = ref(null);
const wrapperEl = ref(null);
let player = null;
let heartbeatTimer = null;
let antiCheatTimer = null;
let lastAllowedTime = 0;
let controlsTimeout = null;

// Function to scale iframe to fill container (no black bars)
const scaleIframeToFill = () => {
  if (playerEl.value && wrapperEl.value) {
    const iframe = playerEl.value.querySelector('iframe');
    if (iframe) {
      const container = wrapperEl.value;
      const containerWidth = container.offsetWidth;
      const containerHeight = container.offsetHeight;

      if (containerWidth > 0 && containerHeight > 0) {
        const containerAspect = containerWidth / containerHeight;
        const videoAspect = 16 / 9; // YouTube standard aspect ratio

        // Calculate scale to fill container (cover approach - no black bars)
        // If container is wider than video, scale based on height
        // If container is taller than video, scale based on width
        let scale = 1;
        if (containerAspect > videoAspect) {
          // Container is wider - scale to fill height
          scale = containerHeight / (containerWidth / videoAspect);
        } else {
          // Container is taller - scale to fill width
          scale = containerWidth / (containerHeight * videoAspect);
        }

        // Add small buffer to ensure no black bars
        scale = scale * 1.02;

        // Apply scaling
        iframe.style.pointerEvents = 'none';
        iframe.style.position = 'absolute';
        iframe.style.top = '50%';
        iframe.style.left = '50%';
        iframe.style.width = `${containerWidth * scale}px`;
        iframe.style.height = `${containerHeight * scale}px`;
        iframe.style.transform = 'translate(-50%, -50%)';
        iframe.style.maxWidth = 'none';
        iframe.style.maxHeight = 'none';
      }
    }
  }
};

// Player state
const isPlaying = ref(false);
const isLoading = ref(true);
const currentTime = ref(0);
const duration = ref(0);
const volume = ref(100);
const isMuted = ref(false);
const showControls = ref(true);
const hasStartedPlaying = ref(false);
const thumbnailError = ref(false);

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

const progressPercent = computed(() => {
  if (duration.value === 0) return 0;
  return (currentTime.value / duration.value) * 100;
});

function formatTime(seconds) {
  if (!seconds || isNaN(seconds)) return '0:00';
  const mins = Math.floor(seconds / 60);
  const secs = Math.floor(seconds % 60);
  return `${mins}:${secs.toString().padStart(2, '0')}`;
}

function startHeartbeat() {
  stopHeartbeat();
  heartbeatTimer = window.setInterval(() => {
    if (!player) return;
    const time = player.getCurrentTime ? player.getCurrentTime() : 0;
    const playbackRate = player.getPlaybackRate ? player.getPlaybackRate() : 1;
    currentTime.value = time;
    emit('heartbeat', { currentTime: time, playbackRate });
  }, 1000);
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
    width: '100%',
    height: '100%',
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
      fs: 0,
      cc_load_policy: 0,
      cc_lang_pref: 'en',
      autoplay: 0,
      mute: 0,
      enablejsapi: 1,
      // Additional parameters to minimize UI
      showinfo: 0,
      autohide: 1,
      // Ensure video fills player
      wmode: 'transparent',
    },
    events: {
      onReady: (event) => {
        lastAllowedTime = props.startSeconds || 0;
        duration.value = event.target.getDuration ? event.target.getDuration() : 0;
        volume.value = event.target.getVolume ? event.target.getVolume() : 100;
        isMuted.value = event.target.isMuted ? event.target.isMuted() : false;
        isLoading.value = false;
        emit('ready', { duration: duration.value });
        startHeartbeat();

        // Scale iframe after player is ready
        setTimeout(() => {
          scaleIframeToFill();
          // Also handle window resize
          window.addEventListener('resize', scaleIframeToFill);
        }, 1000);
      },
      onStateChange: (event) => {
        const state = event.data;
        isPlaying.value = state === window.YT.PlayerState.PLAYING;
        isLoading.value = state === window.YT.PlayerState.BUFFERING;

        // Hide thumbnail once video starts playing
        if (state === window.YT.PlayerState.PLAYING) {
          hasStartedPlaying.value = true;
        }

        emit('stateChange', { state });

        if (state === window.YT.PlayerState.PLAYING) {
          startHeartbeat();

          if (!antiCheatTimer) {
            antiCheatTimer = window.setInterval(() => {
              if (!player || !player.getCurrentTime) return;
              const currentTime = player.getCurrentTime() || 0;

              if (currentTime < lastAllowedTime - 0.5) {
                lastAllowedTime = currentTime;
                return;
              }

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
        if (playbackRate > 1.5 && player && player.setPlaybackRate) {
          player.setPlaybackRate(1.5);
          playbackRate = 1.5;
        }
        const time = player && player.getCurrentTime ? player.getCurrentTime() : 0;
        emit('heartbeat', { currentTime: time, playbackRate });
      },
    },
  });
}

function play() {
  if (player && player.playVideo) {
    hasStartedPlaying.value = true; // Hide thumbnail immediately on play click
    player.playVideo();
  }
}

function handleThumbnailError() {
  thumbnailError.value = true;
}

function togglePlay() {
  if (!player) return;
  if (isPlaying.value) {
    player.pauseVideo();
  } else {
    player.playVideo();
  }
}

function handleVideoClick(event) {
  // Don't toggle if clicking on interactive elements
  const target = event.target;
  const clickedElement = target.closest('button') ||
                        target.closest('.custom-progress-bar') ||
                        target.closest('.custom-volume-container') ||
                        target.closest('.custom-control-btn') ||
                        target.closest('.custom-play-button') ||
                        target.closest('.custom-play-button-large') ||
                        target.closest('.custom-controls-top') ||
                        target.closest('.custom-controls-bottom');

  // If clicking on interactive element, let it handle the click
  if (clickedElement && clickedElement !== target.closest('.youtube-hover-blocker')) {
    return;
  }

  // Toggle play/pause on video area click
  togglePlay();
}

function toggleMute() {
  if (!player) return;
  if (isMuted.value) {
    player.unMute();
    isMuted.value = false;
  } else {
    player.mute();
    isMuted.value = true;
  }
}

function setVolume(event) {
  const newVolume = parseInt(event.target.value);
  volume.value = newVolume;
  if (player && player.setVolume) {
    player.setVolume(newVolume);
  }
}

function seekTo(event) {
  if (!player || !duration.value) return;
  const rect = event.currentTarget.getBoundingClientRect();
  const x = event.clientX - rect.left;
  const percent = x / rect.width;
  const newTime = percent * duration.value;
  const currentTime = player.getCurrentTime ? player.getCurrentTime() : 0;

  // Anti-cheat: Only allow backward seeks or small forward seeks (within 1.25 seconds)
  // Block forward seeks beyond the allowed threshold
  if (newTime > currentTime + 1.25) {
    // Don't allow forward seeks beyond threshold
    return;
  }

  // Allow backward seeks or small forward seeks
  if (player.seekTo) {
    player.seekTo(newTime, true);
    currentTime.value = newTime;
    // Update lastAllowedTime only if it's a backward seek or within threshold
    if (newTime <= currentTime + 1.25) {
      lastAllowedTime = newTime;
    }
  }
}

function hideControls() {
  if (controlsTimeout) clearTimeout(controlsTimeout);
  controlsTimeout = setTimeout(() => {
    if (isPlaying.value) {
      showControls.value = false;
    }
  }, 3000);
}

function showControlsTemporary() {
  if (controlsTimeout) clearTimeout(controlsTimeout);
  showControls.value = true;
  // Reset the hide timer
  hideControls();
}

function handleMouseMove() {
  // Show controls on any mouse movement
  showControlsTemporary();
}

onMounted(async () => {
  await loadYouTubeApi();
  createPlayer();

  // Wait for player to be ready, then disable YouTube UI completely
  setTimeout(() => {
    if (playerEl.value) {
      const iframe = playerEl.value.querySelector('iframe');
      if (iframe) {
        // Disable pointer events on iframe to prevent YouTube hover
        iframe.style.pointerEvents = 'none';
        // Try to hide YouTube UI via CSS injection
        try {
          const style = document.createElement('style');
          style.id = 'youtube-ui-hide';
          style.textContent = `
            .custom-youtube-player iframe {
              pointer-events: none !important;
            }
          `;
          document.head.appendChild(style);
        } catch (e) {
          console.warn('Could not inject YouTube UI hiding styles:', e);
        }
      }
    }
  }, 2000);

  // Auto-hide controls when playing
  watch(isPlaying, (playing) => {
    if (playing) {
      hideControls();
    } else {
      showControls.value = true;
      if (controlsTimeout) clearTimeout(controlsTimeout);
    }
  });
});

onBeforeUnmount(() => {
  stopHeartbeat();
  if (controlsTimeout) clearTimeout(controlsTimeout);
  if (player && player.destroy) {
    player.destroy();
    player = null;
  }
  // Remove injected styles
  const style = document.getElementById('youtube-ui-hide');
  if (style) {
    style.remove();
  }
  // Remove resize listener
  window.removeEventListener('resize', scaleIframeToFill);
});

watch(
  () => props.startSeconds,
  (value) => {
    if (player && typeof value === 'number' && !Number.isNaN(value)) {
      const currentTime = player.getCurrentTime ? player.getCurrentTime() : 0;
      const newTime = Math.max(0, value);

      // Anti-cheat: Only allow backward seeks or small forward seeks (within 1.25 seconds)
      if (newTime > currentTime + 1.25) {
        // Don't allow forward seeks beyond threshold
        return;
      }

      // Allow backward seeks or small forward seeks
      player.seekTo(newTime, true);
      currentTime.value = newTime;
      // Update lastAllowedTime only if it's a backward seek or within threshold
      if (newTime <= currentTime + 1.25) {
        lastAllowedTime = newTime;
      }
    }
  }
);
</script>

<style scoped>
.custom-youtube-player {
  position: relative;
  width: 100%;
  height: 100%;
  background: #000;
  overflow: hidden;
  /* Ensure wrapper intercepts events */
  isolation: isolate;
  /* Prevent layout shift - use absolute positioning for children */
  display: block;
}

/* Hide YouTube's default UI completely */
.youtube-api-container {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
  /* Disable all pointer events to prevent YouTube hover effects */
  pointer-events: none;
  /* Ensure proper video fitting */
  overflow: hidden;
}

.youtube-api-container :deep(iframe) {
  border: none;
  /* Disable pointer events on iframe */
  pointer-events: none;
  /* Scale iframe to fill container - use scale transform to eliminate black bars */
  position: absolute;
  top: 50%;
  left: 50%;
  /* Calculate scale to fill container (assuming 16:9 video) */
  width: 177.78vh; /* 16/9 aspect ratio scaled to height */
  height: 100vh;
  max-width: none;
  max-height: none;
  transform: translate(-50%, -50%) scale(1.1);
  /* If container is wider than 16:9, scale based on width instead */
  min-width: 100%;
  min-height: 100%;
}

/* Additional global styles to hide YouTube UI */
:global(.ytp-watermark),
:global(.ytp-chrome-top),
:global(.ytp-title),
:global(.ytp-share-button),
:global(.ytp-title-channel),
:global(.ytp-chrome-bottom),
:global(.ytp-gradient-top),
:global(.ytp-gradient-bottom) {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  pointer-events: none !important;
}

/* Custom UI Layer - covers YouTube UI completely */
.custom-ui-layer {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 999999;
  pointer-events: auto;
  /* Ensure it's above everything and intercepts all events */
  isolation: isolate;
}

/* Invisible blocker that's always present to prevent YouTube hover */
.youtube-hover-blocker {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
  pointer-events: auto;
  /* Completely transparent but intercepts all mouse events */
  background: transparent;
  /* Ensure it covers everything */
  min-height: 100%;
  min-width: 100%;
  /* Allow clicks to pass through */
  cursor: pointer;
}
<｜tool▁calls▁begin｜><｜tool▁call▁begin｜>
run_terminal_cmd

.custom-controls {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  transition: opacity 0.3s;
  z-index: 2;
  /* Absolute positioning - doesn't affect parent layout */
  box-sizing: border-box;
}

.custom-controls-top {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  padding: 1rem;
  background: linear-gradient(to bottom, rgba(0,0,0,0.9), transparent);
  pointer-events: auto;
  /* Cover YouTube title area */
  height: 70px;
  /* Prevent layout shift - absolute positioning, fixed height */
  flex-shrink: 0;
  box-sizing: border-box;
  /* Always at top, doesn't affect layout */
  z-index: 3;
}

.custom-title {
  color: white;
  font-size: 1rem;
  font-weight: 600;
}

.custom-controls-center {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: auto;
  /* Absolute positioning - doesn't affect layout */
  z-index: 3;
}

.custom-play-button {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: #C73A5A; /* Primary color */
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s, background 0.2s;
}

.custom-play-button:hover {
  transform: scale(1.1);
  background: #DD4D6E; /* Lighter primary shade on hover */
}

.custom-controls-bottom {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 1rem;
  padding-top: 3rem; /* Space for full-width progress bar above */
  background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
  pointer-events: auto;
  /* Cover YouTube watermark area */
  padding-right: calc(150px + 1rem);
  min-height: 80px;
  max-height: 120px;
  /* Prevent layout shift - absolute positioning */
  flex-shrink: 0;
  box-sizing: border-box;
  /* Always at bottom, doesn't affect layout */
  z-index: 3;
  /* Allow scrolling if content overflows */
  overflow-x: auto;
  overflow-y: hidden;
  /* Smooth scrolling */
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
}

.custom-controls-bottom::-webkit-scrollbar {
  height: 4px;
}

.custom-controls-bottom::-webkit-scrollbar-track {
  background: transparent;
}

.custom-controls-bottom::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 2px;
}

.custom-controls-bottom::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.5);
}

.custom-progress-container {
  margin-bottom: 0.75rem;
}

.custom-progress-container-full {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 3.5rem; /* Position above the controls row */
  padding: 0 1rem;
  margin-bottom: 0;
  z-index: 4;
}

.custom-progress-bar {
  width: 100%;
  height: 6px;
  background: rgba(255, 255, 255, 0.3);
  border-radius: 3px;
  cursor: pointer;
  position: relative;
}

.custom-progress-filled {
  height: 100%;
  background: #ef4444;
  border-radius: 3px;
  transition: width 0.1s;
}

.custom-progress-handle {
  position: absolute;
  top: 50%;
  transform: translate(-50%, -50%);
  width: 14px;
  height: 14px;
  background: #ef4444;
  border-radius: 50%;
  opacity: 0;
  transition: opacity 0.2s;
}

.custom-progress-bar:hover .custom-progress-handle {
  opacity: 1;
}

.custom-controls-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  min-width: min-content;
  /* Ensure row doesn't shrink below content width */
  flex-wrap: nowrap;
}

.custom-control-btn {
  background: transparent;
  border: none;
  color: white;
  cursor: pointer;
  padding: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: opacity 0.2s;
}

.custom-control-btn:hover {
  opacity: 0.8;
}

.custom-time {
  color: white;
  font-size: 0.875rem;
  font-variant-numeric: tabular-nums;
}

.custom-volume-container {
  display: flex;
  align-items: center;
  width: 100px;
}

.custom-volume-slider {
  width: 100%;
  height: 4px;
  background: rgba(255, 255, 255, 0.3);
  border-radius: 2px;
  outline: none;
  cursor: pointer;
}

.custom-volume-slider::-webkit-slider-thumb {
  appearance: none;
  width: 12px;
  height: 12px;
  background: white;
  border-radius: 50%;
  cursor: pointer;
}

.custom-loading {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  pointer-events: none;
}

.custom-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Thumbnail Overlay - covers YouTube UI until video starts */
.custom-thumbnail-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 100;
  pointer-events: auto;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #000;
  /* Ensure it covers everything including YouTube UI areas */
  min-height: 100%;
  min-width: 100%;
}

.custom-thumbnail-image {
  width: 100%;
  height: 100%;
  object-fit: contain;
  position: absolute;
  top: 0;
  left: 0;
}

.custom-thumbnail-gradient {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(to bottom, rgba(0,0,0,0.3), transparent, rgba(0,0,0,0.3));
  z-index: 1;
}

.custom-thumbnail-play {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 2;
  pointer-events: auto;
}

.custom-play-button-large {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: #C73A5A; /* Primary color */
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s, background 0.2s;
  box-shadow: 0 4px 20px rgba(199, 58, 90, 0.4);
}

.custom-play-button-large:hover {
  transform: scale(1.1);
  background: #DD4D6E; /* Lighter primary shade on hover */
  box-shadow: 0 4px 25px rgba(199, 58, 90, 0.5);
}
</style>

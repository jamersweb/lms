<template>
  <div class="relative w-full aspect-video bg-black rounded-xl overflow-hidden shadow-lg" style="position: relative; min-height: 0;">
    <!-- YouTube (tracked via Custom Player with clean UI) -->
    <div v-if="shouldUseYouTubePlayer" class="w-full h-full" style="position: relative; min-height: 100%;">
      <CustomYouTubePlayer
        :video-id="effectiveYoutubeId"
        :start-seconds="startSeconds"
        :title="title"
        :allow-free-seek="props.allowFreeSeek"
        @ready="onYouTubeReady"
        @heartbeat="onYouTubeHeartbeat"
        @ended="onYouTubeEnded"
        @stateChange="onYouTubeStateChange"
      />
    </div>

    <!-- MP4 video (HTML5 video with controls) -->
    <div v-else-if="provider === 'mp4'" class="w-full h-full">
      <video
        ref="mp4El"
        class="w-full h-full object-contain bg-black"
        :src="videoUrl"
        playsinline
        preload="metadata"
        controls
        @timeupdate="onMp4TimeUpdate"
        @play="onMp4Play"
        @pause="onMp4Pause"
        @ended="onMp4Ended"
        @ratechange="onMp4RateChange"
        @seeking="onMp4Seeking"
      ></video>
    </div>

    <!-- Vimeo embed -->
    <div v-else-if="provider === 'vimeo'" class="w-full h-full">
      <iframe
        :src="videoUrl"
        class="w-full h-full"
        frameborder="0"
        allow="autoplay; fullscreen; picture-in-picture"
        allowfullscreen
      ></iframe>
    </div>

    <!-- External fallback (shows link) -->
    <div v-else-if="provider === 'external' && videoUrl" class="relative w-full h-full flex items-center justify-center bg-neutral-900">
      <div class="text-center p-8">
        <p class="text-white mb-4">External video content</p>
        <a :href="videoUrl" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
          <ExternalLink class="w-5 h-5" />
          Open Video
        </a>
      </div>
    </div>

    <div v-else class="absolute inset-0 flex items-center justify-center text-white/60 text-sm">
      <div class="text-center">
        <p class="mb-2">Video source not available.</p>
        <p class="text-xs text-white/40">Provider: {{ provider }}, Video URL: {{ videoUrl || 'none' }}</p>
      </div>
    </div>

    <!-- Dev mode tracking indicator -->
    <div v-if="isDevMode && sessionId" class="absolute top-2 right-2 bg-black/70 text-white text-xs px-2 py-1 rounded">
      Tracking active
    </div>

    <!-- Toast notification -->
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0 translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="toastMessage"
        class="absolute top-4 left-1/2 transform -translate-x-1/2 bg-red-600 text-white px-4 py-2 rounded-lg shadow-lg z-50 text-sm font-medium"
      >
        {{ toastMessage }}
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, watch, computed } from 'vue';
import CustomYouTubePlayer from '@/Components/CustomYouTubePlayer.vue';
import { ExternalLink } from 'lucide-vue-next';
import axios from 'axios';
import { route } from 'ziggy-js';

const props = defineProps({
  provider: {
    type: String,
    default: 'youtube',
  },
  videoUrl: {
    type: String,
    default: '',
  },
  youtubeId: {
    type: String,
    default: '',
  },
  startSeconds: {
    type: Number,
    default: 0,
  },
  lessonId: {
    type: Number,
    required: true,
  },
  title: {
    type: String,
    default: '',
  },
  durationSeconds: {
    type: Number,
    default: null,
  },
  autoplay: {
    type: Boolean,
    default: false,
  },
  allowFreeSeek: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['ready', 'heartbeat', 'ended', 'stateChange']);

// Dev mode check
const isDevMode = computed(() => import.meta.env.DEV);

// Extract YouTube video ID from URL
const extractVideoId = (url) => {
  if (!url || typeof url !== 'string') return '';

  const patterns = [
    // Match youtube-nocookie.com/embed/VIDEO_ID or youtube.com/embed/VIDEO_ID
    /(?:youtube-nocookie\.com\/embed\/|youtube\.com\/embed\/|youtu\.be\/)([a-zA-Z0-9_-]+)/,
    // Match youtube.com/watch?v=VIDEO_ID
    /(?:youtube\.com\/watch\?v=)([a-zA-Z0-9_-]+)/,
  ];

  for (const pattern of patterns) {
    const match = url.match(pattern);
    if (match && match[1]) {
      const videoId = match[1].split('?')[0].split('&')[0];
      if (isDevMode.value) {
        console.log('[VideoGuardPlayer] Extracted video ID:', videoId, 'from URL:', url);
      }
      return videoId;
    }
  }
  
  if (isDevMode.value) {
    console.warn('[VideoGuardPlayer] Could not extract video ID from URL:', url);
  }
  return '';
};

const extractedYoutubeId = computed(() => {
  // First try to extract from youtubeId prop
  if (props.youtubeId) {
    const extracted = extractVideoId(props.youtubeId);
    if (extracted) return extracted;
    if (props.youtubeId.length <= 11 && /^[a-zA-Z0-9_-]+$/.test(props.youtubeId)) {
      return props.youtubeId;
    }
  }
  // Then try to extract from videoUrl prop (for external YouTube URLs)
  if (props.videoUrl) {
    const extracted = extractVideoId(props.videoUrl);
    if (extracted) {
      console.log('[VideoGuardPlayer] Extracted YouTube ID from videoUrl:', extracted);
      return extracted;
    }
  }
  return '';
});

const isYouTubeUrl = computed(() => {
  if (!props.videoUrl) return false;
  return /youtube\.com|youtu\.be|youtube-nocookie\.com/.test(props.videoUrl);
});

const shouldUseYouTubePlayer = computed(() => {
  if (props.provider === 'youtube' && effectiveYoutubeId.value) {
    return true;
  }
  if (props.provider === 'external' && isYouTubeUrl.value && effectiveYoutubeId.value) {
    return true;
  }
  return false;
});

const effectiveYoutubeId = computed(() => {
  if (extractedYoutubeId.value) {
    return extractedYoutubeId.value;
  }
  if (props.youtubeId && props.youtubeId.length <= 11 && /^[a-zA-Z0-9_-]+$/.test(props.youtubeId)) {
    return props.youtubeId;
  }
  return '';
});

// Session tracking
const sessionId = ref(null);
const mp4El = ref(null);
let heartbeatInterval = null;
let lastHeartbeatPosition = 0;
let lastHeartbeatTime = 0;
let isSeeking = false;
let lastPlaybackRate = 1.0;
let lastAllowedTime = 0; // For seek blocking
let seekBlockedCount = 0;

// Video progress tracking
let progressUpdateInterval = null;
let lastProgressUpdate = 0;
const PROGRESS_UPDATE_INTERVAL_MS = 5000; // Update every 5 seconds
let lastProgressSnapshot = null; // Store last snapshot to avoid duplicate updates

// Toast notification
const toastMessage = ref('');
let toastTimeout = null;

// Configuration
const MAX_PLAYBACK_RATE = 1.5;
const MAX_FORWARD_JUMP = 5; // seconds

// Heartbeat interval: 15 seconds
const HEARTBEAT_INTERVAL_MS = 15000;

// Toast helper
const showToast = (message, duration = 3000) => {
  toastMessage.value = message;
  if (toastTimeout) clearTimeout(toastTimeout);
  toastTimeout = setTimeout(() => {
    toastMessage.value = '';
  }, duration);
};

// --- YouTube event forwarding ---
const onYouTubeReady = (payload) => {
  emit('ready', payload);
};

const onYouTubeHeartbeat = (payload) => {
  emit('heartbeat', payload);
  
  // Send heartbeat for watch tracking
  sendHeartbeat({
    position_seconds: payload.currentTime ?? 0,
    playback_rate: payload.playbackRate ?? 1,
    duration: payload.duration ?? 0,
  }, payload.isPlaying ?? true);
  
  // Update video progress (for resume functionality)
  // Always update, even if duration is 0 initially (it will be set later)
  updateVideoProgress({
    currentTime: payload.currentTime ?? 0,
    duration: payload.duration ?? 0,
    isPlaying: payload.isPlaying ?? false,
  });
};

const onYouTubeEnded = () => {
  emit('ended');
  stopProgressTracking();
  // Mark as completed (will be handled by heartbeat with 100% completion)
  endSession();
};

const onYouTubeStateChange = (payload) => {
  emit('stateChange', payload);
};

// --- MP4 tracking ---
const getMp4Snapshot = () => {
  const el = mp4El.value;
  if (!el) {
    return { currentTime: 0, duration: 0, playbackRate: 1 };
  }
  return {
    currentTime: el.currentTime || 0,
    duration: el.duration || 0,
    playbackRate: el.playbackRate || 1,
  };
};

const onMp4TimeUpdate = () => {
  const el = mp4El.value;
  if (!el) return;

  const currentTime = el.currentTime || 0;

  // When lesson is completed, allow free seeking (drag to any time)
  if (props.allowFreeSeek) {
    lastAllowedTime = Math.max(lastAllowedTime, currentTime);
  } else if (!isSeeking) {
    // Block forward seeks (only when lesson not completed)
    if (currentTime < lastAllowedTime - 0.5) {
      lastAllowedTime = currentTime;
    } else {
      if (currentTime > lastAllowedTime + MAX_FORWARD_JUMP) {
        try {
          el.currentTime = lastAllowedTime;
          seekBlockedCount++;
          showToast('Skipping ahead is disabled');
        } catch {
          // ignore
        }
      } else {
        lastAllowedTime = Math.max(lastAllowedTime, currentTime);
      }
    }
  }

  const snapshot = getMp4Snapshot();
  emit('heartbeat', snapshot);
  // Heartbeat is sent via interval, not on every timeupdate
  
  // Update video progress (throttled)
  const now = Date.now();
  if (now - lastProgressUpdate >= PROGRESS_UPDATE_INTERVAL_MS) {
    updateVideoProgress({
      currentTime: snapshot.currentTime,
      duration: snapshot.duration,
      isPlaying: !el.paused,
    });
    lastProgressUpdate = now;
  }
};

const onMp4Play = () => {
  emit('stateChange', { state: 'playing', ...getMp4Snapshot() });
  startHeartbeatInterval();
};

const onMp4Pause = () => {
  emit('stateChange', { state: 'paused', ...getMp4Snapshot() });
  stopHeartbeatInterval();
  // Save progress on pause
  saveVideoProgress();
};

const onMp4Ended = () => {
  emit('ended');
  emit('stateChange', { state: 'ended', ...getMp4Snapshot() });
  stopHeartbeatInterval();
  stopProgressTracking();
  // Mark as completed
  saveVideoProgress(true);
  endSession();
};

const onMp4RateChange = () => {
  const el = mp4El.value;
  if (el) {
    // Strict cap playback rate to 1.5
    if (el.playbackRate > MAX_PLAYBACK_RATE) {
      try {
        el.playbackRate = MAX_PLAYBACK_RATE;
        showToast(`Max speed is ${MAX_PLAYBACK_RATE}x`);
      } catch {
        // ignore
      }
    }
    lastPlaybackRate = el.playbackRate;
  }
  emit('heartbeat', getMp4Snapshot());
};

const onMp4Seeking = () => {
  const el = mp4El.value;
  if (!el) return;

  isSeeking = true;
  const currentTime = el.currentTime || 0;

  if (props.allowFreeSeek) {
    lastAllowedTime = Math.max(lastAllowedTime, currentTime);
  } else {
    if (currentTime > lastAllowedTime + MAX_FORWARD_JUMP) {
      try {
        el.currentTime = lastAllowedTime;
        seekBlockedCount++;
        showToast('Skipping ahead is disabled');
      } catch {
        // ignore
      }
    } else if (currentTime < lastAllowedTime) {
      lastAllowedTime = currentTime;
    }
  }

  setTimeout(() => {
    isSeeking = false;
  }, 1000);
};

// React to external seek events via startSeconds prop
watch(
  () => props.startSeconds,
  (value) => {
    const el = mp4El.value;
    if (!el || typeof value !== 'number' || Number.isNaN(value)) return;
    const targetTime = Math.max(0, value);

    if (props.allowFreeSeek || targetTime <= lastAllowedTime + MAX_FORWARD_JUMP) {
      try {
        el.currentTime = targetTime;
        lastHeartbeatPosition = targetTime;
        lastAllowedTime = Math.max(lastAllowedTime, targetTime);
      } catch {
        // ignore seek errors
      }
    } else {
      showToast('Skipping ahead is disabled');
    }
  }
);

// Heartbeat management
const startHeartbeatInterval = () => {
  if (heartbeatInterval) return;

  heartbeatInterval = setInterval(() => {
    if (props.provider === 'mp4' && mp4El.value) {
      const snapshot = getMp4Snapshot();
      sendHeartbeat({
        position_seconds: snapshot.currentTime,
        playback_rate: snapshot.playbackRate,
        duration: snapshot.duration,
      }, !mp4El.value.paused);
    }
    // YouTube heartbeats are sent via CustomYouTubePlayer events
  }, HEARTBEAT_INTERVAL_MS);
};

const stopHeartbeatInterval = () => {
  if (heartbeatInterval) {
    clearInterval(heartbeatInterval);
    heartbeatInterval = null;
  }
};

// Video progress tracking functions
const updateVideoProgress = async (snapshot) => {
  if (!props.lessonId) {
    if (isDevMode.value) {
      console.warn('[VideoGuardPlayer] Cannot update progress: lessonId is missing');
    }
    return;
  }

  const currentTime = snapshot.currentTime || 0;
  let duration = snapshot.duration || props.durationSeconds || 0;
  
  // For YouTube videos, duration might not be available immediately
  // But we should still update progress with current time even if duration is 0
  // The backend can handle this and use watched_seconds for completion
  
  const percentComplete = duration > 0 ? (currentTime / duration) * 100 : 0;

  // Throttle updates to avoid too many requests (every 5 seconds)
  const now = Date.now();
  const timeSinceLastUpdate = now - lastProgressUpdate;
  
  // Skip if we updated recently (unless duration just became available)
  if (timeSinceLastUpdate < PROGRESS_UPDATE_INTERVAL_MS) {
    // Only skip if duration is still 0, or if values haven't changed much
    if (duration <= 0 || (lastProgressSnapshot && 
        Math.abs(lastProgressSnapshot.currentTime - currentTime) < 2 &&
        Math.abs(lastProgressSnapshot.duration - duration) < 1)) {
      return;
    }
  }
  lastProgressUpdate = now;
  lastProgressSnapshot = { currentTime, duration };

  try {
    // Get lesson model to pass to route
    const lessonId = props.lessonId;
    const provider = shouldUseYouTubePlayer.value ? 'youtube' : (props.provider === 'vimeo' ? 'vimeo' : 'html5');
    
    const response = await axios.post(route('lesson-progress.update', { lesson: lessonId }), {
      duration_seconds: Math.round(duration),
      last_position_seconds: Math.round(currentTime),
      percent_complete: Math.round(percentComplete * 100) / 100, // Round to 2 decimals
      provider: provider,
    });
    
    // Log success in dev mode
    if (isDevMode.value) {
      console.log('[VideoGuardPlayer] Progress updated:', {
        currentTime: Math.round(currentTime),
        duration: Math.round(duration),
        percentComplete: Math.round(percentComplete * 100) / 100,
        provider: provider
      });
    }
  } catch (error) {
    // Log error for debugging
    console.error('[VideoGuardPlayer] Failed to update video progress:', {
      error: error.response?.data || error.message,
      lessonId: props.lessonId,
      currentTime: Math.round(currentTime),
      duration: Math.round(duration)
    });
  }
};

const saveVideoProgress = async (isCompleted = false) => {
  if (!props.lessonId) return;

  let snapshot;
  if (props.provider === 'mp4' && mp4El.value) {
    snapshot = getMp4Snapshot();
  } else {
    // For YouTube, we can't get current state here, skip
    return;
  }

  if (!snapshot.duration || snapshot.duration <= 0) return;

  const currentTime = snapshot.currentTime || 0;
  const duration = snapshot.duration || 0;
  let percentComplete = duration > 0 ? (currentTime / duration) * 100 : 0;

  if (isCompleted) {
    percentComplete = 100;
  }

  try {
    const lessonId = props.lessonId;
    await axios.post(route('lesson-progress.update', { lesson: lessonId }), {
      duration_seconds: Math.round(duration),
      last_position_seconds: isCompleted ? Math.round(duration) : Math.round(currentTime),
      percent_complete: Math.round(percentComplete * 100) / 100,
      is_completed: isCompleted,
      provider: props.provider === 'youtube' ? 'youtube' : props.provider === 'vimeo' ? 'vimeo' : 'html5',
    });
  } catch (error) {
    console.debug('Failed to save video progress:', error);
  }
};

const startProgressTracking = () => {
  if (progressUpdateInterval) return;
  
  // For MP4, progress is tracked in timeupdate
  // For YouTube, track via heartbeat events
  // Set up beforeunload handler
  window.addEventListener('beforeunload', saveVideoProgress);
};

const stopProgressTracking = () => {
  if (progressUpdateInterval) {
    clearInterval(progressUpdateInterval);
    progressUpdateInterval = null;
  }
  window.removeEventListener('beforeunload', saveVideoProgress);
};

// Session management
onMounted(() => {
  if (props.provider === 'mp4' && mp4El.value && props.startSeconds > 0) {
    try {
      const startTime = Math.max(0, props.startSeconds);
      mp4El.value.currentTime = startTime;
      lastHeartbeatPosition = startTime;
      lastAllowedTime = startTime;
    } catch {
      // ignore
    }
  } else if (props.provider === 'mp4' && mp4El.value) {
    // Initialize lastAllowedTime even if startSeconds is 0
    lastAllowedTime = 0;
  }

  startSession();
  startProgressTracking();

  // Start heartbeat interval for MP4
  if (props.provider === 'mp4') {
    startHeartbeatInterval();
  }

  // Track visibility changes
  document.addEventListener('visibilitychange', handleVisibilityChange);
});

onBeforeUnmount(() => {
  stopHeartbeatInterval();
  stopProgressTracking();
  saveVideoProgress(); // Final save
  endSession();
  document.removeEventListener('visibilitychange', handleVisibilityChange);
});

const startSession = async () => {
  try {
    const url = route('lessons.watch.start', { lesson: props.lessonId });
    const response = await axios.post(url);
    sessionId.value = response.data.session_id ?? null;
    lastHeartbeatTime = Date.now();
  } catch (error) {
    console.warn('Watch session could not be started:', error.response?.status === 403 ? 'Access denied' : 'Unknown error');
    sessionId.value = null;
  }
};

const sendHeartbeat = async (snapshot, isPlaying) => {
  if (!sessionId.value || !props.lessonId) return;

  const now = Date.now();
  const positionSeconds = snapshot.position_seconds ?? 0;
  const playbackRate = snapshot.playback_rate ?? 1.0;

  // Cap playback rate on client side
  const clampedRate = Math.min(playbackRate, MAX_PLAYBACK_RATE);

  // Compute played delta (time since last heartbeat)
  const timeDelta = (now - lastHeartbeatTime) / 1000; // seconds
  const playedDeltaSeconds = isPlaying ? Math.round(timeDelta * clampedRate) : 0;

  // Detect seek: position jump > threshold forward
  const positionJump = positionSeconds - lastHeartbeatPosition;
  const seekDetected = isSeeking || (positionJump > MAX_FORWARD_JUMP && positionJump < (snapshot.duration || Infinity));

  // Get visibility state
  const visibility = document.visibilityState === 'visible' ? 'visible' : 'hidden';

  try {
    const url = route('lessons.watch.heartbeat', { lesson: props.lessonId });
    await axios.post(url, {
      session_id: sessionId.value,
      position_seconds: positionSeconds,
      playback_rate: clampedRate,
      played_delta_seconds: playedDeltaSeconds,
      visibility: visibility,
      is_seeking: seekDetected,
      client_ts: new Date().toISOString(),
    });

    lastHeartbeatPosition = positionSeconds;
    lastHeartbeatTime = now;
    isSeeking = false; // Reset after sending
  } catch (error) {
    // best-effort; ignore failures
    if (error.response?.status === 403) {
      // Session invalidated, stop tracking
      sessionId.value = null;
      stopHeartbeatInterval();
    }
  }
};

const endSession = async () => {
  if (!sessionId.value || !props.lessonId) return;

  try {
    const url = route('lessons.watch.end', { lesson: props.lessonId });
    await axios.post(url, {
      session_id: sessionId.value,
    });
  } catch {
    // ignore
  }
};

const handleVisibilityChange = () => {
  // Visibility changes are tracked in heartbeat
  // This handler can be used for additional logic if needed
};
</script>

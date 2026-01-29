<template>
  <div class="relative w-full aspect-video bg-black rounded-xl overflow-hidden shadow-lg" style="position: relative; min-height: 0;">
    <!-- YouTube (tracked via Custom Player with clean UI) -->
    <div v-if="shouldUseYouTubePlayer" class="w-full h-full" style="position: relative; min-height: 100%;">
      <CustomYouTubePlayer
        :video-id="effectiveYoutubeId"
        :start-seconds="startSeconds"
        :title="title"
        @ready="onYouTubeReady"
        @heartbeat="onYouTubeHeartbeat"
        @ended="onYouTubeEnded"
        @stateChange="onYouTubeStateChange"
      />
    </div>

    <!-- MP4 video (HTML5 video, controls hidden for now) -->
    <div v-else-if="provider === 'mp4'" class="w-full h-full">
      <video
        ref="mp4El"
        class="w-full h-full object-contain bg-black"
        :src="videoUrl"
        playsinline
        preload="metadata"
        @timeupdate="onMp4TimeUpdate"
        @play="onMp4Play"
        @pause="onMp4Pause"
        @ended="onMp4Ended"
        @ratechange="onMp4RateChange"
      ></video>
    </div>

    <!-- External fallback (including YouTube without video ID) - Direct iframe like Tazkiyah Tarbiyah -->
    <div v-else-if="videoUrl" class="relative w-full h-full youtube-iframe-wrapper" style="position: relative; overflow: hidden;">
      <iframe
        :src="videoUrl"
        tabindex="-1"
        data-no-controls=""
        class="vds-youtube w-full h-full"
        aria-hidden="true"
        frameborder="0"
        allow="autoplay; fullscreen; encrypted-media; picture-in-picture; accelerometer; gyroscope"
        allowfullscreen
        style="position: relative; z-index: 1;"
      ></iframe>
      <!-- Overlays to hide YouTube UI elements - MUST be after iframe in DOM -->
      <div
        class="youtube-watermark-overlay"
        style="position: absolute; bottom: 0; right: 0; width: 150px; height: 40px; background: #000; z-index: 999999; pointer-events: none;"
      ></div>
      <div
        class="youtube-title-overlay"
        style="position: absolute; top: 0; left: 0; right: 0; height: 70px; background: #000; z-index: 999999; pointer-events: none;"
      ></div>
    </div>

    <div v-else class="absolute inset-0 flex items-center justify-center text-white/60 text-sm">
      <div class="text-center">
        <p class="mb-2">Video source not available.</p>
        <p class="text-xs text-white/40">Provider: {{ provider }}, Video URL: {{ videoUrl || 'none' }}, YouTube ID: {{ youtubeId || 'none' }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, watch, computed } from 'vue';
import CustomYouTubePlayer from '@/Components/CustomYouTubePlayer.vue';
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
});

const emit = defineEmits(['ready', 'heartbeat', 'ended', 'stateChange']);

// Extract YouTube video ID from URL (handles cases where youtubeId prop contains full URL)
const extractVideoId = (url) => {
  if (!url || typeof url !== 'string') {
    console.log('[extractVideoId] Invalid input:', url);
    return '';
  }

  console.log('[extractVideoId] Extracting from:', url);

  // Match patterns like:
  // - https://www.youtube.com/embed/VIDEO_ID
  // - https://www.youtube-nocookie.com/embed/VIDEO_ID?params
  // - https://youtu.be/VIDEO_ID
  // - https://www.youtube.com/watch?v=VIDEO_ID
  const patterns = [
    /(?:youtube\.com\/embed\/|youtube-nocookie\.com\/embed\/|youtu\.be\/)([a-zA-Z0-9_-]+)/,
    /(?:youtube\.com\/watch\?v=)([a-zA-Z0-9_-]+)/,
  ];

  for (const pattern of patterns) {
    const match = url.match(pattern);
    if (match && match[1]) {
      // Extract just the video ID (stop at first ? or & if present)
      const videoId = match[1].split('?')[0].split('&')[0];
      console.log('[extractVideoId] Extracted ID:', videoId);
      return videoId;
    }
  }

  console.log('[extractVideoId] No match found');
  return '';
};

// Extract YouTube video ID from URL if not provided or if youtubeId contains full URL
const extractedYoutubeId = computed(() => {
  // If youtubeId is provided, always try to extract (it might be a URL)
  if (props.youtubeId) {
    // Try to extract first - this handles both URLs and IDs
    const extracted = extractVideoId(props.youtubeId);
    if (extracted) {
      return extracted;
    }
    // If extraction failed, check if it's already a valid YouTube video ID
    // (typically 11 characters, alphanumeric with dashes/underscores)
    if (props.youtubeId.length <= 11 && /^[a-zA-Z0-9_-]+$/.test(props.youtubeId)) {
      return props.youtubeId;
    }
    // If it's not a valid ID format, return empty (will fall back to iframe)
    return '';
  }

  // Try to extract from videoUrl if it's a YouTube URL (regardless of provider)
  if (props.videoUrl) {
    return extractVideoId(props.videoUrl);
  }

  return '';
});

// Check if videoUrl is a YouTube URL (even if provider is external)
const isYouTubeUrl = computed(() => {
  if (!props.videoUrl) return false;
  return /youtube\.com|youtu\.be|youtube-nocookie\.com/.test(props.videoUrl);
});

// Determine if we should use YouTube player (even if provider is external but URL is YouTube)
const shouldUseYouTubePlayer = computed(() => {
  // Use YouTube IFrame API if:
  // 1. Provider is youtube AND we have a valid video ID, OR
  // 2. Provider is external BUT the URL is a YouTube URL AND we can extract a video ID
  if (props.provider === 'youtube' && effectiveYoutubeId.value) {
    return true;
  }
  if (props.provider === 'external' && isYouTubeUrl.value && effectiveYoutubeId.value) {
    return true;
  }
  return false;
});

// Use extracted ID (always prefer extracted over raw prop)
const effectiveYoutubeId = computed(() => {
  // Always prefer the extracted ID if available
  if (extractedYoutubeId.value) {
    return extractedYoutubeId.value;
  }
  // Fall back to props.youtubeId only if it's a valid ID format (not a URL)
  if (props.youtubeId && props.youtubeId.length <= 11 && /^[a-zA-Z0-9_-]+$/.test(props.youtubeId)) {
    return props.youtubeId;
  }
  return '';
});

const mp4El = ref(null);
let mp4LastAllowedTime = 0;
let mp4IsPlaying = false;
const sessionId = ref(null);
let lastHeartbeatSentAt = 0;

// --- YouTube event forwarding ---
const onYouTubeReady = (payload) => {
  emit('ready', payload);
};

const onYouTubeHeartbeat = (payload) => {
  emit('heartbeat', payload);
  sendHeartbeat({
    currentTime: payload.currentTime ?? 0,
    duration: payload.duration ?? 0,
    playbackRate: payload.playbackRate ?? 1,
  }, true);
};

const onYouTubeEnded = () => {
  emit('ended');
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

  const snapshot = getMp4Snapshot();
  const currentTime = snapshot.currentTime;

  if (mp4IsPlaying) {
    // Allow backward seeks
    if (currentTime < mp4LastAllowedTime - 0.5) {
      mp4LastAllowedTime = currentTime;
    } else {
      // Block forward jumps beyond threshold
      if (currentTime > mp4LastAllowedTime + 1.25) {
        try {
          el.currentTime = mp4LastAllowedTime;
        } catch {
          // ignore
        }
      } else {
        mp4LastAllowedTime = Math.max(mp4LastAllowedTime, currentTime);
      }
    }
  }

  emit('heartbeat', snapshot);
  sendHeartbeat(snapshot, mp4IsPlaying);
};

const onMp4Play = () => {
  mp4IsPlaying = true;
  emit('stateChange', { state: 'playing', ...getMp4Snapshot() });
};

const onMp4Pause = () => {
  mp4IsPlaying = false;
  emit('stateChange', { state: 'paused', ...getMp4Snapshot() });
};

const onMp4Ended = () => {
  mp4IsPlaying = false;
  emit('ended');
  emit('stateChange', { state: 'ended', ...getMp4Snapshot() });
  endSession();
};

const onMp4RateChange = () => {
  const el = mp4El.value;
  if (el && el.playbackRate > 1.5) {
    try {
      el.playbackRate = 1.5;
    } catch {
      // ignore
    }
  }
  emit('heartbeat', getMp4Snapshot());
};

// React to external seek events via startSeconds prop for MP4 as well
watch(
  () => props.startSeconds,
  (value) => {
    const el = mp4El.value;
    if (!el || typeof value !== 'number' || Number.isNaN(value)) return;
    try {
      el.currentTime = Math.max(0, value);
      mp4LastAllowedTime = Math.max(0, value);
    } catch {
      // ignore seek errors
    }
  }
);

// Optionally autoplay MP4 when timestamp seek is triggered (Phase 2 can adjust UX)
onMounted(() => {
  // Debug logging - expand object to see all values
  const debugInfo = {
    provider: props.provider,
    videoUrl: props.videoUrl,
    youtubeId: props.youtubeId,
    extractedYoutubeId: extractedYoutubeId.value,
    effectiveYoutubeId: effectiveYoutubeId.value,
    shouldUseYouTubePlayer: shouldUseYouTubePlayer.value,
    lessonId: props.lessonId,
  };
  console.log('[TrackedVideoPlayer] Mounted:', debugInfo);
  console.log('[TrackedVideoPlayer] Full youtubeId value:', JSON.stringify(props.youtubeId));
  console.log('[TrackedVideoPlayer] Extracted ID:', extractedYoutubeId.value);
  console.log('[TrackedVideoPlayer] Effective ID:', effectiveYoutubeId.value);

  if (props.provider === 'mp4' && mp4El.value && props.startSeconds > 0) {
    try {
      mp4El.value.currentTime = Math.max(0, props.startSeconds);
    } catch {
      // ignore
    }
  }

  startSession();
});

onBeforeUnmount(() => {
  endSession();
});

const startSession = async () => {
  try {
    const url = route('lessons.watch.start', { lesson: props.lessonId });
    const response = await axios.post(url);
    sessionId.value = response.data.session_id ?? null;
  } catch (error) {
    // If enrollment check fails, session will be null
    // Video can still play but won't be tracked
    console.warn('Watch session could not be started:', error.response?.status === 403 ? 'Enrollment required' : 'Unknown error');
    sessionId.value = null;
  }
};

const sendHeartbeat = async (snapshot, isPlaying) => {
  if (!sessionId.value || !props.lessonId) return;

  const now = Date.now();
  if (now - lastHeartbeatSentAt < 4500) {
    return;
  }
  lastHeartbeatSentAt = now;

  try {
    const url = route('lessons.watch.heartbeat', { lesson: props.lessonId });
    await axios.post(url, {
      session_id: sessionId.value,
      current_time: snapshot.currentTime ?? 0,
      playback_rate: snapshot.playbackRate ?? 1,
      // extra fields ignored by backend for now
      is_playing: !!isPlaying,
      duration_seconds: snapshot.duration ?? null,
    });
  } catch {
    // best-effort; ignore failures
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
</script>


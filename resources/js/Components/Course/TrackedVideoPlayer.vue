<template>
  <div class="relative w-full aspect-video bg-black rounded-xl overflow-hidden shadow-lg">
    <!-- YouTube (tracked via IFrame API wrapper) -->
    <div v-if="provider === 'youtube'" class="w-full h-full">
      <YouTubePlayer
        :video-id="youtubeId"
        :start-seconds="startSeconds"
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

    <!-- External fallback -->
    <iframe
      v-else-if="videoUrl"
      :src="videoUrl"
      class="w-full h-full"
      frameborder="0"
      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
      allowfullscreen
    ></iframe>

    <div v-else class="absolute inset-0 flex items-center justify-center text-white/60 text-sm">
      Video source not available.
    </div>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';
import YouTubePlayer from '@/Components/YouTubePlayer.vue';
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
});

const emit = defineEmits(['ready', 'heartbeat', 'ended', 'stateChange']);

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
  } catch {
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


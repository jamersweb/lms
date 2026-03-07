<template>
  <AppShell>
    <!-- Header -->
    <div class="mb-8">
      <h1 class="font-serif text-3xl font-bold text-neutral-900">Sunnah Tracker</h1>
      <p class="text-neutral-600 mt-1">Consistency is key. Track your daily habits. Habits appear after you complete lesson videos.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <Card noPadding class="p-6 flex items-center justify-between bg-primary-50 border-primary-100">
            <div>
                <div class="text-sm font-medium text-primary-600">Current Streak</div>
                <div class="text-3xl font-bold text-primary-800 font-serif">{{ current_streak }} {{ current_streak === 1 ? 'Day' : 'Days' }}</div>
            </div>
            <div class="h-12 w-12 bg-white rounded-full flex items-center justify-center text-primary-600 shadow-sm">
                <Flame class="h-6 w-6" />
            </div>
        </Card>
         <Card noPadding class="p-6 flex items-center justify-between">
            <div>
                <div class="text-sm font-medium text-neutral-500">Active Habits</div>
                <div class="text-3xl font-bold text-neutral-800 font-serif">{{ habits.length }}</div>
            </div>
            <div class="h-12 w-12 bg-neutral-50 rounded-full flex items-center justify-center text-neutral-400">
                <CheckSquare class="h-6 w-6" />
            </div>
        </Card>
         <Card noPadding class="p-6 flex items-center justify-between">
            <div>
                <div class="text-sm font-medium text-neutral-500">Completion Rate</div>
                <div class="text-3xl font-bold text-neutral-800 font-serif">{{ completion_rate }}%</div>
                <p class="text-xs text-neutral-400 mt-0.5">Last 30 days</p>
            </div>
            <div class="h-12 w-12 bg-neutral-50 rounded-full flex items-center justify-center text-neutral-400">
                <BarChart3 class="h-6 w-6" />
            </div>
        </Card>
    </div>
    
    <!-- Habits List -->
    <div class="space-y-4">
        <div v-if="habits.length === 0" class="text-center py-16 bg-white rounded-xl border border-neutral-100">
             <div class="mx-auto h-16 w-16 bg-neutral-50 rounded-full flex items-center justify-center text-neutral-300 mb-4">
                <CheckSquare class="h-8 w-8" />
             </div>
             <h3 class="text-lg font-medium text-neutral-900">No habits yet</h3>
             <p class="text-neutral-500">Complete lesson videos to unlock Sunnah habits. Admin adds habits for each lesson.</p>
        </div>

        <div v-else class="bg-white rounded-xl border border-neutral-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-100 bg-neutral-50/50 flex items-center justify-between">
               <h3 class="font-bold text-neutral-900">Today's Checklist</h3>
               <span class="text-xs font-medium text-neutral-500">{{ new Date().toLocaleDateString() }}</span>
            </div>
            
            <div class="divide-y divide-neutral-100">
                <div v-for="habit in habits" :key="habit.id" class="p-4 flex items-center justify-between hover:bg-neutral-50 transition-colors group">
                    <div class="flex items-center gap-4">
                        <div class="h-10 w-10 rounded-full bg-primary-50 flex items-center justify-center text-primary-600">
                            <Activity class="h-5 w-5" />
                        </div>
                        <div>
                            <h4 class="font-medium text-neutral-900">{{ habit.title }}</h4>
                            <p class="text-xs text-neutral-500">{{ habit.description }}</p>
                        </div>
                        <div v-if="habit.current_streak > 0" class="flex items-center gap-1 text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">
                            <Flame class="w-3 h-3" /> {{ habit.current_streak }}
                        </div>
                    </div>
                
                    <div class="flex items-center gap-2">
                        <button 
                            @click="logToday(habit, 'done')"
                            :class="[
                                isTodayDone(habit) 
                                    ? 'bg-emerald-100 text-emerald-700 border-emerald-200 shadow-inner' 
                                    : 'bg-white border-neutral-200 text-neutral-600 hover:border-emerald-300 hover:text-emerald-600 hover:bg-emerald-50',
                                'px-4 py-2 rounded-lg border text-sm font-medium transition-all flex items-center gap-2'
                            ]">
                            <Check v-if="isTodayDone(habit)" class="w-4 h-4" />
                            <span>Done</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

  </AppShell>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import AppShell from '@/Layouts/AppShell.vue';
import Button from '@/Components/Common/Button.vue';
import Card from '@/Components/Common/Card.vue';
import { Flame, CheckSquare, BarChart3, Activity, Check } from 'lucide-vue-next';

const props = defineProps({
  habits: Array,
  completion_rate: { type: Number, default: 0 },
  current_streak: { type: Number, default: 0 },
})

function logToday(habit, status) {
    // In a real app this would call the API
    // For demo (dummy data), we might just mock the visual change locally if it wasn't a page reload
    router.post(`/habits/${habit.id}/log`, {
        date: new Date().toISOString().slice(0, 10),
        status: status,
    }, {
        preserveScroll: true,
    })
}

function isTodayDone(habit) {
    return habit.today_log && habit.today_log.status === 'done';
}
</script>

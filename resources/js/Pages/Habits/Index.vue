<template>
  <AppShell>
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="font-serif text-3xl font-bold text-neutral-900">Sunnah Tracker</h1>
        <p class="text-neutral-600 mt-1">Consistency is key. Track your daily habits.</p>
      </div>
      <Button @click="showCreateModal = true">
         <Plus class="w-4 h-4 mr-2" />
         New Habit
      </Button>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <Card noPadding class="p-6 flex items-center justify-between bg-primary-50 border-primary-100">
            <div>
                <div class="text-sm font-medium text-primary-600">Current Streak</div>
                <div class="text-3xl font-bold text-primary-800 font-serif">5 Days</div>
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
                <div class="text-3xl font-bold text-neutral-800 font-serif">85%</div>
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
             <h3 class="text-lg font-medium text-neutral-900">No active habits</h3>
             <p class="text-neutral-500">Start building your Sunnah habits today!</p>
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

    <!-- Create Modal (Simplified) -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-neutral-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
         <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
            <div class="px-6 py-4 border-b border-neutral-100 bg-neutral-50 flex justify-between items-center">
                <h2 class="font-serif text-xl font-bold text-neutral-900">New Habit</h2>
                <button @click="showCreateModal = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
            </div>
            
            <form @submit.prevent="createHabit" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Habit Title</label>
                    <input v-model="form.title" type="text" placeholder="e.g. Read Quran" class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" required />
                </div>
                 <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Description</label>
                    <textarea v-model="form.description" rows="2" placeholder="Optional details..." class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                </div>
                 
                 <div class="flex justify-end gap-3 mt-6">
                    <Button variant="secondary" @click="showCreateModal = false">Cancel</Button>
                    <Button type="submit">Create Habit</Button>
                </div>
            </form>
         </div>
    </div>
  </AppShell>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppShell from '@/Layouts/AppShell.vue';
import Button from '@/Components/Common/Button.vue';
import Card from '@/Components/Common/Card.vue';
import { Flame, CheckSquare, BarChart3, Plus, Activity, Check } from 'lucide-vue-next';

const props = defineProps({
  habits: Array,
})

const showCreateModal = ref(false)

const form = useForm({
  title: '',
  description: '',
  frequency_type: 'daily',
  target_per_day: 1,
})

function createHabit() {
  form.post('/habits', {
    onSuccess: () => {
      showCreateModal.value = false
      form.reset()
    },
  })
}

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

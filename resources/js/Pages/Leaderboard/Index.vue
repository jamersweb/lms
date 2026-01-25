<template>
  <AppShell>
    <div class="mb-6 md:mb-8">
      <h1 class="font-serif text-2xl md:text-3xl font-bold text-neutral-900">Community Leaderboard</h1>
      <p class="text-neutral-600 mt-1 text-sm md:text-base">Strive for excellence (Ihsan) in your learning journey.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">
       <!-- Main Leaderboard -->
       <div class="lg:col-span-2 space-y-4">
          <!-- Desktop Table View -->
          <div class="hidden md:block bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-sm">
             <table class="w-full">
                <thead class="bg-neutral-50 border-b border-neutral-100">
                   <tr>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-500 uppercase">Rank</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-neutral-500 uppercase">Student</th>
                      <th class="px-6 py-4 text-right text-xs font-semibold text-neutral-500 uppercase">Points</th>
                   </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                   <tr 
                     v-for="entry in leaderboard" 
                     :key="entry.rank"
                     :class="[
                       'transition-colors',
                       entry.is_me ? 'bg-primary-50' : 'hover:bg-neutral-50'
                     ]"
                   >
                      <td class="px-6 py-4 whitespace-nowrap">
                         <div class="flex items-center justify-center w-8 h-8 rounded-full font-bold text-sm"
                           :class="[
                              entry.rank === 1 ? 'bg-yellow-100 text-yellow-700' :
                              entry.rank === 2 ? 'bg-slate-200 text-slate-700' :
                              entry.rank === 3 ? 'bg-amber-100 text-amber-800' :
                              'text-neutral-500'
                           ]"
                         >
                            {{ entry.rank }}
                         </div>
                      </td>
                      <td class="px-6 py-4">
                         <div class="flex items-center">
                            <img :src="entry.avatar" alt="" class="h-10 w-10 rounded-full mr-4 border border-neutral-100">
                            <div>
                               <div class="font-medium text-neutral-900 flex items-center gap-2">
                                  {{ entry.name }}
                                  <Badge v-if="entry.is_me" variant="primary">You</Badge>
                               </div>
                               <div class="flex gap-1 mt-1">
                                  <Badge v-for="badge in entry.badges" :key="badge" variant="neutral" class="text-[10px] px-1.5 py-0">{{ badge }}</Badge>
                               </div>
                            </div>
                         </div>
                      </td>
                      <td class="px-6 py-4 text-right whitespace-nowrap">
                         <span class="font-bold text-neutral-900">{{ entry.points }}</span>
                         <span class="text-xs text-neutral-500 ml-1">pts</span>
                      </td>
                   </tr>
                </tbody>
             </table>
          </div>

          <!-- Mobile Card View -->
          <div class="md:hidden space-y-3">
             <div 
               v-for="entry in leaderboard" 
               :key="entry.rank"
               :class="[
                 'bg-white rounded-xl border shadow-sm p-4 transition-all',
                 entry.is_me ? 'border-primary-300 bg-primary-50' : 'border-neutral-200'
               ]"
             >
                <div class="flex items-center gap-4">
                   <!-- Rank Badge -->
                   <div class="flex items-center justify-center w-12 h-12 rounded-full font-bold text-lg shrink-0"
                     :class="[
                        entry.rank === 1 ? 'bg-yellow-100 text-yellow-700' :
                        entry.rank === 2 ? 'bg-slate-200 text-slate-700' :
                        entry.rank === 3 ? 'bg-amber-100 text-amber-800' :
                        'bg-neutral-100 text-neutral-600'
                     ]"
                   >
                      {{ entry.rank }}
                   </div>

                   <!-- User Info -->
                   <div class="flex items-center gap-3 flex-1 min-w-0">
                      <img :src="entry.avatar" alt="" class="h-12 w-12 rounded-full border-2 border-neutral-100 shrink-0">
                      <div class="flex-1 min-w-0">
                         <div class="font-medium text-neutral-900 flex items-center gap-2 flex-wrap">
                            <span class="truncate">{{ entry.name }}</span>
                            <Badge v-if="entry.is_me" variant="primary" class="shrink-0">You</Badge>
                         </div>
                         <div class="flex gap-1 mt-1 flex-wrap">
                            <Badge v-for="badge in entry.badges" :key="badge" variant="neutral" class="text-[10px] px-1.5 py-0.5">{{ badge }}</Badge>
                         </div>
                      </div>
                   </div>

                   <!-- Points -->
                   <div class="text-right shrink-0">
                      <div class="font-bold text-lg text-neutral-900">{{ entry.points }}</div>
                      <div class="text-xs text-neutral-500">points</div>
                   </div>
                </div>
             </div>
          </div>
       </div>
       
       <!-- Sidebar / My Stats -->
       <div class="space-y-4 md:space-y-6">
          <div class="bg-primary-600 rounded-xl p-6 text-white shadow-lg relative overflow-hidden">
             <!-- Background Pattern -->
             <div class="absolute top-0 right-0 opacity-10 transform translate-x-1/3 -translate-y-1/3">
                <Trophy class="w-32 h-32 md:w-48 md:h-48" />
             </div>
             
             <div class="relative z-10">
                <div class="text-primary-100 font-medium mb-1 text-sm">Your Rank</div>
                <div class="text-3xl md:text-4xl font-serif font-bold mb-4 md:mb-6">#3</div>
                
                <div class="grid grid-cols-2 gap-3 md:gap-4">
                   <div class="bg-white/10 rounded-lg p-3 backdrop-blur-sm">
                      <div class="text-xl md:text-2xl font-bold">980</div>
                      <div class="text-xs text-primary-200 uppercase">Total Points</div>
                   </div>
                   <div class="bg-white/10 rounded-lg p-3 backdrop-blur-sm">
                      <div class="text-xl md:text-2xl font-bold">12</div>
                      <div class="text-xs text-primary-200 uppercase">Badges</div>
                   </div>
                </div>
             </div>
          </div>

          <div class="bg-white rounded-xl border border-neutral-200 p-5 md:p-6 shadow-sm">
             <h3 class="font-bold text-neutral-900 mb-4 text-base md:text-lg">How to earn points?</h3>
             <ul class="space-y-3 text-sm text-neutral-600">
                <li class="flex items-center gap-3">
                   <div class="w-1.5 h-1.5 rounded-full bg-primary-500 shrink-0"></div>
                   <span>Complete a lesson (+10 pts)</span>
                </li>
                <li class="flex items-center gap-3">
                   <div class="w-1.5 h-1.5 rounded-full bg-primary-500 shrink-0"></div>
                   <span>Finish a daily habit (+2 pts)</span>
                </li>
                <li class="flex items-center gap-3">
                   <div class="w-1.5 h-1.5 rounded-full bg-primary-500 shrink-0"></div>
                   <span>Join a discussion (+5 pts)</span>
                </li>
                <li class="flex items-center gap-3">
                   <div class="w-1.5 h-1.5 rounded-full bg-primary-500 shrink-0"></div>
                   <span>7-day streak (+50 pts)</span>
                </li>
             </ul>
          </div>
       </div>
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import Badge from '@/Components/Common/Badge.vue';
import { Trophy } from 'lucide-vue-next';

defineProps({
    leaderboard: Array
});
</script>

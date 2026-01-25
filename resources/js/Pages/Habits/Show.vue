<template>
  <div style="padding: 20px; font-family: sans-serif;">
    <div style="margin-bottom: 20px;">
        <a href="/habits" style="color: blue; text-decoration: none;">&larr; Back to Habits</a>
    </div>

    <h1>{{ habit.title }}</h1>
    <p>{{ habit.description }}</p>

    <div style="display: flex; gap: 20px; margin: 20px 0;">
        <div style="background: #f0f0f0; padding: 15px; border-radius: 8px; flex: 1; text-align: center;">
            <h3>Current Streak</h3>
            <span style="font-size: 2em; color: #4CAF50;">{{ streaks.current }} days</span>
        </div>
        <div style="background: #f0f0f0; padding: 15px; border-radius: 8px; flex: 1; text-align: center;">
            <h3>Longest Streak</h3>
            <span style="font-size: 2em; color: #FFA500;">{{ streaks.longest }} days</span>
        </div>
    </div>

    <h2>History (Last 30 Days)</h2>
    <table border="1" cellpadding="10" style="width: 100%; border-collapse: collapse; background: white;">
        <thead>
            <tr>
                <th>Date</th>
                <th>Status</th>
                <th>Count</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="log in logs" :key="log.id">
                <td>{{ log.log_date }}</td>
                <td :style="{ color: log.status === 'done' ? 'green' : (log.status === 'skipped' ? 'orange' : 'black') }">
                    {{ log.status }}
                </td>
                <td>{{ log.completed_count }}</td>
                <td>{{ log.notes }}</td>
            </tr>
            <tr v-if="logs.length === 0">
                <td colspan="4" style="text-align: center;">No history yet.</td>
            </tr>
        </tbody>
    </table>
  </div>
</template>

<script setup>
defineProps({
  habit: Object,
  logs: Array,
  streaks: Object,
})
</script>

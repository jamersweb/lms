<template>
  <div style="padding: 20px; font-family: sans-serif; max-width: 1000px; margin: 0 auto;">
    <h1>🛡️ Moderation Panel</h1>
    
    <div style="display: flex; gap: 20px;">
        <!-- Recent Discussions -->
        <div style="flex: 2;">
            <h2>Recent Discussions</h2>
            <div v-for="discussion in discussions.data" :key="discussion.id" style="border: 1px solid #eee; padding: 15px; margin-bottom: 10px; border-radius: 4px; background: white;">
                <div style="display: flex; justify-content: space-between;">
                    <strong>{{ discussion.title }}</strong>
                    <span v-if="discussion.deleted_at" style="color: red; font-weight: bold;">[DELETED]</span>
                    <span v-else-if="discussion.status === 'closed'" style="color: orange; font-weight: bold;">[LOCKED]</span>
                    <span v-else style="color: green;">[OPEN]</span>
                </div>
                <p style="color: #666; font-size: 0.9em;">By {{ discussion.user.name }}</p>
                <div style="margin-top: 10px; display: flex; gap: 10px;">
                    <a :href="`/discussions/${discussion.id}`" target="_blank" style="color: blue;">View</a>
                    
                    <button v-if="!discussion.deleted_at" @click="handleAction('delete', discussion.id, 'discussion')" style="color: red;">Delete</button>
                    <button v-else @click="handleAction('restore', discussion.id, 'discussion')" style="color: green;">Restore</button>
                    
                    <button v-if="discussion.status === 'open'" @click="handleAction('lock', discussion.id, 'discussion')" style="color: orange;">Lock</button>
                    <button v-else @click="handleAction('unlock', discussion.id, 'discussion')" style="color: blue;">Unlock</button>
                </div>
            </div>
             <!-- Simple Pagination -->
            <div v-if="discussions.links && discussions.links.length > 3">
                <a v-for="link in discussions.links" :key="link.label" :href="link.url" v-html="link.label" style="margin-right: 5px;" :class="{ 'active': link.active }"></a>
            </div>
        </div>

        <!-- Action Log -->
        <div style="flex: 1; background: #f9f9f9; padding: 15px; border-radius: 8px; height: fit-content;">
            <h3>Recent Actions</h3>
            <div v-for="action in actions" :key="action.id" style="font-size: 0.85em; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">
                <strong>{{ action.moderator.name }}</strong>
                {{ action.action }}d
                {{ action.target_type }} #{{ action.target_id }}
                <div style="color: #888;">{{ new Date(action.created_at).toLocaleString() }}</div>
            </div>
             <div v-if="actions.length === 0" style="color: #888;">No actions logged.</div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'

defineProps({
  discussions: Object,
  actions: Array,
})

function handleAction(action, id, type) {
    if (!confirm(`Are you sure you want to ${action} this ${type}?`)) return;

    router.post('/admin/moderation/handle', {
        action: action,
        target_id: id,
        target_type: type,
        reason: 'Admin action via panel',
    })
}
</script>

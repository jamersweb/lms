<script setup>
import QuestionThread from '@/Components/Questions/QuestionThread.vue';
defineProps({ question: Object, messages: Array, admins: Array });
</script>
<template><QuestionThread :question="question" :messages="messages" :admins="admins" admin /></template>

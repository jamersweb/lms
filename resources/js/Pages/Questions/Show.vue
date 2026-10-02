<script setup>
import QuestionThread from '@/Components/Questions/QuestionThread.vue';
defineProps({ question: Object, messages: Array });
</script>
<template><QuestionThread :question="question" :messages="messages" /></template>

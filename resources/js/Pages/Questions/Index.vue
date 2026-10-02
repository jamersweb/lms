<script setup>
import QuestionList from '@/Components/Questions/QuestionList.vue';
defineProps({ questions: Object });
</script>
<template><QuestionList :questions="questions" /></template>

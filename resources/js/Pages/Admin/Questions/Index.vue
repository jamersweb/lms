<script setup>
import QuestionList from '@/Components/Questions/QuestionList.vue';
defineProps({ questions: Object, filters: Object });
</script>
<template><QuestionList :questions="questions" :filters="filters" admin /></template>

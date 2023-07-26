<template>
    <span :class="`buzz-badge badge rounded-pill buzz-badge-${priority}`">
        {{ label }}
    </span>
</template>

<script setup>
defineProps({
  label: String,
  priority: String
});
</script>

<style scoped>
.buzz-badge {
    width: 80px;
    text-transform: capitalize;
}
.buzz-badge-default {
    background-color: #ccc;
}
.buzz-badge-high {
    background-color: #dc3545;
}
.buzz-badge-medium {
    background-color: #ffc107;
    color: #343a40;
}
.buzz-badge-low {
    background-color: #17a2b8;
}
</style>

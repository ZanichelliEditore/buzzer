<template>
  <div>
    <channel-details :id="id" />
  </div>
</template>

<script setup>
import ChannelDetails from "../components/ChannelDetails/ChannelDetails.vue";
const props = defineProps({
  id: Number,
});
</script>

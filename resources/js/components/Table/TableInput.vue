<template>
  <div class="d-flex justify-content-end mb-4 mt-4 align-items-center">
    <div>
      <form class="form-inline me-2">
        <input
          id="inputQuery"
          type="text"
          class="form-control"
          :value="q"
          @input="$emit('update:q', $event.target.value)"
          placeholder="Search"
          aria-label="Query"
        />
      </form>
    </div>

    <div>
      <select
        class="form-select"
        id="inputLimitPage"
        :value="limit"
        @change="$emit('update:limit', $event.target.value)"
      >
        <option>10</option>
        <option>15</option>
        <option>20</option>
      </select>
    </div>
  </div>
</template>

<script setup>
defineProps({
  q: String,
  limit: Number,
});

defineEmits(["update:q", "update:limit"]);
</script>

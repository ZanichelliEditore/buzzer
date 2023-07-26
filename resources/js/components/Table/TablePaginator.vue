<template>
  <div v-if="objectsLength > 0" class="d-flex flex-column align-items-center flex-md-row justify-content-md-between">
    <div>
      {{ title }} from {{ (pagination.currentPage - 1) * limit + 1 }} to
      {{ (pagination.currentPage - 1) * limit + objectsLength }}
    </div>
    <nav aria-label="Page navigation example">
      <ul class="pagination">
        <li :class="[{ disabled: !pagination.prevPageUrl }]" class="page-item">
          <a
            class="page-link"
            href="#"
            @click.stop.prevent="changePage(--pagination.currentPage)"
            aria-label="Previous"
          >
            <span aria-hidden="true">&laquo;</span>
            <span class="sr-only ms-1 d-none d-md-inline">Previous</span>
          </a>
        </li>
        <li
          v-for="ele in pagination.pagesToRender"
          :key="ele"
          class="page-item"
          :class="{
            'font-weight-bold disabled': ele === pagination.currentPage,
          }"
        >
          <a class="page-link" href="#" @click.stop.prevent="changePage(ele)">
            {{ ele }}
          </a>
        </li>
        <li :class="[{ disabled: !pagination.nextPageUrl }]" class="page-item">
          <a
            class="page-link"
            href="#"
            @click.stop.prevent="changePage(++pagination.currentPage)"
            aria-label="Next"
          >
            <span class="sr-only me-1 d-none d-md-inline">Next</span>
            <span aria-hidden="true">&raquo;</span>
          </a>
        </li>
      </ul>
    </nav>
  </div>
</template>

<script setup>
defineProps({
  objectsLength: Number,
  title: String,
  pagination: Object,
  limit: Number,
});
const emit = defineEmits(["change-page"]);

function changePage(page) {
  emit("change-page", page);
}
</script>

<template>
  <div class="row">
    <div class="col-12 pt-3 pb-5">
      <div class="table-responsive">
        <table class="table table-bordered table-hover">
          <thead class="table-dark">
            <tr>
              <th
                v-for="(column, index) in columns"
                :class="{
                  pointer: column.orderby,
                  small_column: column.dimension == 'small',
                  medium_column: column.dimension == 'medium',
                  large_column: column.dimension == 'large',
                }"
                :key="index"
                data-sortable="true"
                scope="col"
                @click="column.orderby && orderBy(column.orderby)"
              >
                {{ column.label }}
                <i
                  :class="{
                    'fa fa-fw fa-sort-desc':
                      column.orderby && column.orderby === query.orderBy && query.order === 'DESC',
                    'fa fa-fw fa-sort-asc':
                      column.orderby && column.orderby === query.orderBy && query.order === 'ASC',
                    'fa fa-fw fa-sort': column.orderby && column.orderby !== query.orderBy,
                  }"
                ></i>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loadingObjects">
              <td :colspan="columns.length">Loading...</td>
            </tr>
            <tr v-if="!loadingObjects && objects.length === 0">
              <td :colspan="columns.length">No items to display</td>
            </tr>
            <tr
              v-if="!loadingObjects"
              :class="{ cell: detail }"
              v-for="object in objects"
              :key="object.id"
            >
              <td
                v-for="(column, index) in columns"
                :class="{
                  'text-center': column.type === 'alert' || column.type === 'status-badge',
                  'text-truncate': column.type === 'text',
                }"
                scope="row"
                :key="index"
              >
                <span v-if="column.type == 'text'" :title="object[column.field]">{{
                  object[column.field]
                }}</span>
                <Badge v-if="column.type == 'badge'" :label="object[column.field]" :priority="object[column.field]"/>
                <span class="col-md-1" v-if="column.type == 'alert' && object[column.field]"
                  ><i class="fa fa-exclamation-triangle fa-2x" aria-hidden="true"></i
                ></span>
                <button
                  v-if="column.type === 'buttonEdit'"
                  id="edit-button"
                  @click="editObject(object.id)"
                  class="btn btn-sm btn-primary"
                  data-bs-toggle="modal"
                  data-bs-target="#editJobModal"
                >
                  <i class="fa fa-pencil"></i>
                </button>
                <button
                  v-if="column.type === 'buttonRetry'"
                  id="retry-button"
                  @click="retryJob(object.id)"
                  class="btn btn-sm btn-success"
                >
                  <i class="fa fa-recycle"></i>
                </button>
                <button
                  v-if="column.type === 'buttonDelete'"
                  id="delete-button"
                  @click.prevent="showDelete(object.id)"
                  class="btn btn-sm btn-danger"
                  data-bs-toggle="modal"
                  data-bs-target="#confirmDeleteModal"
                >
                  <i class="fa fa-trash-o"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <NotificationComponent></NotificationComponent>
  </div>
</template>

<script>
import { defineComponent } from "vue";
import NotificationComponent from "../Notification/NotificationComponent.vue";
import Badge from "../Badge/Badge.vue";

export default defineComponent({
  props: {
    objects: {
      type: Array,
    },

    detail: {
      type: String,
      required: false,
    },

    columns: {
      type: Array,
      required: true,
    },

    query: {
      type: Object,
    },

    loadingObjects: {
      type: Boolean,
    },
  },

  components: { NotificationComponent, Badge },

  emits: ["retry-job", "set-item-to-delete", "order-by"],

  methods: {
    retryJob(id) {
      this.$emit("retry-job", id);
    },

    orderBy(column) {
      this.$emit("order-by", column);
    },

    editObject(id) {
      if (this.detail) {
        this.$router.push({ path: `/${this.detail}/${id}` });
      }
    },

    showDelete(id = null) {
      this.$emit("set-item-to-delete", id);
    },
  },
});
</script>
<style src="./Table.css" />

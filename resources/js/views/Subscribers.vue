<template>
  <div class="mx-5">
    <div class="container-fluid">
      <div class="form-group mt-3 mb-0">
        <h2 class="d-inline-flex align-items-center">Subscribers</h2>
      </div>
      <div class="mt-3 mb-5">
        <SubscriberRow @refresh-table="init"></SubscriberRow>
        <TableInput v-model:q="query.q" v-model:limit="query.limit"></TableInput>
      </div>
      <Table
        :columns="columns"
        :query="query"
        :objects="subscribers"
        :loading-objects="loadingObjects"
        @set-item-to-delete="setSubscriberId"
        @order-by="orderBy"
      ></Table>
      <TablePaginator
        :objects-length="subscribers.length"
        title="Subscribers"
        :pagination="pagination"
        :limit="query.limit"
        @change-page="changePage"
      ></TablePaginator>
      <base-modal
        type="confirmModal"
        :confirmFn="deleteSubscriber"
        title="Delete subscriber"
        confirmBtnLabel="YES"
        cancelBtnLabel="NO"
        message="Are you sure you want to delete the subscriber?"
        id="confirmDeleteModal"
      ></base-modal>
      <base-modal
        type="alert"
        confirmBtnLabel="OK"
        :message="alertData.outcome"
        :confirmFn="alertData.errorCallback"
        :isAnError="false"
        id="alertModal"
      ></base-modal>
    </div>
  </div>
</template>

<script>
import Table from "../components/Table/Table.vue";
import SubscriberRow from "../components/SubscriberRow/SubscriberRow.vue";
import TableInput from "../components/Table/TableInput.vue";
import TablePaginator from "../components/Table/TablePaginator.vue";
import { useTableQuery } from "../hook/tableQuery";
import { useAlertModal } from "../hook/alertModal";
import { defineComponent } from "vue";
import { useSubscribersStore } from "../store/subscribers";
import { storeToRefs } from "pinia";

export default defineComponent({
  components: {
    SubscriberRow,
    Table,
    TableInput,
    TablePaginator,
  },

  setup() {
    const subscribersStore = useSubscribersStore();
    const { orderBy, pagination, query, changePage, makePagination } = useTableQuery(
      subscribersStore.fetchSubscribers
    );
    const { alertData, openAlertModal } = useAlertModal();
    const { subscribers, loadingObjects, meta, links } = storeToRefs(subscribersStore);

    return {
      subscribersStore,
      subscribers,
      orderBy,
      pagination,
      query,
      changePage,
      loadingObjects,
      meta,
      links,
      makePagination,
      alertData,
      openAlertModal,
    };
  },

  async mounted() {
    await this.init();
  },

  data() {
    return {
      columns: [
        {
          label: "Id",
          field: "id",
          orderby: "id",
          type: "text",
        },
        {
          label: "Name",
          field: "name",
          orderby: "name",
          type: "text",
        },
        {
          label: "Host",
          field: "host",
          orderby: "host",
          type: "text",
        },
        {
          type: "buttonDelete",
          dimension: "small",
        },
      ],
      subscriberId: null,
    };
  },

  methods: {
    async init() {
        await this.subscribersStore.fetchSubscribers(this.query)
            .catch(error => {
                this.emitter.emit("newNotification", {
                    message: error.message,
                    type: "ERROR",
                });
            });
      this.makePagination(this.meta, this.links);
    },
    setSubscriberId(id) {
      this.subscriberId = id;
    },
    deleteSubscriber() {
      this.subscribersStore
        .deleteSubscriber(this.subscriberId)
        .then(() => {
          this.emitter.emit("newNotification", {
            message: "Subscriber deleted",
            type: "SUCCESS",
          });
          this.subscribersStore.fetchSubscribers(this.query);
          this.makePagination(this.meta, this.links);
        })
        .catch(err => {
          this.openAlertModal(err.response, true);
        });
    },
  },
});
</script>

<template>
  <div class="mx-5">
    <div class="container-fluid">
      <div class="form-group mt-3 mb-0">
        <h2 class="d-inline-flex align-items-center">Publishers</h2>
      </div>
      <div class="mt-3 mb-5">
        <PublisherRow @refresh-table="init"></PublisherRow>
        <TableInput v-model:q="query.q" v-model:limit="query.limit"></TableInput>
      </div>
      <Table
        :columns="columns"
        :query="query"
        :objects="publishers"
        :loading-objects="loadingObjects"
        @set-item-to-delete="setPublisherId"
        @order-by="orderBy"
      ></Table>
      <TablePaginator
        :objects-length="publishers.length"
        title="Publishers"
        :pagination="pagination"
        :limit="query.limit"
        @change-page="changePage"
      ></TablePaginator>
      <base-modal
        type="confirmModal"
        :confirmFn="deletePublisher"
        title="Delete publisher"
        confirmBtnLabel="YES"
        cancelBtnLabel="NO"
        message="Are you sure you want to delete the publisher?"
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
import TableInput from "../components/Table/TableInput.vue";
import TablePaginator from "../components/Table/TablePaginator.vue";
import PublisherRow from "../components/PublisherRow/PublisherRow.vue";
import { useTableQuery } from "../hook/tableQuery";
import { useAlertModal } from "../hook/alertModal";
import { defineComponent } from "vue";
import { usePublisherStore } from "../store/publisher";
import { storeToRefs } from "pinia";

export default defineComponent({
  components: {
    PublisherRow,
    Table,
    TableInput,
    TablePaginator,
  },

  setup() {
    const publisherStore = usePublisherStore();
    const { orderBy, pagination, query, changePage, makePagination } = useTableQuery(
      publisherStore.fetchPublishers
    );
    const { alertData, openAlertModal } = useAlertModal();
    const { publishers, loadingObjects, meta, links } = storeToRefs(publisherStore);

    return {
      publisherStore,
      publishers,
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
          label: "Username",
          field: "username",
          orderby: "username",
          type: "text",
        },
        {
          type: "buttonDelete",
          dimension: "small",
        },
      ],
      publisherId: null,
    };
  },

  methods: {
    async init() {
      await this.getPublishers();
    },
    async getPublishers() {
      await this.publisherStore.fetchPublishers(this.query).catch(error => {
        this.emitter.emit("newNotification", {
          message: error.message,
          type: "ERROR",
        });
      });
      this.makePagination(this.meta, this.links);
    },
    setPublisherId(id) {
      this.publisherId = id;
    },
    deletePublisher() {
      this.publisherStore
        .deletePublisher(this.publisherId)
        .then(res => {
          this.emitter.emit("newNotification", {
            message: "Publisher deleted",
            type: "SUCCESS",
          });
          this.getPublishers();
        })
        .catch(err => {
          this.openAlertModal(err.response);
        });
    },
  },
});
</script>

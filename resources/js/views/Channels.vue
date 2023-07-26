<template>
  <div class="mx-5">
    <div class="container-fluid">
      <div class="form-group mt-3 mb-0">
        <h2 class="d-inline-flex align-items-center">Channels</h2>
      </div>
      <ChannelRow
        @save-channel="handleSaveChannel"
        :success="success"
        :saving="saving"
      ></ChannelRow>
      <TableInput v-model:q="query.q" v-model:limit="query.limit"></TableInput>
      <Table
        detail="channels"
        :columns="columns"
        :query="query"
        :objects="channels"
        :loading-objects="loadingObjects"
        @set-item-to-delete="setChannelId"
        @order-by="orderBy"
      ></Table>
      <TablePaginator
        :objects-length="channels.length"
        title="Channels"
        :pagination="pagination"
        :limit="query.limit"
        @change-page="changePage"
      ></TablePaginator>
      <base-modal
        type="confirmModal"
        :confirmFn="deleteChannel"
        title="Delete channel"
        confirmBtnLabel="YES"
        cancelBtnLabel="NO"
        message="Are you sure you want to delete the channel?"
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
import { defineComponent } from "vue";
import { useChannelsStore } from "../store/channels";
import { useTableQuery } from "../hook/tableQuery";
import { useAlertModal } from "../hook/alertModal";
import { storeToRefs } from "pinia";
import TablePaginator from "../components/Table/TablePaginator.vue";
import ChannelRow from "../components/ChannelRow/ChannelRow.vue";

export default defineComponent({
  components: {
    Table,
    TableInput,
    TablePaginator,
    ChannelRow,
  },

  setup() {
    const channelsStore = useChannelsStore();
    const { orderBy, pagination, query, changePage, makePagination } = useTableQuery(
      channelsStore.fetchObjects
    );
    const { alertData, openAlertModal } = useAlertModal();
    const { channels, loadingObjects, meta, links } = storeToRefs(channelsStore);

    return {
      channelsStore,
      channels,
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
    await this.getChannels();
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
          label: "priority",
          field: "priority",
          orderby: "priority",
          type: "badge",
        },
        {
          type: "buttonEdit",
          dimension: "small",
        },
        {
          type: "buttonDelete",
          dimension: "small",
        },
      ],
      channelId: null,
      success: false,
      saving: false,
    };
  },

  methods: {
    async getChannels() {
      await this.channelsStore.fetchObjects(this.query).catch(error => {
        this.emitter.emit("newNotification", {
          message: error.message,
          type: "ERROR",
        });
      });
      this.makePagination(this.meta, this.links);
    },
    setChannelId(id) {
      this.channelId = id;
    },
    handleSaveChannel(payload) {
      this.saving = true;
      this.channelsStore
        .saveChannel(payload)
        .then(res => {
          this.getChannels();
          this.emitter.emit("newNotification", {
            message: "Channel saved",
            type: "SUCCESS",
          });
          this.success = true;
        })
        .catch(err => {
          this.emitter.emit("newNotification", {
            message: err.response,
            type: "ERROR",
          });
        })
        .finally(() => (this.saving = false));
    },
    deleteChannel() {
      this.channelsStore
        .deleteChannel(this.channelId)
        .then(res => {
          this.emitter.emit("newNotification", {
            message: "Channel deleted",
            type: "SUCCESS",
          });
          this.getChannels();
        })
        .catch(err => {
          this.openAlertModal(err.response, true);
        });
    },
  },
});
</script>

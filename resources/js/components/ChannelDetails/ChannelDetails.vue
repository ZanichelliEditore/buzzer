<template>
  <div class="container">
    <div class="mt-2">
      <div class="row">
        <div class="col-12 pt-5 d-flex justify-content-between align-items-center">
          <div class="col-7 pl-0 d-flex align-items-center mb-5">
            <h1 class="d-inline-block text-truncate me-3">Channel: {{ form.name }}</h1>
            <Badge :label="form.priority" :priority="form.priority" />
          </div>
        </div>
      </div>
      <div v-if="!loading" class="row">
        <transition name="fade">
          <section class="col-12">
            <form id="firstSection">
              <div>
                <div class="d-flex align-items-center justify-content-between">
                  <h2>Subscribers</h2>

                  <z-toggle-button
                    label="Add Subscribers"
                    @toggleClick="handleToggleClick('showSubscribers')"
                  ></z-toggle-button>
                </div>

                <div class="row" v-if="showSubscribers">
                  <ChannelSubscriptionSubscriber
                    :channel_id="id"
                    @refresh-subscribers-table="subscribersStore.getSubscribersByChannel(id)"
                  ></ChannelSubscriptionSubscriber>
                </div>
                <Table
                  title="Subscribers"
                  :columns="subscribersColumns"
                  :objects="subscribersByChannel"
                  :query="query"
                  :loading-objects="loadingSubscribers"
                  @set-item-to-delete="handleDeleteSubscriber"
                ></Table>
              </div>
              <div>
                <div class="d-flex align-items-center justify-content-between">
                  <h2>Publishers</h2>

                  <z-toggle-button
                    label="Add Publishers"
                    @toggleClick="handleToggleClick('showPublishers')"
                  ></z-toggle-button>
                </div>

                <div class="row my-4" v-if="showPublishers">
                  <ChannelSubscriptionPublisher
                    :channel_id="id"
                    @refresh-publishers-table="publisherStore.getPublishersByChannel(id)"
                  ></ChannelSubscriptionPublisher>
                </div>
                <Table
                  title="Publishers"
                  :columns="publishersColumns"
                  :objects="publishersByChannel"
                  :query="query"
                  :loading-objects="loadingPublishers"
                  @set-item-to-delete="handleDeletePublisher"
                ></Table>
              </div>
            </form>
          </section>
        </transition>
      </div>
    </div>

    <base-modal
      type="confirmModal"
      :confirmFn="deleteFn"
      :title="modalTitle"
      confirmBtnLabel="YES"
      cancelBtnLabel="NO"
      :message="modalMessage"
      id="confirmDeleteModal"
    ></base-modal>
  </div>
</template>

<script>
import { defineComponent } from "vue";
import { useChannelsStore } from "../../store/channels";
import { usePublisherStore } from "../../store/publisher";
import { useSubscribersStore } from "../../store/subscribers";
import { useChannelSubscriberStore } from "../../store/channelSubscriber";
import { useChannelPublisherStore } from "../../store/channelPublisher";
import { storeToRefs } from "pinia";
import { useTableQuery } from "../../hook/tableQuery";
import ChannelSubscriptionSubscriber from "../ChannelSubscriptionSubscriber/ChannelSubscriptionSubscriber.vue";
import ChannelSubscriptionPublisher from "../ChannelSubscriptionPublisher/ChannelSubscriptionPublisher.vue";
import Table from "../Table/Table.vue";
import Badge from "../Badge/Badge.vue";

export default defineComponent({
  components: {
    ChannelSubscriptionSubscriber,
    ChannelSubscriptionPublisher,
    Table,
    Badge,
  },

  setup() {
    const channelsStore = useChannelsStore();
    const publisherStore = usePublisherStore();
    const subscribersStore = useSubscribersStore();
    const channelSubscriberStore = useChannelSubscriberStore();
    const channelPublisherStore = useChannelPublisherStore();
    const { query } = useTableQuery();

    const {
      publishersByChannel,
      loadingObjects: { loadingPublishers },
    } = storeToRefs(publisherStore);
    const {
      subscribersByChannel,
      loadingObjects: { loadingSubscribers },
    } = storeToRefs(subscribersStore);

    return {
      channelsStore,
      publisherStore,
      publishersByChannel,
      subscribersStore,
      subscribersByChannel,
      channelSubscriberStore,
      channelPublisherStore,
      loadingPublishers,
      loadingSubscribers,
      query,
    };
  },

  data() {
    return {
      baseRedirectUrl: "/channels",
      channel: {},
      loading: false,
      saving: false,
      esito: null,
      showSubscribers: false,
      showPublishers: false,
      form: {
        id: "",
        name: "",
        priority: "",
      },
      subscribersColumns: [
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
          label: "Username",
          field: "username",
          orderby: "username",
          type: "text",
        },
        {
          label: "Authentication",
          field: "authentication",
          orderby: "authentication",
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
      publishersColumns: [
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
      modalTitle: "",
      modalMessage: "",
      subscriberId: null,
      publisherId: null,
    };
  },

  props: {
    id: {
      type: Number,
      required: true,
    },
  },

  async mounted() {
    await this.init();
  },

  computed: {
    deleteFn() {
      return this.subscriberId ? this.deleteSubscriber : this.deletePublisher;
    },
  },

  methods: {
    handleToggleClick(form) {
      this[form] = !this[form];
    },

    async init() {
      await this.fetchChannel();
      await this.subscribersStore.getSubscribersByChannel(this.id);
      await this.publisherStore.getPublishersByChannel(this.id);
    },

    async fetchChannel() {
      this.loading = true;
      await this.channelsStore
        .fetchChannel(this.id)
        .then(res => {
          if (res.status === 200) {
            this.channel = res.data.data;
            this.form.id = this.channel["id"];
            this.form.name = this.channel["name"];
            this.form.priority = this.channel["priority"];
          } else {
            this.openAlertModal(
              "An unexpected error occurred, channel currently unavailable",
              true,
              this.onReset
            );
          }
          this.loading = false;
        })
        .catch(err => {
          this.openAlertModal(err.response, true, this.onReset);
        });
    },

    openAlertModal(esito, isError = false, callback = null) {
      this.esito = esito;
      this.errorFound = isError;
      this.showAlert = true;
      this.errorCallback = callback;
    },

    onReset() {
      this.$router.push({ path: this.baseRedirectUrl });
    },
    handleDeleteSubscriber(id) {
      this.subscriberId = id;
      this.modalTitle = "Unsubscribe";
      this.modalMessage = "Are you sure you want to unsubscribe the subscriber from the channel?";
    },
    handleDeletePublisher(id) {
      this.publisherId = id;
      this.modalTitle = "Remove publisher";
      this.modalMessage = "Are you sure you want to remove the publisher from the channel?";
    },
    async deleteSubscriber() {
      try {
        await this.channelSubscriberStore.deleteChannelSubscriber(this.subscriberId);
        this.emitter.emit("newNotification", {
          message: "Subscriber unsubscribed",
          type: "SUCCESS",
        });
        await this.subscribersStore.getSubscribersByChannel(this.id);
      } catch (err) {
        this.emitter.emit("newNotification", {
          message: err.response,
          type: "ERROR",
        });
      }
    },
    async deletePublisher() {
      try {
        await this.channelPublisherStore.deleteChannelPublisher(this.publisherId, this.id);
        this.emitter.emit("newNotification", {
          message: "Publisher removed",
          type: "SUCCESS",
        });
        await this.publisherStore.getPublishersByChannel(this.id);
      } catch (err) {
        this.emitter.emit("newNotification", {
          message: err.response,
          type: "ERROR",
        });
      }
    },
  },
});
</script>

<style src="./ChannelDetails.css" />

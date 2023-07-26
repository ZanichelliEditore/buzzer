<template>
  <div class="row align-items-start">
    <div class="d-flex col-md-6 col-xl-4 me-3">
      <z-combobox
        inputid="publisher-select"
        class="w-100"
        :items="localPublishersList"
        hassearch="true"
        searchlabel="Publisher"
        searchplaceholder="Search"
        noresultslabel="No items to display"
        :label="selectedPublisher ? selectedPublisher.name : 'Select a publisher'"
        closesearchtext="CLOSE"
        :maxcheckableitems="1"
        @comboboxChange="comboboxUpdate"
        isopen="false"
      >
      </z-combobox>
    </div>
    <div class="col-6 mt-4 mt-xl-0">
      <z-button
        variant="primary"
        :disabled="!selectedPublisher || loading"
        title="Save"
        @click.prevent="onSubscribeToChannel()"
        >SAVE</z-button
      >
    </div>
  </div>
</template>

<script>
import { usePublisherStore } from "../../store/publisher";
import { defineComponent } from "vue";
import { storeToRefs } from "pinia";

export default defineComponent({
  setup() {
    const publishersStore = usePublisherStore();
    const { publishers } = storeToRefs(publishersStore);

    return {
      publishersStore,
      publishers,
    };
  },

  emits: ["refresh-publishers-table"],

  data() {
    return {
      selectedPublisher: null,
      localPublishersList: [],
      loading: false,
    };
  },

  props: {
    channel_id: {
      required: true,
    },
  },

  mounted() {
    this.getLocalPublishersList();
  },

  methods: {
    setLocalPublishersList() {
      this.localPublishersList = this.publishers.map(publisher => ({
        id: String(publisher.id),
        name: `${publisher.name}`,
        checked: false,
      }));
    },

    onSubscribeToChannel() {
      this.loading = true;
      let payload = {
        channel_id: this.channel_id,
      };

      this.publishersStore
        .postPublisherToChannel({
          payload,
          publisherId: this.selectedPublisher.id,
        })
        .then(res => {
          if (res.status === 201) {
            this.$emit("refresh-publishers-table");
          }
          this.resetForm();
        })
        .catch(err => {
          this.loading = false;
          this.emitter.emit("newNotification", {
            message: err.response.data.message? err.response.data.message : "Error while associating publisher to channel",
            type: "ERROR",
          });
        });
    },

    getLocalPublishersList() {
      this.loading = true;
      this.publishersStore
        .fetchPublishers()
        .then(() => {
          this.loading = false;
          this.setLocalPublishersList();
        })
        .catch(err => {
          this.loading = false;
          this.emitter.emit("newNotification", {
            message: "Error while retrieving publisher",
            type: "ERROR",
          });
        });
    },

    comboboxUpdate(event) {
      let items = event.detail.items;
      let selectedItem = items.find(item => item.checked == true);
      this.selectedPublisher = selectedItem;
      if (this.selectedPublisher) {
        this.selectedPublisher.name = selectedItem?.name.replace(/(.{30})..+/, "$1…");
      }
    },

    resetForm() {
      this.loading = false;
      this.selectedPublisher = null;
      this.setLocalPublishersList();
    },
  },
});
</script>

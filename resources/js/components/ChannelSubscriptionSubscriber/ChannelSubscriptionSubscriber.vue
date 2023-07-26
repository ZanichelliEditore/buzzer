<template>
  <div class="row">
    <div class="d-flex col-md-6 col-xl-4 mt-2 me-3 mb-4">
      <z-combobox
        inputid="subscriber-select"
        class="mt-4 w-100"
        :items="localSubscribersList"
        hassearch="true"
        searchlabel="Subscriber"
        searchplaceholder="Search"
        noresultslabel="No items to display"
        :label="selectedSubscriber ? selectedSubscriber.name : 'Select a subscriber'"
        closesearchtext="CLOSE"
        :maxcheckableitems="1"
        @comboboxChange="comboboxUpdate"
        isopen="false"
      >
      </z-combobox>
    </div>
    <div class="d-flex flex-column col-12 col-md-6 col-xl-3 mt-2 mr-3">
      <z-input
        id="endpoint-input"
        class="w-100"
        type="text"
        label="Endpoint"
        :value="endpoint"
        placeholder="example/endpoint"
        @inputChange="endpointUpdate"
      ></z-input>
    </div>
    <div class="d-flex flex-column col-12 col-md-3 col-xl-2 mt-2 mr-3">
      <z-select
        class="w-100"
        label="Authentication"
        placeholder="Select option"
        :items="autTypesList"
        @optionSelect="authTypeUpdate"
      ></z-select>
    </div>
    <div v-show="authType == 'BASIC' || authType == 'OAUTH2'" class="col-12 col-md-6 col-xl-3">
      <div class="d-flex flex-column col-12 mt-2 mr-3">
        <z-input
          id="usename-input"
          class="w-100"
          type="text"
          :value="username"
          :label="authType === 'BASIC' ? 'Username' : 'Client Id'"
          :placeholder="authType === 'BASIC' ? 'Insert Username' : 'Insert Client Id'"
          @inputChange="usernameUpdate"
        ></z-input>
      </div>
    </div>
    <div v-show="authType == 'BASIC' || authType == 'OAUTH2'" class="col-12 col-md-6 col-xl-3">
      <div class="d-flex flex-column col-12 mt-2 mr-3">
        <z-input
          class="w-100"
          id="usename-input"
          type="text"
          :value="password"
          :label="authType === 'BASIC' ? 'Password' : 'Client Secret'"
          :placeholder="authType === 'BASIC' ? 'Insert Password' : 'Insert Client Secret'"
          @inputChange="passwordUpdate"
        ></z-input>
      </div>
    </div>
    <div class="col-12">
      <z-button
        variant="primary"
        :disabled="!validatedForm || loading"
        title="Save"
        class="mb-4"
        @click.prevent="onSubscribeToChannel()"
        >Save</z-button
      >
    </div>
  </div>
</template>

<script>
import { useSubscribersStore } from "../../store/subscribers";
import { storeToRefs } from "pinia";
import { defineComponent } from "vue";

export default defineComponent({
  setup() {
    const subscribersStore = useSubscribersStore();

    const { subscribers } = storeToRefs(subscribersStore);

    return {
      subscribers,
      subscribersStore,
    };
  },

  emits: ["refresh-subscribers-table"],

  data() {
    return {
      endpoint: "",
      selectedSubscriber: "",
      authType: "",
      username: "",
      password: "",
      loading: false,
      localSubscribersList: [],
      autTypesList: [],
    };
  },

  props: {
    channel_id: {
      required: true,
    },
  },

  mounted() {
    this.init();
  },

  computed: {
    validatedForm() {
      return (
        this.selectedSubscriber &&
        this.endpointState &&
        this.selectedSubscriber &&
        this.authType &&
        this.credentialsState
      );
    },

    endpointState() {
      return this.endpoint.trim() && this.endpoint.length <= 50;
    },

    credentialsState() {
      if (this.authType == "BASIC" || this.authType == "OAUTH2") {
        return (
          this.username.trim() &&
          this.password.trim() &&
          this.username.length <= 50 &&
          this.password.length <= 50
        );
      }
      return true;
    },
  },

  methods: {
    init() {
      this.getLocalSubscribersList();
      this.setAuthTypesList();
    },

    onSubscribeToChannel() {
      if (!this.validatedForm) return;

      this.loading = true;
      let payload = {
        channel_id: this.channel_id,
        endpoint: this.endpoint,
        authentication: this.authType,
      };
      if (this.authType == "BASIC" || this.authType == "OAUTH2") {
        payload.username = this.username;
        payload.password = this.password;
      }
      this.subscribersStore
        .postSubscriberToChannel({
          payload,
          subscriberId: this.selectedSubscriber.id,
        })
        .then(res => {
          if (res.status === 201) {
            this.$emit("refresh-subscribers-table");
          }
          this.resetForm();
        })
        .catch(err => {
          this.loading = false;
          this.emitter.emit("newNotification", {
            message: "Error while creating subscription",
            type: "ERROR",
          });
        });
    },

    getLocalSubscribersList() {
      this.loading = true;
      this.subscribersStore
        .fetchSubscribers()
        .then(() => {
          this.loading = false;
          this.setLocalSubscribersList();
        })
        .catch(err => {
          this.loading = false;
          this.emitter.emit("newNotification", {
            message: "Error while retrieving subscriber",
            type: "ERROR",
          });
        });
    },

    setLocalSubscribersList() {
      this.localSubscribersList = this.subscribers.map(subscriber => ({
        id: String(subscriber.id),
        name: `${subscriber.name} - ${subscriber.host}`,
        checked: false,
      }));
    },

    setAuthTypesList() {
      this.autTypesList = [
        { id: "NONE", name: "None", selected: false },
        { id: "BASIC", name: "Basic", selected: false },
        { id: "OAUTH2", name: "Oauth2", selected: false },
      ];
    },

    resetForm() {
      this.loading = false;
      this.selectedSubscriber = null;
      this.setLocalSubscribersList();
      this.authType = "";
      this.setAuthTypesList();
      this.endpoint = "";
      this.username = "";
      this.password = "";
    },

    comboboxUpdate(event) {
      let items = event.detail.items;
      let selectedItem = items.find(item => item.checked == true);
      this.selectedSubscriber = selectedItem;
      this.selectedSubscriber.name = selectedItem.name.replace(/(.{30})..+/, "$1…");
    },

    endpointUpdate(event) {
      this.endpoint = event.detail.value;
    },

    authTypeUpdate(event) {
      this.authType = event.detail.selected;
    },

    usernameUpdate(event) {
      this.username = event.detail.value;
    },

    passwordUpdate(event) {
      this.password = event.detail.value;
    },
  },
});
</script>

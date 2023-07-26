<template>
  <div>
    <form class="row">
      <div class="col-12 col-md-6 col-xl-3 mt-2 me-3">
        <div>
          <label class="mb-0 me-3" for="name-input">Name</label>
        </div>
        <div>
          <input
            id="name-input"
            type="text"
            name="name"
            v-model="name"
            :class="'form-control  ' + (nameState ? 'is-valid' : '')"
          />
        </div>
      </div>
      <div class="col-12 col-md-6 col-xl-3 mt-2 me-3">
        <div>
          <label class="mb-0 me-3" for="host-input">Host</label>
        </div>
        <div>
          <input
            id="host-input"
            type="text"
            name="host"
            v-model="host"
            :placeholder="'https://example.com/'"
            :class="'form-control  ' + (hostState ? 'is-valid' : '')"
          />
        </div>
      </div>
      <div class="col-2 col-xl-1 mt-2 me-3 d-flex align-items-end">
        <button
          :disabled="saving || validateForms"
          data-placement="top"
          data-toggle="tooltip"
          title="Save"
          :class="saving || validateForms ? 'btn-secondary' : 'btn-success'"
          class="btn w-100"
          @click.prevent="onSaveSubscriber()"
        >
          Save
        </button>
      </div>
    </form>
  </div>
</template>

<script>
import { defineComponent } from "vue";
import { useSubscribersStore } from "../../store/subscribers";

export default defineComponent({
  setup() {
    const subscribersStore = useSubscribersStore();

    return {
      subscribersStore,
    };
  },

  emits: ["refresh-table"],

  data() {
    return {
      name: "",
      host: "",
      saving: false,
    };
  },

  computed: {
    validateForms() {
      return !this.nameState || !this.hostState;
    },

    nameState() {
      if (!this.name.length) {
        return;
      }
      return !!this.name.length && this.name.length < 50;
    },

    hostState() {
      if (!this.host.length) {
        return;
      }
      var re = /^https?:\/\/([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/;
      return re.test(this.host) && this.host.length < 150;
    },
  },

  methods: {
    onSaveSubscriber() {
      this.saving = true;
      const payload = {
        name: this.name,
        host: this.host,
      };

      this.subscribersStore
        .saveSubscriber({
          payload,
        })
        .then(res => {
          this.$emit("refresh-table");
          this.emitter.emit("newNotification", {
            message: "Subscriber created",
            type: "SUCCESS",
          });
          this.name = "";
          this.host = "";
          this.saving = false;
        })
        .catch(err => {
          this.emitter.emit("newNotification", {
            message: err.response,
            type: "ERROR",
          });
          this.saving = false;
        });
    },
  },
});
</script>

<style src="./SubscriberRow.css" scoped></style>

<template>
  <div>
    <div>
      <form class="row align-items-end">
        <div class="d-flex flex-column col-12 col-md-6 col-xl-3 mt-2 mr-3">
          <div>
            <label class="mb-0 mr-3" for="name-input">Name</label>
          </div>
          <div>
            <input
              class="form-control"
              id="name-input"
              type="text"
              name="name"
              v-model="name"
              :state="nameState"
            />
          </div>
        </div>
        <div class="d-flex flex-column col-12 col-md-6 col-xl-3 mt-2 mr-3">
          <div>
            <label class="mb-0 mr-3" for="host-input">Host</label>
          </div>
          <div>
            <input
              class="form-control"
              id="host-input"
              type="text"
              name="host"
              v-model="host"
              :state="hostState"
            />
          </div>
        </div>
        <div class="d-flex flex-column col-12 col-md-6 col-xl-3 mt-2 mr-3">
          <div>
            <label class="mb-0 mr-3" for="username-input">Username</label>
          </div>
          <div>
            <input
              class="form-control"
              id="username-input"
              type="text"
              name="username"
              v-model="username"
              :state="usernameState"
            />
          </div>
        </div>
        <div class="d-flex flex-column col-2 col-xl-1 mt-2 mr-3">
          <button
            :disabled="saving || validateForms"
            data-placement="top"
            data-toggle="tooltip"
            title="Save"
            :class="saving || validateForms ? 'btn-secondary' : 'btn-success'"
            class="btn"
            @click.prevent="onSavePublisher()"
          >
            Save
          </button>
        </div>
      </form>
    </div>
    <base-modal
      type="confirmModal"
      :confirmFn="togglePasswordModal"
      title="Password"
      confirmBtnLabel="OK"
      :message="modalMessage"
      id="passwordModal"
    ></base-modal>
  </div>
</template>

<script>
import { defineComponent } from "vue";
import { usePublisherStore } from "../../store/publisher";
import { Modal } from "bootstrap";

import BaseModal from "../BaseModal/BaseModal.vue";
export default defineComponent({
  setup() {
    const publisherStore = usePublisherStore();

    return {
      publisherStore,
    };
  },

  components: {
    BaseModal,
  },

  emits: ["refresh-table"],

  data() {
    return {
      url: "/admin/publishers",
      name: "",
      host: "",
      saving: false,
      username: "",
      password: "",
    };
  },

  computed: {
    validateForms() {
      return !this.nameState || !this.hostState || !this.usernameState;
    },

    nameState() {
      if (!this.name.length) {
        return;
      }
      return !!this.name.length && this.name.length < 50;
    },

    modalMessage() {
      return `The password is ${this.password}`;
    },

    hostState() {
      if (!this.host.length) {
        return;
      }
      var re = /^https?:\/\/([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/;
      return re.test(this.host) && !!this.host.length && this.host.length < 50;
    },

    usernameState() {
      if (!this.username.length) {
        return;
      }
      return !!this.username.length && this.username.length < 50;
    },
  },

  methods: {
    dec2hex(dec) {
      return ("0" + dec.toString(16)).substr(-2);
    },

    generatePassword() {
      var result = "";
      var characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
      var charactersLength = characters.length;
      for (var i = 0; i < 10; i++) {
        result += characters.charAt(Math.floor(Math.random() * charactersLength));
      }
      return result;
    },

    togglePasswordModal(action = "hide") {
      const alertModal = new Modal(document.getElementById("passwordModal"));
      action === "show" ? alertModal.show() : alertModal.hide();
    },

    async onSavePublisher() {
      this.saving = true;
      this.password = this.generatePassword();
      const payload = {
        name: this.name,
        host: this.host,
        username: this.username,
        password: this.password,
      };

      await this.publisherStore
        .savePublisher(this.url, payload)
        .then(res => {
          this.$emit("refresh-table");
          this.emitter.emit("newNotification", {
            message: "Publisher created",
            type: "SUCCESS",
          });
          this.togglePasswordModal("show");
          this.name = "";
          this.host = "";
          this.username = "";
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

<style src="./PublisherRow.css" scoped></style>

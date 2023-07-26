<template>
  <div
    v-if="currentNotification"
    id="notification-container"
    :class="
      getBackgroundByType(currentNotification.type) +
      ' d-flex flex-column position-fixed ms-auto me-3 col-10 col-md-4 col-lg-3 px-3 py-3 text-white align-items-center ' +
      (currentNotification.type === 'ERROR' ? 'animation-error' : 'animation-success')
    "
  >
    <div class="d-flex w-100 justify-content-between align-items-start">
      <div>
        <h3 v-if="currentNotification.type === 'SUCCESS'" class="text-white">Success</h3>
        <h3 v-if="currentNotification.type === 'ERROR'" class="text-white">Error</h3>
        <h3 v-if="currentNotification.type === 'INFO'" class="text-white">Info</h3>
      </div>
      <button
        v-show="currentNotification.type === 'ERROR'"
        class="btn btn-close"
        aria-label="Close"
        @click="closeNotification"
      ></button>
    </div>
    <p class="w-100">{{ currentNotification.message }}</p>
  </div>
</template>

<script>
import { defineComponent } from "@vue/runtime-core";

export default defineComponent({
  data() {
    return {
      processing: false,
      notifications: [],
      currentNotification: null,
      types: ["SUCCESS", "ERROR", "INFO"],
    };
  },

  created() {
    this.emitter.on("newNotification", notification => {
      if (notification.hasOwnProperty("message") && notification.hasOwnProperty("type")) {
        notification.message = this.parseMessage(notification.message);
        this.notifications.push(notification);
      }
    });
  },

  methods: {
    processNotification() {
      if (this.notifications.length && !this.processing) {
        this.processing = true;
        this.currentNotification = this.notifications[0];
        if (this.currentNotification.type === "ERROR") {
          return;
        }

        return setTimeout(() => {
          this.processing = false;
          this.currentNotification = null;

          setTimeout(() => {
            this.notifications.shift();
          }, 200);
        }, 3000);
      }
    },

    closeNotification() {
      this.processing = false;
      this.currentNotification = null;

      setTimeout(() => {
        this.notifications.shift();
      }, 200);
    },

    getBackgroundByType(type) {
      switch (type) {
        case "SUCCESS":
          return "bg-success";
        case "ERROR":
          return "bg-danger";
        case "INFO":
          return "bg-info";
        default:
          return "bg-success";
      }
    },

    parseMessage(message) {
      if (message == null) {
        return null;
      }

      if (typeof message === "string") {
        return message;
      }

      if (message.data && message.data.errors) {
        let msg = "";

        for (let k in message.data.errors) {
          msg += message.data.errors[k] + "\n";
        }
        return msg;
      }

      return "An unexpected error occurred";
    },
  },

  watch: {
    notifications: {
      handler() {
        this.processNotification();
      },
      deep: true,
    },
  },
});
</script>

<style src="./NotificationComponent.css" scoped></style>

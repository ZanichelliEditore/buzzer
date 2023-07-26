<template>
  <div class="modal" tabindex="-1" :id="id">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-light">{{ title }}</h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          ></button>
        </div>
        <div class="modal-body">
          <h3 class="text-center fw-light">{{ computedMessage }}</h3>
        </div>
        <div class="modal-footer justify-content-around" v-if="type !== 'alert'">
          <button data-bs-dismiss="modal" class="btn btn-outline-success col-5" @click="confirmFn">
            {{ confirmBtnLabel }}
          </button>

          <button
            v-if="cancelBtnLabel"
            class="btn btn-outline-danger col-5"
            data-bs-dismiss="modal"
          >
            {{ cancelBtnLabel }}
          </button>
        </div>

        <div class="modal-footer justify-content-around" v-if="type === 'alert'">
          <button
            :class="`col-4 offset-4 mt-5 p-1 btn ${btnOutline}`"
            block
            data-bs-dismiss="modal"
          >
            {{ confirmBtnLabel }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ErrorHandler } from "../../ErrorHandler.js";

export default {
  props: {
    type: {
      type: String,
      required: true,
    },
    confirmBtnLabel: {
      type: String,
      default: "OK",
    },
    cancelBtnLabel: {
      type: String,
    },
    title: String,
    message: [String, Object, Error],
    isAnError: Boolean,
    id: String,
    confirmFn: Function,
  },
  data() {
    return {
      errorHandler: new ErrorHandler(),
    };
  },
  computed: {
    computedMessage: function () {
      this.errorHandler.setResponse(this.message);
      return this.errorHandler.getMessage();
    },

    btnOutline() {
      return this.isAnError ? "btn-outline-danger" : "btn-outline-success";
    },
  },

  methods: {},
};
</script>

<style src="./BaseModal.css" />

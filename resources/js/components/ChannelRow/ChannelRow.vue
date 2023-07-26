<template>
  <div class="row items-start">
    <div class="col-12 col-md-6 col-xl-3 mt-2 me-3">
      <div>
        <label class="mb-0 me-3" for="name-input">Name</label>
      </div>
      <div>
        <input
          id="name-input"
          class="form-control"
          type="text"
          name="name"
          v-model="formValues.name"
          :class="!isValidNameLength ? 'is-invalid' : ''"
        />
        <span class="invalid-feedback">The name cannot be longer than 50 characters</span>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3 mt-2 me-3">
      <div>
        <label class="mb-0 me-3" for="priority-select">Priority</label>
      </div>
      <div>
        <select
          id="priority-select"
          class="form-select"
          name="priority"
          v-model="formValues.priority"
        >
          <option v-for="option in priorityOptions" :value="option.value" :key="option.value">
            {{ option.label }}
          </option>
        </select>
      </div>
    </div>
    <div
      class="col-2 col-xl-1 mt-2 me-3 d-flex align-items-end"
      :class="!isValidNameLength ? 'align-items-center' : ''"
    >
      <button
        :disabled="saveButtonDisabled || saving"
        data-placement="top"
        data-toggle="tooltip"
        title="Save"
        :class="saveButtonDisabled || saving ? 'btn-secondary' : 'btn-success'"
        class="btn w-100"
        @click.prevent="onSaveChannel"
      >
        Save
      </button>
    </div>
  </div>
</template>

<script>
import { defineComponent } from "vue";

const PRIORITY_OPTIONS = [
  { label: "HIGH", value: "high" },
  { label: "MEDIUM", value: "medium" },
  { label: "LOW", value: "low" },
  { label: "DEFAULT", value: "default" },
];

export default defineComponent({
  emits: ["save-channel"],

  props: {
    success: Boolean,
    saving: Boolean,
  },

  data() {
    return {
      formValues: {
        name: "",
        priority: "default",
      },
      priorityOptions: PRIORITY_OPTIONS,
    };
  },

  watch: {
    success(value) {
      if (value) this.resetForm();
    },
  },

  computed: {
    saveButtonDisabled() {
      return !this.formValues.name || !this.formValues.priority || !this.isValidNameLength;
    },

    isValidNameLength() {
      return this.formValues.name.length < 50;
    },
  },

  methods: {
    onSaveChannel() {
      this.$emit("save-channel", this.formValues);
    },

    resetForm() {
      this.formValues.name = "";
      this.formValues.priority = "default";
    },
  },
});
</script>

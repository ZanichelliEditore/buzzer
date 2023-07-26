import { ref } from "vue";
import { Modal } from "bootstrap";

export function useAlertModal() {
  const alertData = ref({
    outcome: {
      data: { message: "An unexpected error occurred" },
    },
    errorCallback: null,
  });
  function toggleAlertModal() {
    const alertModal = new Modal(document.getElementById("alertModal"));
    alertModal.toggle();
  }
  function openAlertModal(outcome, callback = null) {
    alertData.value.outcome = outcome;
    alertData.value.errorCallback = callback;
    toggleAlertModal();
  }

  return { alertData, openAlertModal };
}

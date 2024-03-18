<template>
    <div class="mx-5">
        <div class="form-group mb-4 position-absolute" style="z-index: 1">
            <h2 class="d-inline-flex align-items-center">Failed Jobs</h2>
            <button
                id="retry-button"
                @click="retryAllJobs()"
                class="ms-2 mb-2 btn btn-sm btn-success"
                :disabled="failedJobs.length == 0"
            >
                Retry first 1000 jobs
                <i class="fa fa-recycle"></i>
            </button>
            <button
                id="delete-button"
                class="ms-1 mb-2 btn btn-sm btn-danger"
                :disabled="failedJobs.length == 0"
                data-bs-toggle="modal"
                data-bs-target="#deleteAllJobsModal"
            >
                Delete all
                <i class="fa fa-trash-o"></i>
            </button>
        </div>
        <TableInput
            v-model:q="query.q"
            v-model:limit="query.limit"
        ></TableInput>

        <div class="container-fluid">
            <Table
                :columns="columns"
                :query="query"
                :objects="failedJobs"
                :loading-objects="loadingObjects"
                @retry-job="retryJob"
                @set-item-to-delete="setJobId"
                @order-by="orderBy"
            ></Table>
            <TablePaginator
                :objects-length="failedJobs.length"
                title="Failed Jobs"
                :pagination="pagination"
                :limit="query.limit"
                @change-page="changePage"
            ></TablePaginator>
        </div>
        <base-modal
            type="confirmModal"
            :confirmFn="deleteJob"
            title="Delete job"
            confirmBtnLabel="YES"
            cancelBtnLabel="NO"
            message="Are you sure you want to delete the job?"
            id="confirmDeleteModal"
        ></base-modal>
        <base-modal
            type="confirmModal"
            :confirmFn="deleteAllJob"
            title="Delete all the jobs"
            confirmBtnLabel="YES"
            cancelBtnLabel="NO"
            message="Are you sure you want to delete all jobs?"
            id="deleteAllJobsModal"
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
</template>

<script>
import { useTableQuery } from "../hook/tableQuery";
import { useFailedJobsStore } from "../store/failedJobs";
import { defineComponent } from "vue";
import Table from "../components/Table/Table.vue";
import TableInput from "../components/Table/TableInput.vue";
import TablePaginator from "../components/Table/TablePaginator.vue";
import { useAlertModal } from "../hook/alertModal";
import { storeToRefs } from "pinia";

export default defineComponent({
    data() {
        return {
            columns: [
                {
                    label: "Id",
                    field: "id",
                    orderby: "id",
                    type: "text",
                    dimension: "small",
                },
                {
                    label: "Subscriber",
                    field: "subscriber",
                    orderby: "subscriber",
                    type: "text",
                    dimension: "medium",
                },
                {
                    label: "Channel",
                    field: "channel",
                    orderby: "channel",
                    type: "text",
                    dimension: "medium",
                },
                {
                    label: "Payload",
                    field: "payload",
                    orderby: "payload",
                    type: "text",
                    dimension: "large",
                },
                {
                    label: "Exception",
                    field: "exception",
                    orderby: "exception",
                    type: "text",
                    dimension: "large",
                },
                {
                    label: "Failed at",
                    field: "failed_at",
                    orderby: "failed_at",
                    type: "text",
                    dimension: "medium",
                },
                {
                    type: "buttonRetry",
                    dimension: "small",
                },
                {
                    type: "buttonDelete",
                    dimension: "small",
                },
            ],
            jobId: null,
        };
    },

    setup() {
        const failedJobsStore = useFailedJobsStore();
        const { orderBy, pagination, query, changePage, makePagination } =
            useTableQuery(failedJobsStore.getFailedJobs);
        const { alertData, openAlertModal } = useAlertModal();

        const { failedJobs, loadingObjects, meta, links } =
            storeToRefs(failedJobsStore);

        return {
            failedJobsStore,
            failedJobs,
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

    components: {
        Table,
        TableInput,
        TablePaginator,
    },

    async mounted() {
        await this.getFailedJobs();
    },

    methods: {
        setJobId(id) {
            this.jobId = id;
        },
        hideAlertModal() {
            this.showAlert = false;
            if (!!this.errorCallback) {
                this.errorCallback();
            }
        },
        async getFailedJobs() {
            await this.failedJobsStore
                .getFailedJobs(this.query)
                .catch((error) => {
                    this.emitter.emit("newNotification", {
                        message: error.message,
                        type: "ERROR",
                    });
                });
            this.makePagination(this.meta, this.links);
        },

        deleteAllJob() {
            this.failedJobsStore
                .deleteAllJobs()
                .then((res) => {
                    this.emitter.emit("newNotification", {
                        message: "Jobs deleted",
                        type: "SUCCESS",
                    });
                    this.getFailedJobs();
                })
                .catch((err) => {
                    this.openAlertModal(err.response, true);
                });
        },

        retryJob(id) {
            this.failedJobsStore
                .retryJob(id)
                .then((res) => {
                    this.emitter.emit("newNotification", {
                        message: "Job queued",
                        type: "SUCCESS",
                    });
                    this.getFailedJobs();
                })
                .catch((err) => {
                    this.openAlertModal(err.response, true);
                });
        },

        retryAllJobs() {
            this.failedJobsStore
                .retryAllJobs()
                .then((res) => {
                    this.emitter.emit("newNotification", {
                        message: "Jobs queued",
                        type: "SUCCESS",
                    });
                    this.getFailedJobs();
                })
                .catch((err) => {
                    this.openAlertModal(err.response, true);
                });
        },

        deleteJob() {
            this.failedJobsStore
                .deleteJob(this.jobId)
                .then((res) => {
                    this.emitter.emit("newNotification", {
                        message: "Job deleted",
                        type: "SUCCESS",
                    });
                    this.getFailedJobs();
                })
                .catch((err) => {
                    this.openAlertModal(err.response, true);
                });
        },
    },
});
</script>

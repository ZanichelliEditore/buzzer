import { defineStore } from "pinia";
import axios from "axios";
import { useTableQuery } from "../hook/tableQuery";
export const useFailedJobsStore = defineStore("failedJobs", {
  state: () => ({
    failedJobs: [],
    loadingObjects: false,
    meta: {},
    links: {},
  }),

  actions: {
    async getFailedJobs(query) {
      try {
        this.loadingObject = true;
        const response = await axios.get("/admin/failedJobs/", { params: query });
        const { data, links, meta } = response.data;
        this.failedJobs = data;
        this.meta = meta;
        this.links = links;
        this.loadingObject = false;

        return { meta, links };
      } catch (error) {
        this.loadingObjects = false;
        throw error.response.data;
      }
    },

    async retryJob(id) {
      return await axios.get(`/admin/failedJobs/retry/${id}`);
    },

    async retryAllJobs() {
      return await axios.get("/admin/failedJobs/retry/all");
    },

    async deleteJob(id) {
      return await axios.delete(`/admin/failedJobs/${id}`);
    },

    async deleteAllJobs() {
      return await axios.delete("/admin/failedJobs/all/");
    },
  },
});

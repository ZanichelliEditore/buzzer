import { defineStore } from "pinia";
import axios from "axios";

export const usePublisherStore = defineStore("publisher", {
  state: () => ({
    publishers: [],
    publishersByChannel: [],
    loadingObjects: false,
    meta: {},
    links: {},
  }),

  actions: {
    async fetchPublishers(query) {
      try {
        this.loadingObject = true;
        const response = await axios.get("/admin/publishers", {
          params: query,
        });
        const { data, links, meta } = response.data;
        this.publishers = data;
        this.meta = meta;
        this.links = links;
        this.loadingObject = false;

        return { meta, links };
      } catch (error) {
        this.loadingObject = false;
        throw error.response.data;
      }
    },

    async getPublishersByChannel(id) {
      try {
        this.loadingObject = true;
        const response = await axios.get(`/admin/channels/${id}/publishers`);

        this.publishersByChannel = response.data.data;
        this.loadingObject = false;
      } catch (error) {
        this.loadingObject = false;
      }
    },

    async postPublisherToChannel({ payload, publisherId }) {
      const url = `/admin/publishers/${publisherId}/channels`;

      return await axios.post(url, payload);
    },

    async savePublisher(url, payload) {
      return await axios.post(url, payload);
    },

    async deletePublisher(id) {
      return await axios.delete(`/admin/publishers/${id}`);
    },
  },
});

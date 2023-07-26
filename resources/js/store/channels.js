import { defineStore } from "pinia";
import axios from "axios";
export const useChannelsStore = defineStore("channels", {
  state: () => ({
    channels: [],
    loadingObjects: false,
    meta: {},
    links: {},
  }),

  actions: {
    async fetchChannel(id) {
      return await axios.get(`/admin/channels/${id}`);
    },

    async fetchObjects(query) {
      try {
        this.loadingObject = true;
        const response = await axios.get("/admin/channels", {
          params: query,
        });
        const { data, links, meta } = response.data;
        this.channels = data;
        this.meta = meta;
        this.links = links;
        this.loadingObject = false;

        return { meta, links };
      } catch (error) {
        this.loadingObject = false;
        throw error.response.data;;
      }
    },

    async saveChannel(payload) {
      const url = "/admin/channels";

      return await axios.post(url, payload);
    },

    async deleteChannel(id) {
      return await axios.delete(`/admin/channels/${id}`);
    },
  },
});

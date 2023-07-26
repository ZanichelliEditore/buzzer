import { defineStore } from "pinia";
import axios from "axios";

export const useSubscribersStore = defineStore("subscribers", {
  state: () => ({
    subscribersByChannel: [],
    subscribers: [],
    loadingObjects: false,
    meta: {},
    links: {},
  }),

  actions: {
    async fetchSubscribers(query) {
      try {
        this.loadingObject = true;
        const response = await axios.get("/admin/subscribers", {
          params: query,
        });
        const { data, links, meta } = response.data;
        this.subscribers = data;
        this.meta = meta;
        this.links = links;
        this.loadingObject = false;

        return { meta, links };
      } catch (error) {
        this.loadingObject = false;
        throw error.response.data;
      }
    },

    async getSubscribersByChannel(id) {
      try {
        this.loadingObject = true;
        const response = await axios.get(`/admin/channels/${id}/subscribers`);

        this.subscribersByChannel = response.data.data;
        this.loadingObject = false;
      } catch (error) {
        this.loadingObject = false;
      }
    },

    async postSubscriberToChannel({ payload, subscriberId }) {
      const url = `/admin/subscribers/${subscriberId}/channels`;

      return await axios.post(url, payload);
    },

    async saveSubscriber({ payload }) {
      const url = `/admin/subscribers`;

      return await axios.post(url, payload);
    },

    async deleteSubscriber(id) {
      return await axios.delete(`/admin/subscribers/${id}`);
    },
  },
});

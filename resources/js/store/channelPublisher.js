import { defineStore } from "pinia";
import axios from "axios";
export const useChannelPublisherStore = defineStore("channelPublisher", {
  state: () => ({}),

  actions: {
    async deleteChannelPublisher(publisherId, channelId) {
      return await axios.delete(`/admin/publishers/${publisherId}/channels/${channelId}`);
    },
  },
});

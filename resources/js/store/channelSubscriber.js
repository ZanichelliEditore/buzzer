import { defineStore } from "pinia";
import axios from "axios";
export const useChannelSubscriberStore = defineStore("channelSubscriber", {
  state: () => ({}),

  actions: {
    async deleteChannelSubscriber(id) {
      return await axios.delete(`/admin/channel-subscriber/${id}`);
    },
  },
});

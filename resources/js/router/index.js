import { createRouter, createWebHistory } from "vue-router";

const routes = [
  {
    path: "/",
    redirect: "/channels",
  },

  {
    path: "/channels",
    name: "Channels",
    component: () => import("../views/Channels.vue"),
  },

  {
    path: "/subscribers",
    name: "Subscribers",
    component: () => import("../views/Subscribers.vue"),
  },

  {
    path: "/publishers",
    name: "Publishers",
    component: () => import("../views/Publishers.vue"),
  },

  {
    path: "/failedJobs",
    name: "FailedJobs",
    component: () => import("../views/FailedJobs.vue"),
  },

  {
    path: "/channels/:id",
    component: () => import("../views/Channel.vue"),
    props: castRouteParams,
  },
];

function castRouteParams(route) {
  return {
    id: Number(route.params.id),
  };
}

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;

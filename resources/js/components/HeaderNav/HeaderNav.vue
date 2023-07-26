<template>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
    <div class="d-flex align-items-center">
        <button
          class="navbar-toggler me-3"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarSupportedContent"
          aria-controls="navbarSupportedContent"
          aria-expanded="false"
          aria-label="Toggle navigation"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
        <a class="navbar-brand" @click="onClick(rootElem.link)" :href="rootElem.link">{{
          rootElem.label
        }}</a>
    </div>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav mt-3 mt-lg-0">
        <li class="nav-item active" v-for="(elem, index) in navElemList" v-bind:key="index">
          <router-link class="nav-link" :to="elem.link">{{ elem.label }}</router-link>
        </li>
      </ul>
      <ul v-if="showLogout" class="navbar-nav ms-auto">
        <li class="nav-item active ms-auto">
          <a class="nav-link" :href="logoutRoute" @click="onClick(logoutRoute)">Logout</a>
        </li>
      </ul>
    </div>
  </nav>
</template>

<script>
export default {
  data() {
    return {
      rootElem: { label: "Buzzer", link: "/channels" },
      navElemList: [
        { label: "Channels", link: "/channels" },
        { label: "Subscribers", link: "/subscribers" },
        { label: "Publishers", link: "/publishers" },
        { label: "Failed Jobs", link: "/failedJobs" },
      ],
      logoutRoute: "/logout",
    };
  },
  methods: {
    onClick(redirectUrl) {
      const splittedUrl = window.location.href.split("/");
      const editableElemId = parseInt(splittedUrl[splittedUrl.length - 1]);

      if (typeof editableElemId === NaN) {
        return;
      }
    },
  },
  computed: {
    showLogout() {
      return import.meta.env.VITE_SHOW_LOGOUT.toLowerCase() === "true";
    },
  }
};
</script>

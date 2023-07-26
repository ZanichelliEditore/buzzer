import { createApp } from "vue";
import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap";
import { createPinia } from "pinia";
import mitt from "mitt";
import "@zanichelli/albe-web-components/www/build/web-components-library.css";
import { defineCustomElements, applyPolyfills } from "@zanichelli/albe-web-components/loader";
import router from "./router";
const emitter = mitt();
import App from "./App.vue";
import HeaderNav from "./components/HeaderNav/HeaderNav.vue";
import BaseModal from "./components/BaseModal/BaseModal.vue";
const pinia = createPinia();
const app = createApp({
  components: {
    App,
  },
});
app.use(pinia);
app.config.globalProperties.emitter = emitter;

app.component("header-nav", HeaderNav);
app.component("base-modal", BaseModal);

applyPolyfills().then(() => {
  defineCustomElements();
});

app.use(router);
app.mount("#app");

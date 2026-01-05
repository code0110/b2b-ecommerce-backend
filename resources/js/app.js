import { createApp } from 'vue';
import { createPinia } from 'pinia';   // <<–– adăugăm Pinia
import App from './App.vue';
import router from './router';

// Bootstrap (dacă îl folosești)
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'bootstrap';

// CSS-ul tău
import '../css/app.css'; // sau ce folosești tu

import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";

const app = createApp(App);
const pinia = createPinia();          // <<–– instanță Pinia

app.use(pinia);                       // <<–– foarte important: înregistrăm Pinia
app.use(router);
app.use(Toast);

app.mount('#app');

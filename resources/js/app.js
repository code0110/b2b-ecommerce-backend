import { createApp } from 'vue';
import App from './App.vue';
import router from './router';

// Bootstrap (dacă în template aveai bootstrap)
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';

// CSS-ul tău custom
import '../css/app.css';
// sau import './assets/main.css' dacă asta foloseai

const app = createApp(App);

app.use(router);

app.mount('#app');

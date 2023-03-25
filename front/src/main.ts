import {createApp} from 'vue';
// @ts-ignore
import App from './App.vue';
import router from './router';
import store from "./store";
import ElementPlus from 'element-plus';
import 'element-plus/dist/index.css';
import './assets/main.scss';
import Pusher from "./libs/Pusher";
import socketio from "./libs/Socketio";
import * as ElementPlusIconsVue from '@element-plus/icons-vue'

const app = createApp(App);
app.provide('pusher', Pusher);
app.provide('socketio', socketio);
app.use(store);
app.use(router);
app.use(ElementPlus);
// @ts-ignore
for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
    app.component(key, component)
}
app.mount('#app');

import {createRouter, createWebHistory} from 'vue-router'
import Cache from "@/libs/Cache";
import player from "@/api/player";
import store from "@/store";

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: '/',
            name: 'index',
            component: () => import("@/views/Index.vue")
        },
        {
            path: '/room',
            name: 'room',
            component: () => import("@/views/Room.vue")
        },
    ]
})
router.beforeEach(async (to, from, next) => {
    let token = Cache.getToken();
    if (!token && to.name != 'index') {
        next('/');
        return;
    }
    try {
        let info = await player.detail();
        store.commit('Player/setInfo', info.data);
        if (info.data.room.id && to.name != 'room') {
            next('/room');
            return;
        } else if (!info.data.room.id && to.name == 'room') {
            next('/');
            return;
        }
    } catch (e) {
        Cache.remove();
        //store.commit('Player/setInfo', {});
        if (to.name != 'index') {
            next('/');
            return;
        }
    }
    next();
});
export default router

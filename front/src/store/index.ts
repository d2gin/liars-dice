import {createStore} from "vuex";
import Player from "@/store/modules/Player";
import index from "@/api/index";
import {debounce} from "@/libs/Utils";

export default createStore({
    state: {
        roomList: [],
        statistics: {
            online: 0,
            gaming: 0,
            preparing: 0,
        },
    },
    getters: {},
    mutations: {
        setRoomList(state, list) {
            state.roomList = list;
        },
        setStatistics(state, statistics) {
            if (statistics) {
                state.statistics = statistics;
            }
        },
    },
    actions: {
        refreshRoomList({commit}: any, loading = false) {
            debounce(() => {
                index.loading(loading).roomList().then((res: any) => {
                    commit('setRoomList', res.data);
                });
            })();
        },
        refreshStatistics({commit}: any, loading = false) {
            debounce(() => {
                index.loading(loading).statistics().then((res: any) => {
                    commit('setStatistics', res.data);
                });
            })();
        }
    },
    modules: {
        Player,
    }
});

import player from "@/api/player";
import {debounce} from "@/libs/Utils";
import store from "@/store";

export default {
    namespaced: true,
    state: {
        info: {},
        connectionId: null,
    },
    getters: {},
    mutations: {
        setId(state: any, id: any) {
            state.info.id = id;
        },
        setNickname(state: any, nickname: any) {
            state.info.nickname = nickname;
        },
        setSex(state: any, sex: number) {
            state.info.sex = sex;
        },
        setConnectionId(state: any, connectionId: number) {
            state.connectionId = connectionId;
        },
        setToken(state: any, token: string) {
            state.info.token = token;
        },
        setRoom(state: any, room: any) {
            state.info.room = room;
        },
        setDices(state: any, dices: any) {
            state.info.dices = dices;
        },
        setGuess(state: any, guess: any) {
            state.info.guess = guess;
        },
        setInfo(state:any, info:any) {
            state.info = info;
        },
    },
    actions: {
        refreshInfo({commit, state}: any, loading = false) {
            debounce(() => {
                player.loading(loading).detail().then((res: any) => {
                    commit('setInfo', res.data);
                });
            }).apply(this);
        }
    }
};

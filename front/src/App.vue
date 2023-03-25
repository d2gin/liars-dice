<template>
    <div>
        <RouterView/>
        <div class="footer">
            {{statistics.online}}人在线 / {{statistics.gaming}}人游戏中 / {{statistics.preparing}}人准备中
        </div>
    </div>
</template>

<script setup lang="ts">
    import {useStore} from "vuex";
    import Cache from "./libs/Cache";
    import player from "./api/player";
    import {useSocketio} from "./libs/Socketio";
    import {ElMessage, ElNotification} from "element-plus";
    import {computed, onMounted} from "vue";

    let socketio = useSocketio();
    let store = useStore();
    let statistics = computed(() => store.state.statistics);

    socketio.off('toast').on('toast', (res: any) => {
        ElMessage({
            message: res.message,
            type: res.type,
        });
    });
    socketio.off('notice').on('notice', (res: any) => {
        ElNotification({
            message: res.message,
            type: res.type,
        })
    });
    socketio.onResident('connection', (data: any) => {
        store.commit('Player/setConnectionId', data.id);
        if (Cache.getToken()) {
            player.bindConnectionId(data.id);
        }
    }).connect();
    onMounted(() => {
        store.dispatch('refreshStatistics')
    });
    socketio.off('player_update').on('player_update', () => store.dispatch('Player/refreshInfo'));
    socketio.off('rooms_update').on('rooms_update', (data: any) => store.dispatch('refreshRoomList'));
    socketio.off('statistics_update').on('statistics_update', (data: any) => store.dispatch('refreshStatistics'));
</script>

<style lang="scss" scoped>
    .footer {
        padding: 10px 0;
        text-align: center;
        font-size: 12px;
        color: #999999;
    }
</style>

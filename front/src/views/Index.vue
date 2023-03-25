<template>
    <div class="container">
        <el-space style="width: 100%" fill wrap>
            <el-card class="box-card">
                <template #header>玩家信息</template>
                <el-form @submit.prevent="handleSetInformation" label-width="120px">
                    <el-form-item label="昵称">
                        <el-input v-model="nickname"/>
                    </el-form-item>
                    <el-form-item label="性别">
                        <el-radio-group v-model="store.state.Player.info.sex">
                            <el-radio :label="1">男</el-radio>
                            <el-radio :label="2">女</el-radio>
                        </el-radio-group>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" navite-type="submit" @click="handleSetInformation">设置</el-button>
                    </el-form-item>
                </el-form>
            </el-card>
            <el-card class="box-card" v-if="store.state.Player.info.token">
                <template #header>创建房间</template>
                <el-form @submit.prevent="handleCreateRoom" label-width="120px">
                    <el-form-item label="房间名称">
                        <el-input v-model="roomName"/>
                    </el-form-item>
                    <el-form-item label="房间人数">
                        <el-input type="number" v-model="memberLimit"/>
                    </el-form-item>
                    <el-form-item label="骰子数">
                        <el-input type="number" v-model="diceNum"/>
                    </el-form-item>
                    <el-form-item label="投降输一半">
                        <el-switch v-model="loseHalf"/>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" native-type="submit">创建</el-button>
                    </el-form-item>
                </el-form>
            </el-card>
            <el-card class="box-card">
                <template #header>房间列表</template>
                <el-empty v-if="roomList.length == 0" :image-size="100"/>
                <template :key="index" v-for="(room, index) in roomList">
                    <div class="room-title">
                        <el-space wrap>
                            <el-button type="primary"
                                       size="small"
                                       v-if="room.can_enter"
                                       @click="() => handleEnterRoom(room.id)"
                                       plain round>
                                加入
                            </el-button>
                            <div class="room-name">{{room.name}}</div>
                        </el-space>
                    </div>
                    <div class="room-meta">
                        <el-tag class="margin-right"
                                type="success"
                                effect="dark"
                                v-if="room.status == 1">
                            房间准备中...
                        </el-tag>
                        <el-tag class="margin-right" type="warning" v-else-if="room.status == 2">房间正在游戏</el-tag>
                        <el-tag class="margin-right" type="warning" v-else-if="room.status == 3">房间正在结算</el-tag>
                        <el-tag class="margin-right">
                            {{room.member_total}}/{{room.member_limit}}人
                        </el-tag>
                        <el-tag class="margin-right" v-if="room.lose_half">投降输一半</el-tag>
                        <el-tag class="margin-right">{{room.dice_num}}骰/人</el-tag>
                        <el-tag class="margin-right">胜 +{{room.win_score}}分/场</el-tag>
                        <el-tag class="margin-right">败 -{{room.lose_score}}分/场</el-tag>
                    </div>
                </template>
            </el-card>
        </el-space>
    </div>
</template>

<script lang="ts" setup>
    import {useStore} from "vuex";
    import {ElMessage, ElMessageBox} from "element-plus";
    import player from "@/api/player";
    import room from "@/api/room";
    import {useSocketio} from "@/libs/Socketio";
    import {computed, onMounted, ref, unref} from "vue";
    import {useRouter} from "vue-router";
    import Cache from "@/libs/Cache";

    let store = useStore();
    let socketio = useSocketio();
    let router = useRouter();
    let nickname = ref(store.state.Player.info.nickname);
    let roomName = ref('');
    let memberLimit = ref(20);
    let diceNum = ref(4);
    let loseHalf = ref(true);
    let roomList = computed(() => store.state.roomList);

    // 绑定信息
    let handleSetInformation = () => {
        player.loading().bind(unref(nickname), store.state.Player.info.sex).then((res: any) => {
            ElMessage.success(res.message);
            Cache.setToken(res.data.token);
            store.dispatch('Player/refreshInfo');
        }, (err: any) => ElMessage.error(err.message));
    };

    // 创建房间
    let handleCreateRoom = () => {
        room.loading().create({
            room_name: unref(roomName),
            dice_num: unref(diceNum),
            member_limit: unref(memberLimit),
            lose_half: unref(loseHalf),
        }).then((res: any) => {
            ElMessage.success(res.message);
            roomName.value = '';
            store.dispatch('Player/refreshInfo');
            router.push('room');
        }, (err: any) => ElMessage.error(err.message));
    }

    let handleEnterRoom = (roomId: any) => {
        ElMessageBox.confirm("确定进入房间？", '进入房间', {cancelButtonText: "取消", confirmButtonText: "确定"}).then(() => {
            // 进入房间
            room.loading().enter(roomId).then((res: any) => {
                socketio.subcribe(`room#${roomId}`, {
                    id: store.state.Player.info.id,
                    token: store.state.Player.info.token
                });
                ElMessage.success(res.message);
                store.dispatch('Player/refreshInfo');
                router.push('/room');
            }, (res: any) => ElMessage.error(res.message));
        });
    };

    onMounted(() => {
        store.dispatch('refreshRoomList', true);
    });
</script>

<style lang="scss" scoped>
    .room-title {
        margin-bottom: 10px;
    }

    .room-meta {
        padding-bottom: 10px;
        margin-bottom: 10px;
        border-bottom: 1px dashed #e5e5e5;
    }

    .box-card {
        width: 100%;
    }
</style>

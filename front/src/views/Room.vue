<template>
    <div class="container">
        <el-space style="width: 100%" fill>
            <el-card class="box-card">
                <template #header>
                    房间信息
                    <el-button @click="handleLeaveRoom"
                               type="danger"
                               size="small"
                               v-if="room.status != 2"
                               plain round>退出
                    </el-button>
                </template>
                <el-space fill wrap>
                    <div class="room-title">
                        <span class="margin-right">{{room.name}}</span>
                        <el-button type="primary"
                                   size="small" @click="handleStart"
                                   v-if="isCreator && room.member_total > 1 && room.status != 2">
                            开局
                        </el-button>

                        <el-tooltip content="这意味着这个房间可以被其他玩家加入进来" placement="right">
                            <el-button type="warning"
                                       size="small"
                                       @click="handleRestart"
                                       v-if="isCreator && room.status == 3">
                                开放房间
                            </el-button>
                        </el-tooltip>
                    </div>
                    <div v-if="room.settlement_result">
                        <div>
                            结算：{{room.settlement_result}}
                        </div>
                        <div>
                            对局：{{room.picker.sponsor.nickname}} 劈
                            {{room.picker.referto.nickname}}({{room.picker.referto.guess.num}}个{{room.picker.referto.guess.point}})
                        </div>
                        <div>
                            胜者：{{room.winner.nickname}}
                        </div>
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
                        <el-tag class="margin-right" v-if="room.member_total">
                            {{room.member_total}}人
                        </el-tag>
                        <el-tag class="margin-right-xs" v-if="room.lose_half">投降输一半</el-tag>
                        <el-tag class="margin-right">{{room.dice_num}}骰/人</el-tag>
                        <el-tag class="margin-right">胜 +{{room.win_score}}分/场</el-tag>
                        <el-tag class="margin-right">败 -{{room.lose_score}}分/场</el-tag>
                        <el-tag class="margin-right" v-if="room.is_zhai">斋</el-tag>
                    </div>
                </el-space>
            </el-card>
            <el-card class="box-card">
                <template #header>
                    我的
                </template>
                <el-form>
                    <el-form-item label="名字">
                        <el-tag round hit size="small" v-if="isCreator" class="margin-right-xs">
                            房主
                        </el-tag>
                        <span>{{playerInfo.nickname}}</span>
                    </el-form-item>
                    <template v-if="playerInfo.room.is_turn_to_me && room.status == 2">
                        <el-form-item label="上次" v-if="playerInfo.guess">
                            {{playerInfo.guess.num}}个{{playerInfo.guess.point}}
                        </el-form-item>
                        <el-form-item label="上家" v-if="room.prev_guess">
                            {{room.prev_guess.num}}个{{room.prev_guess.point}}
                        </el-form-item>
                        <el-form-item label="叫骰">
                            <el-row :gutter="5">
                                <el-col :span="6">
                                    <el-input v-model="guess.num" type="number"/>
                                </el-col>
                                <el-col :span="2" style="text-align: center">
                                    个
                                </el-col>
                                <el-col :span="6">
                                    <el-input v-model="guess.point" type="number"/>
                                </el-col>
                                <el-col :span="3">
                                    <el-checkbox v-model="isPozhai" v-if="room.is_zhai">破斋</el-checkbox>
                                    <el-checkbox v-model="isZhai" v-else>斋</el-checkbox>
                                </el-col>
                                <el-col :span="7">
                                    <el-button style="margin-left: 10px" @click="handleGuess" type="primary">提交
                                    </el-button>
                                </el-col>
                            </el-row>
                        </el-form-item>
                    </template>
                    <el-form-item label="叫骰" v-else-if="playerInfo.guess">
                        {{playerInfo.guess.num}}个{{playerInfo.guess.point}}
                    </el-form-item>

                    <el-form-item label="骰子">
                        <div class="dice-list">
                            <div class="dice" v-for="(dice, index) in dices" :key="index">
                                {{showDices ? dice.point.value : '*'}}
                            </div>
                            <template v-if="!playerInfo.guess">
                                <el-tag hit style="cursor: pointer"
                                        type="primary"
                                        class="margin-left"
                                        @click="handleRollDices">
                                    摇一摇
                                </el-tag>
                            </template>
                            <el-tag hit style="cursor: pointer"
                                    type="warning"
                                    class="margin-left"
                                    @click="handleToggleShowDices">
                                {{showDices?'藏起来':'瞄一眼'}}
                            </el-tag>
                        </div>
                    </el-form-item>
                    <el-form-item label="好汉" v-if="room.status==2">
                        <el-button type="warning" size="small">投降</el-button>
                        <el-tooltip content="开骰指的是开上家的骰，相当于“劈”上家" placement="right">
                            <el-button type="primary" size="small">开骰</el-button>
                        </el-tooltip>
                    </el-form-item>
                </el-form>
            </el-card>
            <el-card class="box-card">
                <template #header>玩家</template>
                <div class="players" v-for="(member, index) in room.members" :key="index">
                    <div class="player-meta">
                        <el-tag class="margin-right-xs" type="info" hit round>{{index + 1}}</el-tag>
                        <el-button class="margin-right-xs" size="small" type="warning"
                                   @click="() => handlePick(member.id)"
                                   v-if="canPick && playerInfo.id !== member.id && member.guess">
                            劈Ta
                        </el-button>
                        <el-button size="small" class="margin-right-xs" type="danger"
                                   @click="() => handleKickOut(member.id)"
                                   v-if="isCreator && playerInfo.id !== member.id">
                            踢掉
                        </el-button>
                        <span v-if="playerInfo.id == member.id">
                            [自己]
                        </span>
                        <span v-else>
                            {{member.is_online ? '' : '[离线]'}}
                        </span>
                        <span v-if="member.is_prev && playerInfo.room.is_turn_to_me">
                            [上家]
                        </span>
                        <span v-if="room.creator == member.id">
                            [房主]
                        </span>
                        <span>
                            {{member.nickname}} ({{member.score}}分)
                        </span>
                        <div class="dice-list margin-left" style="display: inline-block">
                            <div class="dice" v-for="(dice, index) in member.dices" :key="index">
                                {{dice.point.value}}
                            </div>
                        </div>
                        <el-tag class="margin-right" type="primary" v-if="member.turn_to && room.status == 2" hit>
                            叫骰ing
                        </el-tag>
                    </div>
                    <el-tag class="margin-right" type="primary" hit v-if="member.guess">
                        猜 {{member.guess.num}}个{{member.guess.point}}
                    </el-tag>
                    <el-tag class="margin-right" type="success" hit>胜{{member.win}}场</el-tag>
                    <el-tag class="margin-right" type="error" hit>输{{member.lose}}场</el-tag>
                </div>
            </el-card>
        </el-space>
    </div>
</template>

<script lang="ts" setup>
    import {useStore} from "vuex";
    import {onBeforeRouteLeave, useRoute, useRouter} from "vue-router";
    import {ref, onMounted, unref, reactive, computed, watch} from 'vue';
    import player from "@/api/player";
    import roomApi from "@/api/room";
    import index from "@/api/index";
    import {ElMessage, ElMessageBox} from "element-plus";
    import {useSocketio} from "@/libs/Socketio";

    let store = useStore();
    let router = useRouter();
    let room = ref<any>({});
    let playerInfo = computed(() => store.state.Player.info);
    let dices = computed(() => playerInfo.value.dices);
    let isCreator = computed(() => room.value.creator == playerInfo.value.id);
    let canPick = computed(() => room.value.status == 2 && playerInfo.value.room.is_turn_to_me);
    let socketio = useSocketio();
    let guess = reactive({num: 0, point: 0,});
    let showDices = ref(false);
    let isZhai = ref(false);
    let isPozhai = ref(false);
    watch(room, (v: any) => {
        // 记录上家的猜骰
        if (v.prev_guess) {
            guess.num = v.prev_guess.num;
            guess.point = v.prev_guess.point;
        } else {// 没有上家就是刚开始游戏
            guess.num = v.members.length;
            guess.point = 0;
        }
        if (v.status == 3) {
            showDices.value = true;
        }
    });
    socketio.off('room_inside_update').on('room_inside_update', () => {
        store.dispatch('Player/refreshInfo');
        getRoom();
    });
    socketio.off('kicked_out').on('kicked_out', (data: any) => {
        if (data.id == playerInfo.value.id) {
            socketio.unSubcribe(`room#${unref(room).id}`);
            ElMessage.error('被房主踢出');
            router.push('/');
        }
    });

    socketio.off('connection').on('connection', () => {
        // 重连
        socketio.subcribe(`room#${room.value.id}`, {id: playerInfo.value.id, token: playerInfo.value.token});
    });
    onMounted(() => {
        roomApi.loading();
        getRoom().then((res: any) => {
            // 订阅房间
            socketio.subcribe(`room#${room.value.id}`, {id: playerInfo.value.id, token: playerInfo.value.token});
        });
    });

    let getRoom = () => {
        return roomApi.detail().then((res: any) => {
            room.value = res.data;
            return Promise.resolve(res);
        }, (err: any) => {
            // ElMessage.error(err.message);
            router.push('/');
        });
    };

    // 退出房间
    let leaveRoom = () => {
        return roomApi.loading().leave().then(() => {
            socketio.unSubcribe(`room#${unref(room).id}`);
        });
    };

    // 退出房间
    let handleLeaveRoom = () => {
        ElMessageBox.confirm("确定退出房间？", '退出房间', {
            cancelButtonText: "取消",
            confirmButtonText: "确定",
        }).then(() => {
            ElMessage.success('已退出');
            router.push('/');
        });
    }

    // 摇骰
    let handleRollDices = () => {
        return player.loading().rollDice().then(() => {
            store.dispatch('Player/refreshInfo');
        });
    };

    // 开局
    let handleStart = () => {
        guess.num = 0;
        guess.point = 0;
        ElMessageBox.confirm("开局后房间无法再加入玩家，确定开局？", '游戏开局', {
            cancelButtonText: "取消",
            confirmButtonText: "确定"
        }).then(() => {
            roomApi.start().then((res: any) => {
                ElMessage.success('已开局');
            }, (res: any) => ElMessage.error(res.message));
        });
    };

    // 提交猜骰
    let handleGuess = () => {
        roomApi.guess({
            num: guess.num,
            point: guess.point,
            is_zhai: unref(isZhai),
            is_pozhai: unref(isPozhai),
        }).then((res: any) => {
            ElMessage.success('已提交');
            store.dispatch('Player/refreshInfo');
            isZhai.value = false;
            isPozhai.value = false;
        }, (res: any) => ElMessage.error(res.message));
    };

    // 开骰
    let handlePick = (id: any) => {
        ElMessageBox.confirm("提交成功后本局将进入结算", '劈Ta', {
            cancelButtonText: "取消",
            confirmButtonText: "确定"
        }).then(() => {
            roomApi.loading().pick(id).then(() => {
                ElMessage.success('已提交');
                guess.num = 0;
                guess.point = 0;
            });
        });
    };
    // 骰子的显示
    let handleToggleShowDices = () => {
        showDices.value = !showDices.value;
    }

    // 重开
    let handleRestart = () => {
        ElMessageBox.confirm("开放后其他玩家可以加入进来", '房间开放', {
            cancelButtonText: "取消",
            confirmButtonText: "确定"
        }).then(() => {
            roomApi.loading().restart().then(() => {
                // 重开
            }, (res: any) => ElMessage.error(res.message));
        });
    };

    let handleKickOut = (id: any) => {
        ElMessageBox.confirm("是否踢掉当前玩家？", '踢出房间', {
            cancelButtonText: "取消",
            confirmButtonText: "确定"
        }).then(() => {
            roomApi.kickOut(id).then(() => {
            }, (err: any) => ElMessage.error(err.message));
        });
    }

    onBeforeRouteLeave((to, from) => {
        leaveRoom();
    });
</script>

<style lang="scss" scoped>
    .box-result__dice {
        display: inline-block;
        border: 1px solid #555555;
        width: 35px;
        height: 35px;
        text-align: center;
        margin: 0 5px;
    }

    .box {
        margin-bottom: 10px;
    }

    .box-card {
        width: 100%;
    }

    .dice-list {
        $width: 30px;

        .dice {
            display: inline-block;
            width: $width;
            height: $width;
            box-shadow: 0 0 3px #b0b0b0;
            border-radius: 3px;
            text-align: center;
            line-height: $width;
            margin-right: 5px;
            font-size: 13px;
            font-weight: bold;

            &:last-of-type {
                margin-right: 0;
            }
        }

        .point-1 {
            &:after {
                content: "";

            }
        }
    }

    .players {
        border-bottom: 1px dashed #e5e5e5;
        padding: 10px 0;
        margin-bottom: 5px;

        .player-meta {
            margin-bottom: 10px;
        }
    }
</style>

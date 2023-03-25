import {inject, ref, unref} from 'vue';
import {io} from "socket.io-client";
import type {Socket} from "socket.io-client/build/esm/socket";
import Config from "@/libs/Config";
import EventBus from "@/libs/EventBus";
import type {Event, EventList} from "@/libs/EventBus";

export const useSocketio = (): Socketio => <Socketio>inject('socketio');

export class Socketio {
    public io!: Socket;
    protected eventBus: EventBus;
    protected channels: string[] = [];
    protected residentEvents: EventList = {
        connect: [
            {
                event: () => {
                    console.log('connected');
                },
                once: false,
            },
        ],
        disconnect: [
            {
                event: () => {
                    console.log("disconnected");
                },
                once: false,
            },
        ],
    };

    constructor() {
        this.eventBus = new EventBus();
    }

    public connect(options = {}) {
        if (this.io?.connected) {
            return this;
        }
        this.io = io(Config.websocket, {
            ...options,
            transports: ['websocket'],
            autoConnect: false,
        });
        this.listen();
        this.io.connect();
        return this;
    }

    public disconnect() {
        if (this.io?.connected) {
            this.eventBus.offAny();
            this.io.disconnect();
        }
    }

    public isConnect() {
        return this.io?.connected;
    }

    public hasChannel(channel: string) {
        return this.channels.indexOf(channel) >= 0;
    }

    /**
     * 监听事件
     */
    public listen() {
        // 重置所有默认队列
        for (let event in this.residentEvents) {
            this.residentEvents[event].forEach((item: Event) => {
                // 置入最前
                this.eventBus.off(event, item.event).on(event, item.event, item.once, true);
            });
        }
        // websocket监听
        if (this.io) {
            this.io.off();
            for (let event in this.eventBus.getEventList()) {
                this.io.on(event, (...args: any[]) => this.eventBus.trigger(event, ...args));
            }
        }
        return this;
    }

    /**
     * 卸载事件队列
     * @param name
     * @param resolver
     */
    public off(name: string, resolver?: Function) {
        this.eventBus.off(name, resolver);
        return this.listen();
    }

    /**
     * 监听事件队列
     * @param name
     * @param event
     * @param once
     * @param unshift
     */
    public on(name: string, event: Function, once: boolean = false, unshift: boolean = false) {
        if (this.io?.connected && name == 'connect') {
            event.apply(null);
            return this;
        }
        this.eventBus.on(name, event, once, unshift);
        return this.listen();
    }

    public onResident(name: string, event: Function, once: boolean = false) {
        let payload: Event = {event, once};
        if (!(name in this.residentEvents)) {
            this.residentEvents[name] = [];
        }
        this.residentEvents[name].push(payload);
        return this.on(name, event, once, true);
    }

    public subcribe(channel: string, info = {}) {
        this.emit('subcribe', {channel, data: info});
        this.onResident('subcribe_success', (chan: any) => {
            if (chan && this.channels.indexOf(chan) < 0) {
                this.channels.push(chan);
            }
        }, true);
        return this;
    }

    public unSubcribe(channel: string) {
        this.emit('unsubcribe', {channel});
    }

    public emit(event: string, ...args: any[]) {
        return this.io.volatile.emit(event, ...args);
    }
}

export default new Socketio();

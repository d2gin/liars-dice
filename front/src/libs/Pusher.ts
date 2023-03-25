import Config from "@/libs/Config";
import type Data from "@/types/Data";
import type {Event as BusEvent} from "@/libs/EventBus";
import EventBus from "@/libs/EventBus";
import {inject} from "vue";

export const usePusher = (): Pusher => <Pusher>inject('pusher');

export class Pusher {
    public io!: WebSocket;
    public dataReady: any[] = [];
    public channels: string[] = [];
    public ioState: number;
    protected eventBus: EventBus;
    protected residentEvents: {
        [key: string]: BusEvent[]
    } = {};

    protected connectionId = null;

    constructor() {
        this.ioState = WebSocket.CONNECTING;
        this.eventBus = new EventBus();
    }

    public connect() {
        if (this.ioState === WebSocket.OPEN) {
            return this;
        }
        this.io = new WebSocket(Config.websocket);
        return this.listen();
    }

    public listen() {
        this.io.onopen = this.onConnect.bind(this);
        this.io.onmessage = this.onMessage.bind(this);
        this.io.onclose = this.onClose.bind(this);
        this.io.onerror = this.onError.bind(this);
        return this;
    }

    public onMessage(e: MessageEvent) {
        let data: Data = JSON.parse(e.data);
        if (data.event == 'connection') {
            this.connectionId = data.data.id;
        }
        if (data.event) {
            this.eventBus.trigger(data.event, data.data);
        }
    }

    public onConnect(e: Event) {
        this.ioState = WebSocket.OPEN;
        let data;
        // 数据泄洪
        while (data = this.dataReady.shift()) {
            this.send(data);
        }
        this.eventBus.trigger('connect', e);
    }

    public listenConnection(event: Function, once: boolean = false, unshift: boolean = false) {
        if (this.ioState == WebSocket.OPEN) {
            event.apply(null, [{id: this.connectionId}]);
        } else this.eventBus.on('connection', event, once, unshift);
    }

    public onClose(e: Event) {
        this.ioState = WebSocket.CLOSED;
        this.eventBus.trigger('close', e);
        //this.eventBus.offAny();
    }

    public onError(e: Event) {
        this.eventBus.trigger('error', e);
    }


    public off(name: string, resolver?: Function) {
        this.eventBus.off(name, resolver);
        if (name in this.residentEvents) {
            // 还原常驻事件
            this.residentEvents[name].forEach((be: BusEvent) => {
                this.onResident(name, be.event, be.once);
            });
        }
        return this;
    }

    public on(name: string, event: Function, once: boolean = false, unshift: boolean = false) {
        if (name == 'connection') {
            this.listenConnection(event, once, unshift);
        } else this.eventBus.on(name, event, once, unshift);
        return this;
    }

    /**
     * @param name
     * @param event
     * @param once
     */
    public onResident(name: string, event: Function, once: boolean = false) {
        if (!(name in this.residentEvents)) {
            this.residentEvents[name] = [];
        }
        this.residentEvents[name].push({event, once});
        return this.on(name, event, once, true);
    }

    public send(data: Data) {
        if (this.ioState !== WebSocket.OPEN) {
            // 数据堆积
            this.dataReady.push(data);
        } else {
            this.io.send(JSON.stringify(data));
        }
        return true;
    }

    public subcribe(channel: string, info = {}) {
        this.send({data: {channel, info}, event: 'subcribe',});
        this.on('subcribeSuccess', (data: any) => {
            if (data.data['channel']) {
                this.channels.push(data.data.channel);
            }
        }, true);
    }

    public unSubcribe(channel: string) {
        this.send({data: {channel}, event: 'unSubcribe',});
    }

    public close() {
        if (this.ioState === WebSocket.OPEN) {
            this.ioState = WebSocket.CLOSING;
            this.io.close();
            this.channels = [];
        }
    }
}

export default new Pusher();

import {Http} from "@/libs/Http";

class Room extends Http {

    public create(data: {
        room_name: string,
        dice_num?: number,
        member_limit?: number,
        lose_half?: boolean,
    }) {
        return this.post('/api/room/create', data);
    }

    public detail() {
        return this.get('/api/room/detail');
    }

    public leave() {
        return this.post('/api/room/leave');
    }

    public enter(room_id: any) {
        return this.post('/api/room/enter', {room_id});
    }

    public start() {
        return this.post('/api/room/start');
    }

    public restart() {
        return this.post('/api/room/restart');
    }

    public guess(data: {
        num: any, point: any, is_zhai?: any, is_pozhai?: any
    }) {
        return this.post('/api/room/guess', data);
    }

    public pick(id: any) {
        return this.post('/api/room/pick', {id});
    }

    public kickOut(id: any) {
        return this.post('/api/room/kickOut', {id});
    }
}

export default new Room();
